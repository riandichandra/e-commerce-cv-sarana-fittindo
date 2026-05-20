<?php

namespace Tests\Feature\Pelanggan;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_open_checkout_detail_page_from_cart(): void
    {
        $user = $this->makeCustomerWithCart();

        $response = $this->actingAs($user)->get(route('pelanggan.cart.checkout'));

        $response->assertOk();
        $response->assertSee('Detail Pemesanan');
        $response->assertSee('Detail Pengiriman');
        $response->assertSee('Metode Pembayaran');
        $response->assertSee('Produk Dipesan');
    }

    public function test_customer_can_checkout_with_shipping_detail_and_payment_method(): void
    {
        $user = $this->makeCustomerWithCart();
        $paymentMethod = PaymentMethod::first();
        $product = Product::first();

        $response = $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), [
            'shipping_name' => 'Budi Santoso',
            'shipping_phone' => '081234567890',
            'shipping_address' => 'Jl. Merdeka No. 10',
            'shipping_province' => 'Jawa Barat',
            'shipping_city' => 'Bandung',
            'shipping_district' => 'Coblong',
            'shipping_village' => 'Dago',
            'shipping_postal_code' => '40135',
            'payment_method_id' => $paymentMethod->id,
            'notes' => 'Kirim pagi.',
        ]);

        $response->assertRedirect(route('pelanggan.products.index'));

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
            'shipping_name' => 'Budi Santoso',
            'shipping_city' => 'Bandung',
            'notes' => 'Kirim pagi.',
        ]);

        $order = Order::first();

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'payment_method_id' => $paymentMethod->id,
            'status' => 'pending',
        ]);

        $this->assertSame(0, (int) $product->fresh()->stock);
        $this->assertSame(0, CartItem::count());
        $this->assertSame(1, Payment::count());
    }

    private function makeCustomerWithCart(): User
    {
        $role = Role::create([
            'name' => 'pelanggan',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'phone' => '081111111111',
        ]);

        $category = ProductCategory::create([
            'name' => 'Material',
            'description' => 'Material bangunan',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Besi Hollow',
            'description' => 'Produk pengujian',
            'price' => 150000,
            'stock' => 1,
            'weight' => 1000,
            'is_active' => true,
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        PaymentMethod::create([
            'name' => 'Transfer Bank BCA',
            'code' => 'bca_test',
            'account_number' => '1234567890',
            'account_name' => 'CV Sarana Fittindo',
            'bank_name' => 'BCA',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return $user;
    }
}
