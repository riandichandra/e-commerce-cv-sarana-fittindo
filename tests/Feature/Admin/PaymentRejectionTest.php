<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentRejectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_rejects_payment_with_reason_and_restores_order_stock(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->makeUser('pelanggan');
        $paymentMethod = $this->makePaymentMethod();
        $product = $this->makeProduct(['stock' => 0]);
        $order = $this->makeOrder($customer, $paymentMethod, $product, quantity: 2);
        $payment = $order->payment;

        $response = $this->actingAs($admin)->patch(route('admin.payments.reject', $payment), [
            'rejection_reason' => 'Nominal transfer tidak sesuai.',
        ]);

        $response
            ->assertRedirect(route('admin.payments.index'))
            ->assertSessionHas('success', 'Pembayaran ditolak, stok dikembalikan, dan status order diubah menjadi dibatalkan.');

        $payment->refresh();
        $order->refresh();
        $product->refresh();

        $this->assertSame('ditolak', $payment->status);
        $this->assertSame('Nominal transfer tidak sesuai.', $payment->rejection_reason);
        $this->assertSame($admin->id, $payment->verified_by);
        $this->assertNotNull($payment->verified_at);
        $this->assertSame('dibatalkan', $order->status);
        $this->assertSame($admin->id, $order->cancelled_by);
        $this->assertSame('Pembayaran ditolak: Nominal transfer tidak sesuai.', $order->cancellation_reason);
        $this->assertNotNull($order->cancelled_at);
        $this->assertSame($admin->id, $order->stock_restored_by);
        $this->assertNotNull($order->stock_restored_at);
        $this->assertSame(2, (int) $product->stock);
        $this->assertSame(Product::STATUS_AVAILABLE, $product->status);
    }

    public function test_admin_cannot_reject_payment_without_reason(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->makeUser('pelanggan');
        $paymentMethod = $this->makePaymentMethod();
        $product = $this->makeProduct(['stock' => 0]);
        $order = $this->makeOrder($customer, $paymentMethod, $product, quantity: 1);
        $payment = $order->payment;

        $response = $this->actingAs($admin)->from(route('admin.payments.index'))->patch(route('admin.payments.reject', $payment), [
            'rejection_reason' => '',
        ]);

        $response
            ->assertRedirect(route('admin.payments.index'))
            ->assertSessionHasErrors('rejection_reason');

        $this->assertSame('menunggu', $payment->fresh()->status);
        $this->assertSame('menunggu_verifikasi_pembayaran', $order->fresh()->status);
        $this->assertSame(0, (int) $product->fresh()->stock);
    }

    public function test_rejecting_already_processed_payment_does_not_restore_stock_twice(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->makeUser('pelanggan');
        $paymentMethod = $this->makePaymentMethod();
        $product = $this->makeProduct(['stock' => 0]);
        $order = $this->makeOrder($customer, $paymentMethod, $product, quantity: 3);
        $payment = $order->payment;

        $this->actingAs($admin)->patch(route('admin.payments.reject', $payment), [
            'rejection_reason' => 'Bukti transfer tidak valid.',
        ]);

        $this->actingAs($admin)->patch(route('admin.payments.reject', $payment->fresh()), [
            'rejection_reason' => 'Ditolak lagi.',
        ]);

        $this->assertSame(3, (int) $product->fresh()->stock);
        $this->assertSame('Bukti transfer tidak valid.', $payment->fresh()->rejection_reason);
    }

    public function test_admin_payment_list_shows_rejection_reason(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->makeUser('pelanggan');
        $paymentMethod = $this->makePaymentMethod();
        $product = $this->makeProduct(['stock' => 0]);
        $order = $this->makeOrder($customer, $paymentMethod, $product, quantity: 1);

        $order->payment->update([
            'status' => 'ditolak',
            'rejection_reason' => 'Bukti tidak terbaca.',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.payments.index'));

        $response->assertOk();
        $response->assertSee('Keterangan');
        $response->assertSee('Bukti tidak terbaca.');
    }

    public function test_admin_payment_list_uses_modal_for_rejection_reason(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->makeUser('pelanggan');
        $paymentMethod = $this->makePaymentMethod();
        $product = $this->makeProduct(['stock' => 0]);
        $this->makeOrder($customer, $paymentMethod, $product, quantity: 1);

        $response = $this->actingAs($admin)->get(route('admin.payments.index'));

        $response->assertOk();
        $response->assertSee('Tolak Pembayaran');
        $response->assertSee('Keterangan Penolakan');
        $response->assertSee('Contoh: Nominal transfer tidak sesuai dengan total pembayaran.');
    }

    public function test_customer_cannot_upload_payment_proof_after_payment_is_rejected(): void
    {
        Storage::fake('public');

        $admin = $this->makeUser('admin');
        $customer = $this->makeUser('pelanggan');
        $paymentMethod = $this->makePaymentMethod();
        $product = $this->makeProduct(['stock' => 0]);
        $order = $this->makeOrder($customer, $paymentMethod, $product, quantity: 1);

        $this->actingAs($admin)->patch(route('admin.payments.reject', $order->payment), [
            'rejection_reason' => 'Nominal transfer tidak sesuai.',
        ]);

        $formResponse = $this->actingAs($customer)->get(route('pelanggan.orders.payment-proof', $order));
        $formResponse
            ->assertRedirect(route('pelanggan.orders.show', $order))
            ->assertSessionHas('error', 'Pesanan sudah dibatalkan karena pembayaran ditolak. Silakan buat pesanan baru.');

        $postResponse = $this->actingAs($customer)->post(route('pelanggan.orders.payment-proof.store', $order), [
            'sender_name' => 'Budi Santoso',
            'transfer_date' => now()->format('Y-m-d'),
            'proof_image' => $this->fakePngUpload(),
        ]);

        $postResponse
            ->assertRedirect(route('pelanggan.orders.show', $order))
            ->assertSessionHas('error', 'Pesanan sudah dibatalkan karena pembayaran ditolak. Silakan buat pesanan baru.');

        $this->assertNull($order->payment->fresh()->proof_image);
    }

    private function makeUser(string $roleName): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['guard_name' => 'web']
        );

        $user = User::factory()->create([
            'phone' => '081111111111',
        ]);

        $user->assignRole($role->name);

        return $user;
    }

    private function makePaymentMethod(): PaymentMethod
    {
        return PaymentMethod::create([
            'name' => 'Transfer Bank BCA',
            'code' => 'bca_test_' . PaymentMethod::count(),
            'account_number' => '1234567890',
            'account_name' => 'CV Sarana Fittindo',
            'bank_name' => 'BCA',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function makeProduct(array $overrides = []): Product
    {
        $category = ProductCategory::create([
            'name' => 'Material',
            'description' => 'Material bangunan',
            'is_active' => true,
        ]);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Besi Hollow',
            'description' => 'Produk pengujian',
            'price' => 150000,
            'stock' => 5,
            'weight' => 1000,
            'is_active' => true,
        ], $overrides));
    }

    private function makeOrder(User $user, PaymentMethod $paymentMethod, Product $product, int $quantity): Order
    {
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'SF' . now()->format('Ymd') . str_pad((string) (Order::count() + 1), 4, '0', STR_PAD_LEFT),
            'status' => 'menunggu_verifikasi_pembayaran',
            'subtotal' => $product->price * $quantity,
            'discount_amount' => 0,
            'shipping_cost' => 0,
            'total_amount' => $product->price * $quantity,
            'payment_method_id' => $paymentMethod->id,
            'shipping_name' => $user->name,
            'shipping_phone' => $user->phone,
            'shipping_address' => 'Jl. Merdeka No. 10',
            'shipping_province' => 'Jawa Barat',
            'shipping_city' => 'Bandung',
            'shipping_district' => 'Coblong',
            'shipping_village' => 'Dago',
            'shipping_postal_code' => '40135',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_price' => $product->price,
            'quantity' => $quantity,
            'subtotal' => $product->price * $quantity,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => $order->total_amount,
            'status' => 'menunggu',
        ]);

        return $order->load('payment');
    }

    private function fakePngUpload(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'proof');
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));

        return new UploadedFile($path, 'bukti.png', 'image/png', null, true);
    }
}
