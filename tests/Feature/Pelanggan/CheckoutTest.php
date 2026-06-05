<?php

namespace Tests\Feature\Pelanggan;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Promotion;
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

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
            'shipping_name' => 'Budi Santoso',
            'shipping_city' => 'Bandung',
            'notes' => 'Kirim pagi.',
            'status' => 'menunggu_konfirmasi_ongkir',
            'shipping_cost_status' => 'waiting_admin',
            'shipping_cost' => 0,
        ]);

        $order = Order::first();
        $response->assertRedirect(route('pelanggan.orders.show', $order));

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => 300000,
            'status' => 'menunggu',
        ]);

        $this->assertSame(1, (int) $product->fresh()->stock);
        $this->assertSame(0, CartItem::count());
        $this->assertSame(1, Payment::count());
    }

    public function test_customer_checkout_to_palembang_gets_fixed_shipping_cost(): void
    {
        $user = $this->makeCustomerWithCart();
        $paymentMethod = PaymentMethod::first();

        $response = $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), [
            'shipping_name' => 'Budi Santoso',
            'shipping_phone' => '081234567890',
            'shipping_address' => 'Jl. Merdeka No. 10',
            'shipping_province' => 'Sumatera Selatan',
            'shipping_city' => 'Kota Palembang',
            'shipping_district' => 'Ilir Timur I',
            'shipping_village' => 'Dago',
            'shipping_postal_code' => '30111',
            'payment_method_id' => $paymentMethod->id,
        ]);

        $order = Order::first();

        $response->assertRedirect(route('pelanggan.orders.show', $order));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'belum_dibayar',
            'shipping_cost_status' => 'fixed',
            'shipping_cost' => 20000,
            'total_amount' => 320000,
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 320000,
            'status' => 'menunggu',
        ]);
    }

    public function test_checkout_applies_active_nominal_promotion(): void
    {
        $user = $this->makeCustomerWithCart();
        $paymentMethod = PaymentMethod::first();
        $promotion = $this->makePromotion([
            'name' => 'Promo Nominal',
            'type' => 'nominal',
            'value' => 50000,
        ]);

        $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), $this->palembangCheckoutPayload($paymentMethod));

        $order = Order::first();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'promotion_id' => $promotion->id,
            'promotion_name' => 'Promo Nominal',
            'promotion_type' => 'nominal',
            'promotion_value' => 50000,
            'discount_amount' => 50000,
            'shipping_cost' => 20000,
            'total_amount' => 270000,
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 270000,
        ]);
    }

    public function test_checkout_applies_active_percent_promotion(): void
    {
        $user = $this->makeCustomerWithCart();
        $paymentMethod = PaymentMethod::first();
        $this->makePromotion([
            'name' => 'Promo Persen',
            'type' => 'percent',
            'value' => 10,
        ]);

        $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), $this->palembangCheckoutPayload($paymentMethod));

        $this->assertDatabaseHas('orders', [
            'discount_amount' => 30000,
            'total_amount' => 290000,
        ]);

        $this->assertDatabaseHas('payments', [
            'amount' => 290000,
        ]);
    }

    public function test_checkout_limits_percent_promotion_by_max_discount(): void
    {
        $user = $this->makeCustomerWithCart();
        $paymentMethod = PaymentMethod::first();
        $this->makePromotion([
            'type' => 'percent',
            'value' => 50,
            'max_discount' => 75000,
        ]);

        $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), $this->palembangCheckoutPayload($paymentMethod));

        $this->assertDatabaseHas('orders', [
            'discount_amount' => 75000,
            'total_amount' => 245000,
        ]);
    }

    public function test_checkout_does_not_apply_promotion_below_min_purchase(): void
    {
        $user = $this->makeCustomerWithCart();
        $paymentMethod = PaymentMethod::first();
        $this->makePromotion([
            'type' => 'nominal',
            'value' => 50000,
            'min_purchase' => 400000,
        ]);

        $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), $this->palembangCheckoutPayload($paymentMethod));

        $this->assertDatabaseHas('orders', [
            'promotion_id' => null,
            'discount_amount' => 0,
            'total_amount' => 320000,
        ]);
    }

    public function test_checkout_ignores_inactive_upcoming_and_expired_promotions(): void
    {
        $user = $this->makeCustomerWithCart();
        $paymentMethod = PaymentMethod::first();

        $this->makePromotion(['name' => 'Inactive', 'value' => 50000, 'is_active' => false]);
        $this->makePromotion(['name' => 'Upcoming', 'value' => 50000, 'start_date' => now()->addDay(), 'end_date' => now()->addDays(3)]);
        $this->makePromotion(['name' => 'Expired', 'value' => 50000, 'start_date' => now()->subDays(3), 'end_date' => now()->subDay()]);

        $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), $this->palembangCheckoutPayload($paymentMethod));

        $this->assertDatabaseHas('orders', [
            'promotion_id' => null,
            'discount_amount' => 0,
            'total_amount' => 320000,
        ]);
    }

    public function test_checkout_uses_promotion_with_largest_discount(): void
    {
        $user = $this->makeCustomerWithCart();
        $paymentMethod = PaymentMethod::first();
        $this->makePromotion(['name' => 'Small Promo', 'type' => 'nominal', 'value' => 25000]);
        $bestPromotion = $this->makePromotion(['name' => 'Best Promo', 'type' => 'percent', 'value' => 20]);

        $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), $this->palembangCheckoutPayload($paymentMethod));

        $this->assertDatabaseHas('orders', [
            'promotion_id' => $bestPromotion->id,
            'promotion_name' => 'Best Promo',
            'discount_amount' => 60000,
            'total_amount' => 260000,
        ]);
    }

    public function test_checkout_outside_palembang_uses_discounted_subtotal_until_shipping_is_confirmed(): void
    {
        $user = $this->makeCustomerWithCart();
        $paymentMethod = PaymentMethod::first();
        $this->makePromotion(['type' => 'nominal', 'value' => 50000]);

        $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), [
            'shipping_name' => 'Budi Santoso',
            'shipping_phone' => '081234567890',
            'shipping_address' => 'Jl. Merdeka No. 10',
            'shipping_province' => 'Jawa Barat',
            'shipping_city' => 'Bandung',
            'shipping_district' => 'Coblong',
            'shipping_village' => 'Dago',
            'shipping_postal_code' => '40135',
            'payment_method_id' => $paymentMethod->id,
        ]);

        $order = Order::first();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'menunggu_konfirmasi_ongkir',
            'shipping_cost_status' => 'waiting_admin',
            'shipping_cost' => 0,
            'discount_amount' => 50000,
            'total_amount' => 250000,
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 250000,
        ]);
    }

    public function test_checkout_reduces_stock_to_zero_and_sets_status_unavailable(): void
    {
        $user = $this->makeCustomerWithCart();
        $paymentMethod = PaymentMethod::first();
        $product = $user->cart()->first()->items()->first()->product;

        $product->update(['stock' => 2]);
        $user->cart()->first()->items()->first()->update(['quantity' => 2]);

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
        ]);

        $order = Order::first();

        $response->assertRedirect(route('pelanggan.orders.show', $order));
        $this->assertSame(0, (int) $product->fresh()->stock);
        $this->assertSame('tidak tersedia', $product->fresh()->status);
    }

    public function test_customer_cannot_checkout_when_cart_quantity_exceeds_stock(): void
    {
        $user = $this->makeCustomerWithCart();
        $paymentMethod = PaymentMethod::first();
        $product = $user->cart()->first()->items()->first()->product;

        $product->update(['stock' => 1]);
        $user->cart()->first()->items()->first()->update(['quantity' => 2]);

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
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(0, Order::count());
        $this->assertSame(0, Payment::count());
    }

    public function test_customer_cannot_add_more_than_stock_to_cart(): void
    {
        $user = $this->makeCustomerWithCart();
        $product = $user->cart()->first()->items()->first()->product;
        $product->update(['stock' => 1]);
        $user->cart()->first()->items()->first()->update(['quantity' => 1]);

        $response = $this->actingAs($user)->post(route('pelanggan.cart.store', $product), [
            'quantity' => 1,
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(1, CartItem::count());
        $this->assertSame(1, CartItem::first()->quantity);
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

        $order = Order::first();

        $response->assertRedirect(route('pelanggan.orders.show', $order));

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
            'status' => 'menunggu_konfirmasi_ongkir',
            'shipping_cost_status' => 'waiting_admin',
        ]);
    }

    public function test_customer_cannot_upload_payment_proof_before_shipping_cost_is_confirmed(): void
    {
        $user = $this->makeCustomerWithCart();
        $paymentMethod = PaymentMethod::first();

        $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), [
            'shipping_name' => 'Budi Santoso',
            'shipping_phone' => '081234567890',
            'shipping_address' => 'Jl. Merdeka No. 10',
            'shipping_province' => 'Jawa Barat',
            'shipping_city' => 'Bandung',
            'shipping_district' => 'Coblong',
            'shipping_village' => 'Dago',
            'shipping_postal_code' => '40135',
            'payment_method_id' => $paymentMethod->id,
        ]);

        $order = Order::first();

        $response = $this->actingAs($user)->get(route('pelanggan.orders.payment-proof', $order));

        $response->assertRedirect(route('pelanggan.orders.show', $order));
        $response->assertSessionHas('error');
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
            'stock' => 3,
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

    private function makePromotion(array $overrides = []): Promotion
    {
        return Promotion::create(array_merge([
            'name' => 'Promo Test',
            'code' => null,
            'type' => 'nominal',
            'value' => 50000,
            'min_purchase' => null,
            'max_discount' => null,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => true,
        ], $overrides));
    }

    private function palembangCheckoutPayload(PaymentMethod $paymentMethod): array
    {
        return [
            'shipping_name' => 'Budi Santoso',
            'shipping_phone' => '081234567890',
            'shipping_address' => 'Jl. Merdeka No. 10',
            'shipping_province' => 'Sumatera Selatan',
            'shipping_city' => 'Kota Palembang',
            'shipping_district' => 'Ilir Timur I',
            'shipping_village' => 'Dago',
            'shipping_postal_code' => '30111',
            'payment_method_id' => $paymentMethod->id,
        ];
    }
}
