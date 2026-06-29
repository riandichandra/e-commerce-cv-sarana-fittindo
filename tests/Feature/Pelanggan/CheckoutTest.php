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
use App\Services\RajaOngkirService;
use App\Services\ShippingQuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_open_checkout_detail_page_from_cart(): void
    {
        $user = $this->makeCustomerWithCart();
        $this->createAddressForUser($user);

        $response = $this->actingAs($user)->get(route('pelanggan.cart.checkout'));

        $response->assertOk();
        $response->assertSee('Detail Pemesanan');
        $response->assertSee('Detail Pengiriman');
        $response->assertSee('Layanan Pengiriman');
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

    public function test_customer_without_saved_address_is_redirected_from_checkout_page(): void
    {
        $user = $this->makeCustomerWithCart();

        $response = $this->actingAs($user)->get(route('pelanggan.cart.checkout'));

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('error', 'Tambahkan alamat pengiriman di profil sebelum checkout.');
    }

    public function test_customer_cannot_checkout_without_saved_address_id(): void
    {
        $user = $this->makeCustomerWithCart();
        $paymentMethod = PaymentMethod::first();

        $response = $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), [
            'shipping_name' => 'Budi Santoso',
            'shipping_phone' => '081234567890',
            'shipping_address' => 'Jl. Merdeka No. 10',
            'shipping_province_id' => '1',
            'shipping_city_id' => '2',
            'shipping_district_id' => '3',
            'shipping_village_id' => '4',
            'payment_method_id' => $paymentMethod->id,
        ]);

        $response->assertSessionHasErrors('shipping_address_id');
        $this->assertSame(0, Order::count());
    }

    public function test_customer_can_fetch_rajaongkir_shipping_options(): void
    {
        $user = $this->makeCustomerWithCart();
        $address = $this->createAddressForUser($user);
        $this->mockCheckoutRegion(cityName: 'Bandung');

        $response = $this->actingAs($user)->postJson(route('pelanggan.shipping.domestic-cost'), [
            'shipping_address_id' => $address->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.0.courier_code', 'jne');
        $response->assertJsonPath('data.0.service', 'REG');
        $response->assertJsonPath('data.0.cost', 45000);
        $response->assertJsonPath('data.0.weight_gram', 2000);
        $this->assertNotEmpty($response->json('data.0.quote_token'));
    }

    public function test_shipping_quote_endpoint_requires_saved_address(): void
    {
        $user = $this->makeCustomerWithCart();
        $this->mockCheckoutRegion(cityName: 'Bandung');

        $response = $this->actingAs($user)->postJson(route('pelanggan.shipping.domestic-cost'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('shipping_address_id');
    }

    public function test_shipping_quote_endpoint_requires_origin_configuration(): void
    {
        $user = $this->makeCustomerWithCart();
        $address = $this->createAddressForUser($user);

        $this->mock(RajaOngkirService::class, function ($mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturn(true);
            $mock->shouldReceive('canCalculateShipping')->once()->andReturn(false);
        });

        $response = $this->actingAs($user)->postJson(route('pelanggan.shipping.domestic-cost'), [
            'shipping_address_id' => $address->id,
        ]);

        $response->assertStatus(503);
        $response->assertJsonPath('message', 'Origin pengiriman RajaOngkir belum dikonfigurasi.');
    }

    public function test_customer_can_checkout_with_shipping_detail_and_payment_method(): void
    {
        $user = $this->makeCustomerWithCart();
        $paymentMethod = PaymentMethod::first();
        $product = $user->cart()->first()->items()->first()->product;
        $this->mockCheckoutRegion(cityName: 'Bandung');

        $response = $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), $this->savedAddressCheckoutPayload($paymentMethod, [
            'shipping_province' => 'Provinsi dari browser',
            'shipping_city' => 'Kota dari browser',
            'shipping_district' => 'Kecamatan dari browser',
            'shipping_village' => 'Desa dari browser',
            'notes' => 'Kirim pagi.',
        ]));

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
            'shipping_name' => 'Budi Santoso',
            'shipping_province' => 'DKI Jakarta',
            'shipping_city' => 'Jakarta Selatan',
            'shipping_district' => 'Kebayoran Baru',
            'shipping_village' => 'Senayan',
            'shipping_postal_code' => '12190',
            'notes' => 'Kirim pagi.',
            'status' => 'belum_dibayar',
            'shipping_cost_status' => 'calculated',
            'shipping_cost_source' => 'rajaongkir',
            'shipping_cost' => 45000,
            'shipping_courier_code' => 'jne',
            'shipping_service' => 'REG',
            'shipping_weight_gram' => 2000,
            'total_amount' => 345000,
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
            'amount' => 345000,
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
        $this->mockPalembangCheckoutRegion();

        $response = $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), $this->savedAddressCheckoutPayload($paymentMethod, [
            'shipping_postal_code' => '30111',
            '_shipping_cost' => 20000,
        ]));

        $order = Order::first();

        $response->assertRedirect(route('pelanggan.orders.show', $order));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'belum_dibayar',
            'shipping_cost_status' => 'calculated',
            'shipping_cost_source' => 'rajaongkir',
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

        $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), $this->palembangSavedAddressCheckoutPayload($paymentMethod));

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

        $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), $this->palembangSavedAddressCheckoutPayload($paymentMethod));

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

        $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), $this->palembangSavedAddressCheckoutPayload($paymentMethod));

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

        $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), $this->palembangSavedAddressCheckoutPayload($paymentMethod));

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

        $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), $this->palembangSavedAddressCheckoutPayload($paymentMethod));

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

        $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), $this->palembangSavedAddressCheckoutPayload($paymentMethod));

        $this->assertDatabaseHas('orders', [
            'promotion_id' => $bestPromotion->id,
            'promotion_name' => 'Best Promo',
            'discount_amount' => 60000,
            'total_amount' => 260000,
        ]);
    }

    public function test_checkout_outside_palembang_uses_rajaongkir_shipping_quote_in_total(): void
    {
        $user = $this->makeCustomerWithCart();
        $paymentMethod = PaymentMethod::first();
        $this->makePromotion(['type' => 'nominal', 'value' => 50000]);
        $this->mockCheckoutRegion(cityName: 'Bandung');

        $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), $this->savedAddressCheckoutPayload($paymentMethod));

        $order = Order::first();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'belum_dibayar',
            'shipping_cost_status' => 'calculated',
            'shipping_cost_source' => 'rajaongkir',
            'shipping_cost' => 45000,
            'discount_amount' => 50000,
            'total_amount' => 295000,
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 295000,
        ]);
    }

    public function test_checkout_reduces_stock_to_zero_and_sets_status_unavailable(): void
    {
        $user = $this->makeCustomerWithCart();
        $paymentMethod = PaymentMethod::first();
        $product = $user->cart()->first()->items()->first()->product;

        $product->update(['stock' => 2]);
        $user->cart()->first()->items()->first()->update(['quantity' => 2]);
        $this->mockCheckoutRegion(cityName: 'Bandung');

        $response = $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), $this->savedAddressCheckoutPayload($paymentMethod));

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
        $this->mockCheckoutRegion(cityName: 'Bandung');

        $response = $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), $this->savedAddressCheckoutPayload($paymentMethod));

        $response->assertSessionHas('error');
        $this->assertSame(0, Order::count());
        $this->assertSame(0, Payment::count());
    }

    public function test_customer_cannot_checkout_when_product_category_is_inactive(): void
    {
        $user = $this->makeCustomerWithCart();
        $paymentMethod = PaymentMethod::first();
        $product = $user->cart()->first()->items()->first()->product;
        $product->category->update(['is_active' => false]);
        $this->mockCheckoutRegion(cityName: 'Bandung');

        $response = $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), $this->savedAddressCheckoutPayload($paymentMethod));

        $response->assertSessionHas('error', 'Produk Besi Hollow sedang tidak tersedia.');
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
            'shipping_quote_token' => $this->shippingQuoteToken($user, destinationDistrictId: (string) $address->district_id),
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
            'status' => 'belum_dibayar',
            'shipping_cost_status' => 'calculated',
            'shipping_cost' => 45000,
        ]);
    }

    public function test_customer_cannot_upload_payment_proof_before_shipping_cost_is_confirmed(): void
    {
        $user = $this->makeCustomerWithCart();
        $paymentMethod = PaymentMethod::first();
        $this->mockCheckoutRegion(cityName: 'Bandung');

        $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), $this->savedAddressCheckoutPayload($paymentMethod, [
            'shipping_fallback' => 'admin_manual',
            'shipping_quote_token' => null,
        ]));

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

    public function test_customer_cannot_checkout_when_saved_address_region_is_invalid(): void
    {
        $user = $this->makeCustomerWithCart();
        $paymentMethod = PaymentMethod::first();
        $address = $this->createAddressForUser($user);
        $address->update([
            'region_source' => 'legacy',
        ]);

        $response = $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), [
            'shipping_address_id' => $address->id,
            'payment_method_id' => $paymentMethod->id,
            'shipping_quote_token' => 'invalid-address-token',
        ]);

        $response->assertSessionHasErrors('shipping_quote_token');
        $this->assertSame(0, Order::count());
    }

    public function test_customer_cannot_fetch_shipping_options_when_raja_ongkir_key_is_missing(): void
    {
        $user = $this->makeCustomerWithCart();
        $address = $this->createAddressForUser($user);
        $this->mockCheckoutRegion(configured: false);

        $response = $this->actingAs($user)->postJson(route('pelanggan.shipping.domestic-cost'), [
            'shipping_address_id' => $address->id,
        ]);

        $response->assertStatus(503);
        $this->assertSame(0, Order::count());
    }

    public function test_customer_cannot_checkout_with_invalid_shipping_quote_token(): void
    {
        $user = $this->makeCustomerWithCart();
        $paymentMethod = PaymentMethod::first();
        $this->mockCheckoutRegion(cityName: 'Bandung');

        $response = $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), $this->savedAddressCheckoutPayload($paymentMethod, [
            'shipping_quote_token' => 'invalid-token',
        ]));

        $response->assertSessionHasErrors('shipping_quote_token');
        $this->assertSame(0, Order::count());
    }

    public function test_customer_cannot_checkout_when_cart_weight_changed_after_shipping_quote(): void
    {
        $user = $this->makeCustomerWithCart();
        $paymentMethod = PaymentMethod::first();
        $this->mockCheckoutRegion(cityName: 'Bandung');
        $payload = $this->savedAddressCheckoutPayload($paymentMethod);

        $user->cart()->first()->items()->first()->update(['quantity' => 1]);

        $response = $this->actingAs($user)->post(route('pelanggan.cart.checkout.process'), $payload);

        $response->assertSessionHasErrors('shipping_quote_token');
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
        return $user->addresses()->create([
            'label' => 'Rumah',
            'receiver_name' => 'Budi Santoso',
            'receiver_phone' => '081234567890',
            'full_address' => 'Jl. Merdeka No. 10',
            'province_id' => '1',
            'regency_id' => '2',
            'district_id' => '3',
            'village_id' => '4',
            'province_name' => 'DKI Jakarta',
            'city_name' => 'Jakarta Selatan',
            'district_name' => 'Kebayoran Baru',
            'village_name' => 'Senayan',
            'region_source' => 'rajaongkir',
            'postal_code' => '12190',
            'is_main' => true,
        ]);
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

    private function palembangSavedAddressCheckoutPayload(PaymentMethod $paymentMethod): array
    {
        $this->mockPalembangCheckoutRegion();

        return $this->savedAddressCheckoutPayload($paymentMethod, [
            'shipping_postal_code' => '30111',
            '_shipping_cost' => 20000,
        ]);
    }

    private function savedAddressCheckoutPayload(PaymentMethod $paymentMethod, array $overrides = []): array
    {
        $cart = Cart::with(['user', 'items.product'])->first();
        $user = $cart->user;
        $address = $user->addresses()->first() ?? $this->createAddressForUser($user);

        $payload = array_merge([
            'shipping_address_id' => $address->id,
            'payment_method_id' => $paymentMethod->id,
        ], $overrides);

        $shippingCost = (int) ($payload['_shipping_cost'] ?? 45000);
        unset($payload['_shipping_cost']);

        if (($payload['shipping_fallback'] ?? null) !== 'admin_manual' && empty($payload['shipping_quote_token'])) {
            $payload['shipping_quote_token'] = $this->shippingQuoteToken(
                user: $user,
                cart: $cart,
                destinationDistrictId: (string) $address->district_id,
                cost: $shippingCost
            );
        }

        return $payload;
    }

    private function mockPalembangCheckoutRegion(): void
    {
        $this->mockCheckoutRegion(
            provinceName: 'Sumatera Selatan',
            cityName: 'Kota Palembang',
            districtName: 'Ilir Timur I',
            postalCode: '30111',
        );
    }

    private function mockCheckoutRegion(
        string $provinceId = '1',
        string $provinceName = 'Jawa Barat',
        string $cityId = '2',
        string $cityName = 'Bandung',
        string $districtId = '3',
        string $districtName = 'Coblong',
        string $villageId = '4',
        string $villageName = 'Dago',
        ?string $postalCode = '40135',
        bool $configured = true,
        ?array $resolvedRegions = null,
    ): void {
        $this->mock(RajaOngkirService::class, function ($mock) use (
            $provinceId,
            $provinceName,
            $cityId,
            $cityName,
            $districtId,
            $districtName,
            $villageId,
            $villageName,
            $postalCode,
            $configured,
            $resolvedRegions,
        ): void {
            $mock->shouldReceive('isConfigured')->zeroOrMoreTimes()->andReturn($configured);
            $mock->shouldReceive('canCalculateShipping')->zeroOrMoreTimes()->andReturn($configured);
            $mock->shouldReceive('originDistrictId')->zeroOrMoreTimes()->andReturn('1391');
            $mock->shouldReceive('originLabel')->zeroOrMoreTimes()->andReturn('Gudang utama');
            $mock->shouldReceive('shippingQuoteTtl')->zeroOrMoreTimes()->andReturn(600);
            $mock->shouldReceive('defaultCouriers')->zeroOrMoreTimes()->andReturn('jne:sicepat:jnt:tiki:pos');

            if (! $configured) {
                return;
            }

            $mock->shouldReceive('resolveRegionChain')
                ->zeroOrMoreTimes()
                ->with($provinceId, $cityId, $districtId, $villageId)
                ->andReturn($resolvedRegions ?? [
                    'province' => ['id' => $provinceId, 'name' => $provinceName, 'postal_code' => null],
                    'city' => ['id' => $cityId, 'name' => $cityName, 'postal_code' => null],
                    'district' => ['id' => $districtId, 'name' => $districtName, 'postal_code' => null],
                    'subdistrict' => ['id' => $villageId, 'name' => $villageName, 'postal_code' => $postalCode],
                ]);

            $mock->shouldReceive('calculateDistrictDomesticCost')
                ->zeroOrMoreTimes()
                ->with('1391', $districtId, 2000)
                ->andReturn([
                    [
                        'courier_name' => 'Jalur Nugraha Ekakurir (JNE)',
                        'courier_code' => 'jne',
                        'service' => 'REG',
                        'description' => 'Regular Service',
                        'cost' => 45000,
                        'etd' => '1-2 day',
                    ],
                ]);
        });
    }

    private function shippingQuoteToken(
        ?User $user = null,
        ?Cart $cart = null,
        string $originDistrictId = '1391',
        string $destinationDistrictId = '3',
        int $cost = 45000,
    ): string {
        $cart ??= Cart::with(['user', 'items.product'])->first();
        $user ??= $cart->user;

        return app(ShippingQuoteService::class)->storeQuote($user, $cart, [
            'courier_name' => 'Jalur Nugraha Ekakurir (JNE)',
            'courier_code' => 'jne',
            'service' => 'REG',
            'description' => 'Regular Service',
            'cost' => $cost,
            'etd' => '1-2 day',
        ], $originDistrictId, $destinationDistrictId, app(ShippingQuoteService::class)->weightForCart($cart));
    }
}
