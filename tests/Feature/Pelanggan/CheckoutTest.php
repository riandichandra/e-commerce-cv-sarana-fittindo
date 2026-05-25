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
use Illuminate\Support\Facades\DB;
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

    public function test_checkout_page_shows_saved_customer_addresses(): void
    {
        $user = $this->makeCustomerWithCart();
        $address = $this->createAddressForUser($user);

        $response = $this->actingAs($user)->get(route('pelanggan.cart.checkout'));

        $response->assertOk();
        $response->assertSee('Pilih alamat tersimpan');
        $response->assertSee($address->label);
        $response->assertSee($address->full_address);
    }

    public function test_customer_can_checkout_with_shipping_detail_and_payment_method(): void
    {
        $user = $this->makeCustomerWithCart();
        $paymentMethod = PaymentMethod::first();
        $product = $user->cart()->first()->items()->first()->product;

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

    public function test_customer_can_checkout_using_saved_address(): void
    {
        $user = $this->makeCustomerWithCart();
        $address = $this->createAddressForUser($user);
        $paymentMethod = PaymentMethod::first();

        $response = $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), [
            'shipping_address_id' => $address->id,
            'payment_method_id' => $paymentMethod->id,
        ]);

        $response->assertRedirect(route('pelanggan.products.index'));

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'shipping_name' => $address->receiver_name,
            'shipping_phone' => $address->receiver_phone,
            'shipping_address' => $address->full_address,
            'shipping_province' => 'DKI Jakarta',
            'shipping_city' => 'Jakarta Selatan',
            'shipping_district' => 'Kebayoran Baru',
            'shipping_village' => 'Senayan',
            'shipping_postal_code' => $address->postal_code,
        ]);
    }

    public function test_customer_cannot_checkout_using_another_users_saved_address(): void
    {
        $user = $this->makeCustomerWithCart();
        $otherUser = User::factory()->create();
        $address = $this->createAddressForUser($otherUser);
        $paymentMethod = PaymentMethod::first();

        $response = $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), [
            'shipping_address_id' => $address->id,
            'payment_method_id' => $paymentMethod->id,
        ]);

        $response->assertSessionHasErrors('shipping_address_id');
        $this->assertSame(0, Order::count());
    }

    private function makeCustomerWithCart(): User
    {
        $role = Role::create([
            'name' => 'pelanggan',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create([
            'phone' => '081111111111',
        ]);

        $user->assignRole($role->name);

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

    private function createAddressForUser(User $user)
    {
        $this->createLocation();

        return $user->addresses()->create([
            'label' => 'Rumah',
            'receiver_name' => 'Budi Santoso',
            'receiver_phone' => '081234567890',
            'full_address' => 'Jl. Merdeka No. 10',
            'province_id' => 1,
            'regency_id' => 1,
            'district_id' => 1,
            'village_id' => 1,
            'postal_code' => '12190',
            'is_main' => true,
        ]);
    }

    private function createLocation(): void
    {
        DB::table('provinces')->insertOrIgnore(['id' => 1, 'name' => 'DKI Jakarta']);
        DB::table('regencies')->insertOrIgnore(['id' => 1, 'province_id' => 1, 'name' => 'Jakarta Selatan']);
        DB::table('districts')->insertOrIgnore(['id' => 1, 'regency_id' => 1, 'name' => 'Kebayoran Baru']);
        DB::table('villages')->insertOrIgnore(['id' => 1, 'district_id' => 1, 'name' => 'Senayan']);
    }
}
