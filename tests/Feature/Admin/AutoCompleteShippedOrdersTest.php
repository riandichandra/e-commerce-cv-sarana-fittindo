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
use Tests\TestCase;

class AutoCompleteShippedOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_auto_completes_shipped_orders_after_three_days(): void
    {
        $order = $this->makeOrder([
            'status' => 'dikirim',
            'shipped_at' => now()->subDays(3),
        ]);

        $this->artisan('orders:auto-complete-shipped')
            ->expectsOutput('1 pesanan dikirim otomatis diselesaikan.')
            ->assertSuccessful();

        $order->refresh();

        $this->assertSame('selesai', $order->status);
        $this->assertNotNull($order->completed_at);
        $this->assertNotNull($order->auto_completed_at);
        $this->assertSame('system', $order->completion_source);
        $this->assertSame('Otomatis selesai setelah 3 hari sejak dikirim.', $order->completion_notes);
    }

    public function test_command_does_not_complete_recent_or_non_shipped_orders(): void
    {
        $recentOrder = $this->makeOrder([
            'status' => 'dikirim',
            'shipped_at' => now()->subDays(2),
        ]);
        $processingOrder = $this->makeOrder(['status' => 'diproses']);
        $cancelledOrder = $this->makeOrder(['status' => 'dibatalkan']);

        $this->artisan('orders:auto-complete-shipped')
            ->expectsOutput('0 pesanan dikirim otomatis diselesaikan.')
            ->assertSuccessful();

        $this->assertSame('dikirim', $recentOrder->fresh()->status);
        $this->assertSame('diproses', $processingOrder->fresh()->status);
        $this->assertSame('dibatalkan', $cancelledOrder->fresh()->status);
    }

    public function test_command_is_idempotent_and_keeps_customer_completed_orders(): void
    {
        $eligibleOrder = $this->makeOrder([
            'status' => 'dikirim',
            'shipped_at' => now()->subDays(4),
        ]);
        $customerCompletedOrder = $this->makeOrder([
            'status' => 'selesai',
            'shipped_at' => now()->subDays(4),
            'completed_at' => now()->subDay(),
            'completion_source' => 'customer',
            'completion_notes' => 'Diselesaikan manual oleh pelanggan.',
        ]);

        $this->artisan('orders:auto-complete-shipped')->assertSuccessful();
        $firstCompletedAt = $eligibleOrder->fresh()->completed_at;

        $this->artisan('orders:auto-complete-shipped')
            ->expectsOutput('0 pesanan dikirim otomatis diselesaikan.')
            ->assertSuccessful();

        $this->assertTrue($firstCompletedAt->equalTo($eligibleOrder->fresh()->completed_at));
        $this->assertSame('customer', $customerCompletedOrder->fresh()->completion_source);
    }

    private function makeOrder(array $attributes = []): Order
    {
        $customer = $this->makeUser('pelanggan');
        $paymentMethod = $this->makePaymentMethod();
        $product = $this->makeProduct();

        $order = Order::create(array_merge([
            'user_id' => $customer->id,
            'order_number' => 'SF' . now()->format('Ymd') . str_pad((string) (Order::count() + 1), 4, '0', STR_PAD_LEFT),
            'status' => 'dikirim',
            'subtotal' => 150000,
            'discount_amount' => 0,
            'shipping_cost' => 0,
            'total_amount' => 150000,
            'payment_method_id' => $paymentMethod->id,
            'shipping_name' => $customer->name,
            'shipping_phone' => $customer->phone,
            'shipping_address' => 'Jl. Merdeka No. 10',
            'shipping_province' => 'Jawa Barat',
            'shipping_city' => 'Bandung',
            'shipping_district' => 'Coblong',
            'shipping_village' => 'Dago',
            'shipping_postal_code' => '40135',
        ], $attributes));

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
            'status' => 'terverifikasi',
        ]);

        return $order;
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

    private function makeProduct(): Product
    {
        $category = ProductCategory::firstOrCreate(
            ['name' => 'Material'],
            [
                'description' => 'Material bangunan',
                'is_active' => true,
            ]
        );

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Besi Hollow ' . (Product::count() + 1),
            'description' => 'Produk pengujian',
            'price' => 150000,
            'stock' => 1,
            'weight' => 1000,
            'is_active' => true,
        ]);
    }
}
