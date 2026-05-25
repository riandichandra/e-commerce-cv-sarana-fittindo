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

class OrderFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_and_filter_order_history(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->makeUser('pelanggan', ['name' => 'Budi Customer', 'phone' => '08123456789']);
        $otherCustomer = $this->makeUser('pelanggan', ['name' => 'Sari Customer', 'phone' => '08999999999']);
        $paymentMethod = $this->makePaymentMethod();
        $product = $this->makeProduct();

        $matchedOrder = $this->makeOrder($customer, $paymentMethod, $product, 'verified', 'completed');
        $otherOrder = $this->makeOrder($otherCustomer, $paymentMethod, $product, 'pending', 'pending_payment');

        $response = $this->actingAs($admin)->get(route('admin.orders.index', [
            'search' => 'Budi',
            'status' => 'completed',
            'payment_status' => 'verified',
            'delivery_status' => 'none',
        ]));

        $response->assertOk();
        $response->assertSee('CARI & FILTER RIWAYAT PEMESANAN', false);
        $response->assertSee($matchedOrder->order_number);
        $response->assertDontSee($otherOrder->order_number);
    }

    private function makeUser(string $roleName, array $attributes = []): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['guard_name' => 'web']
        );

        $user = User::factory()->create(array_merge([
            'phone' => '081111111111',
        ], $attributes));

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

    private function makeOrder(User $user, PaymentMethod $paymentMethod, Product $product, string $paymentStatus, string $orderStatus): Order
    {
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'SF' . now()->format('Ymd') . str_pad((string) (Order::count() + 1), 4, '0', STR_PAD_LEFT),
            'status' => $orderStatus,
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
}
