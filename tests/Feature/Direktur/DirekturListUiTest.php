<?php

namespace Tests\Feature\Direktur;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Promotion;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirekturListUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_direktur_dashboard_list_sections_use_clean_table_labels(): void
    {
        $direktur = $this->makeUserWithRole('direktur');
        $marketing = $this->makeUserWithRole('marketing', ['name' => 'Marketing Test']);
        $order = $this->makeOrderWithItem();
        $this->makePromotion($marketing);

        $response = $this->actingAs($direktur)->get(route('direktur.dashboard'));

        $response->assertOk();
        $response->assertSee('Top Pelanggan');
        $response->assertSee('Data Pesanan Terbaru');
        $response->assertSee('Produk Terlaris');
        $response->assertSee('Promosi Sedang Berjalan');
        $response->assertSee('No.');
        $response->assertSee($order->order_number);
        $response->assertSee('Promo Direktur');
        $response->assertDontSee('>#<', false);
    }

    public function test_direktur_strategic_report_list_uses_no_label_and_keeps_order_data(): void
    {
        $direktur = $this->makeUserWithRole('direktur');
        $order = $this->makeOrderWithItem();

        $response = $this->actingAs($direktur)->get(route('direktur.reports.strategic'));

        $response->assertOk();
        $response->assertSee('DATA PESANAN');
        $response->assertSee('No.');
        $response->assertSee($order->order_number);
        $response->assertSee('Pelanggan Test');
        $response->assertDontSee('>#<', false);
    }

    private function makeUserWithRole(string $roleName, array $attributes = []): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['guard_name' => 'web']
        );

        $user = User::factory()->create($attributes);
        $user->assignRole($role->name);

        return $user;
    }

    private function makeOrderWithItem(): Order
    {
        $customer = $this->makeUserWithRole('pelanggan', [
            'name' => 'Pelanggan Test',
            'phone' => '08123456789',
        ]);

        $category = ProductCategory::create([
            'name' => 'Kategori Test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Produk Test',
            'description' => 'Produk untuk test list Direktur.',
            'price' => 100000,
            'stock' => 10,
            'weight' => 1000,
            'is_active' => true,
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'order_number' => 'DIR-' . strtoupper(uniqid()),
            'status' => 'selesai',
            'subtotal' => 200000,
            'discount_amount' => 0,
            'shipping_cost' => 0,
            'total_amount' => 200000,
            'shipping_name' => $customer->name,
            'shipping_phone' => '08123456789',
            'shipping_address' => 'Jl. Test',
            'shipping_province' => 'Sumatera Selatan',
            'shipping_city' => 'Palembang',
            'shipping_district' => 'Ilir Timur',
            'shipping_village' => 'Test',
            'shipping_postal_code' => '30111',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_price' => 100000,
            'quantity' => 2,
            'subtotal' => 200000,
        ]);

        return $order;
    }

    private function makePromotion(User $creator): Promotion
    {
        return Promotion::create([
            'name' => 'Promo Direktur',
            'code' => 'DIR10',
            'type' => 'percent',
            'value' => 10,
            'min_purchase' => 100000,
            'max_discount' => 50000,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(7),
            'is_active' => true,
            'created_by' => $creator->id,
        ]);
    }
}
