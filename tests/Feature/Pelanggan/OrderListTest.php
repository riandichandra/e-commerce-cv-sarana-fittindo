<?php

namespace Tests\Feature\Pelanggan;

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

class OrderListTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_see_orders_grouped_by_payment_status(): void
    {
        $customer = $this->makeCustomer();
        $otherCustomer = $this->makeCustomer();
        $paymentMethod = $this->makePaymentMethod();
        $product = $this->makeProduct();

        $unpaidOrder = $this->makeOrder($customer, $paymentMethod, $product, 'pending');
        $paidOrder = $this->makeOrder($customer, $paymentMethod, $product, 'verified');
        $otherOrder = $this->makeOrder($otherCustomer, $paymentMethod, $product, 'pending');

        $response = $this->actingAs($customer)->get(route('pelanggan.orders.index'));

        $response->assertOk();
        $response->assertSee('Belum Dibayar');
        $response->assertSee('Sudah Dibayar');
        $response->assertSee($unpaidOrder->order_number);
        $response->assertSee($paidOrder->order_number);
        $response->assertDontSee($otherOrder->order_number);
    }

    public function test_customer_can_upload_payment_proof_for_unpaid_order(): void
    {
        Storage::fake('public');

        $customer = $this->makeCustomer();
        $paymentMethod = $this->makePaymentMethod();
        $product = $this->makeProduct();
        $order = $this->makeOrder($customer, $paymentMethod, $product, 'pending');

        $formResponse = $this->actingAs($customer)->get(route('pelanggan.orders.payment-proof', $order));
        $formResponse->assertOk();
        $formResponse->assertSee('Upload Bukti Pembayaran');

        $response = $this->actingAs($customer)->post(route('pelanggan.orders.payment-proof.store', $order), [
            'sender_name' => 'Budi Santoso',
            'transfer_date' => now()->format('Y-m-d'),
            'proof_image' => $this->fakePngUpload(),
            'notes' => 'Transfer dari BCA.',
        ]);

        $response->assertRedirect(route('pelanggan.orders.index'));

        $payment = $order->payment()->first();

        $this->assertSame('Budi Santoso', $payment->sender_name);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('Transfer dari BCA.', $payment->notes);
        $this->assertSame('waiting_payment_confirmation', $order->fresh()->status);
        Storage::disk('public')->assertExists($payment->proof_image);
    }

    public function test_customer_cannot_upload_payment_proof_for_verified_order(): void
    {
        Storage::fake('public');

        $customer = $this->makeCustomer();
        $paymentMethod = $this->makePaymentMethod();
        $product = $this->makeProduct();
        $order = $this->makeOrder($customer, $paymentMethod, $product, 'verified');

        $response = $this->actingAs($customer)->post(route('pelanggan.orders.payment-proof.store', $order), [
            'sender_name' => 'Budi Santoso',
            'transfer_date' => now()->format('Y-m-d'),
            'proof_image' => $this->fakePngUpload(),
        ]);

        $response->assertRedirect(route('pelanggan.orders.index'));
        $this->assertNull($order->payment()->first()->proof_image);
    }

    private function makeCustomer(): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'pelanggan'],
            ['guard_name' => 'web']
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'phone' => '081111111111',
        ]);
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

    private function makeProduct(): Product
    {
        $category = ProductCategory::create([
            'name' => 'Material',
            'description' => 'Material bangunan',
            'is_active' => true,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Besi Hollow',
            'description' => 'Produk pengujian',
            'price' => 150000,
            'stock' => 1,
            'weight' => 1000,
            'is_active' => true,
        ]);
    }

    private function makeOrder(User $user, PaymentMethod $paymentMethod, Product $product, string $paymentStatus): Order
    {
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'SF' . now()->format('Ymd') . str_pad((string) (Order::count() + 1), 4, '0', STR_PAD_LEFT),
            'status' => $paymentStatus === 'verified' ? 'payment_confirmed' : 'pending_payment',
            'subtotal' => 150000,
            'discount_amount' => 0,
            'shipping_cost' => 0,
            'total_amount' => 150000,
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
            'quantity' => 1,
            'subtotal' => $product->price,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => $order->total_amount,
            'status' => $paymentStatus,
        ]);

        return $order;
    }

    private function fakePngUpload(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'proof');
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));

        return new UploadedFile($path, 'bukti.png', 'image/png', null, true);
    }
}
