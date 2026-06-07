<?php

namespace Tests\Feature\GM;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GmListUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_gm_dashboard_list_sections_use_clean_table_labels(): void
    {
        $gm = $this->makeUserWithRole('gm');
        $order = $this->makeOrderWithItem();

        $response = $this->actingAs($gm)->get(route('gm.dashboard'));

        $response->assertOk();
        $response->assertSee('Top Pelanggan');
        $response->assertSee('Pesanan Terbaru');
        $response->assertSee('Produk Teratas');
        $response->assertSee('No.');
        $response->assertSee($order->order_number);
        $response->assertSee('Produk Test');
        $response->assertDontSee('>#<', false);
    }

    public function test_gm_report_list_uses_no_label_and_keeps_order_data(): void
    {
        $gm = $this->makeUserWithRole('gm');
        $order = $this->makeOrderWithItem();

        $response = $this->actingAs($gm)->get(route('gm.reports.index'));

        $response->assertOk();
        $response->assertSee('LAPORAN ORDER');
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
            'description' => 'Produk untuk test list GM.',
            'price' => 100000,
            'stock' => 10,
            'weight' => 1000,
            'is_active' => true,
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'order_number' => 'GM-' . strtoupper(uniqid()),
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
}
