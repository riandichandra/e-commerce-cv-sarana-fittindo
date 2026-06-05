<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationTablesTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithRole(string $roleName): User
    {
        $role = Role::create(['name' => $roleName, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role->name);

        return $user;
    }

    public function test_admin_users_page_shows_pagination_when_many_users(): void
    {
        $admin = $this->createUserWithRole('admin');

        Role::create(['name' => 'pelanggan', 'guard_name' => 'web']);
        User::factory()->count(15)->create()->each->assignRole('pelanggan');

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('page=2');
    }

    public function test_admin_users_can_filter_by_role(): void
    {
        $admin = $this->createUserWithRole('admin');

        $marketingRole = Role::create(['name' => 'marketing', 'guard_name' => 'web']);
        $pelangganRole = Role::create(['name' => 'pelanggan', 'guard_name' => 'web']);

        $marketingUser = User::factory()->create(['name' => 'Marketing User']);
        $marketingUser->assignRole($marketingRole->name);

        $pelangganUser = User::factory()->create(['name' => 'Pelanggan User']);
        $pelangganUser->assignRole($pelangganRole->name);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['role_id' => $marketingRole->id]));

        $response->assertOk();
        $response->assertSee('Marketing User');
        $response->assertDontSee('Pelanggan User');
    }

    public function test_admin_order_detail_shows_item_pagination(): void
    {
        $admin = $this->createUserWithRole('admin');
        $pelanggan = User::factory()->create();

        $order = $this->createOrder($pelanggan, ['status' => 'belum_dibayar', 'subtotal' => 150000, 'total_amount' => 150000]);

        $category = \App\Models\ProductCategory::create(['name' => 'Test Category', 'slug' => 'test-category', 'is_active' => true]);
        $product = \App\Models\Product::create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'slug' => 'test-product-' . uniqid(),
            'price' => 10000,
            'weight' => 1000,
            'stock' => true,
            'is_active' => true,
        ]);

        $itemsCount = 15;
        for ($i = 0; $i < $itemsCount; $i++) {
            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => "Item #{$i}",
                'product_price' => 10000,
                'quantity' => 1,
                'subtotal' => 10000,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertSee('items_page=2');
    }

    private function createOrder(User $pelanggan, array $overrides = []): Order
    {
        $defaults = [
            'user_id' => $pelanggan->id,
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'status' => 'selesai',
            'subtotal' => 50000,
            'shipping_cost' => 0,
            'total_amount' => 50000,
            'shipping_name' => 'Test',
            'shipping_phone' => '08123456789',
            'shipping_address' => 'Jl. Test',
            'shipping_province' => 'DKI Jakarta',
            'shipping_city' => 'Jakarta',
            'shipping_district' => 'Kebayoran',
        ];

        return Order::create(array_merge($defaults, $overrides));
    }

    private function createOrders(int $count, User $pelanggan): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->createOrder($pelanggan, ['created_at' => now()->subDays($i)]);
        }
    }

    public function test_gm_dashboard_has_pagination_links(): void
    {
        $gm = $this->createUserWithRole('gm');

        $pelanggan = User::factory()->create();
        $this->createOrders(10, $pelanggan);

        $response = $this->actingAs($gm)->get(route('gm.dashboard'));

        $response->assertOk();
        $response->assertSee('recent_orders_page=2');
    }

    public function test_gm_dashboard_retains_year_month_on_pagination(): void
    {
        $gm = $this->createUserWithRole('gm');

        $pelanggan = User::factory()->create();
        $this->createOrders(10, $pelanggan);

        $response = $this->actingAs($gm)->get(route('gm.dashboard', [
            'year' => now()->year,
            'month' => now()->month,
        ]));

        $response->assertOk();
        $response->assertSee('year=' . now()->year);
    }

    public function test_direktur_dashboard_has_pagination_links(): void
    {
        $direktur = $this->createUserWithRole('direktur');

        $pelanggan = User::factory()->create();
        $this->createOrders(10, $pelanggan);

        $response = $this->actingAs($direktur)->get(route('direktur.dashboard'));

        $response->assertOk();
        $response->assertSee('recent_orders_page=2');
    }

    public function test_direktur_dashboard_retains_year_month_on_pagination(): void
    {
        $direktur = $this->createUserWithRole('direktur');

        $pelanggan = User::factory()->create();
        $this->createOrders(10, $pelanggan);

        $response = $this->actingAs($direktur)->get(route('direktur.dashboard', [
            'year' => now()->year,
            'month' => now()->month,
        ]));

        $response->assertOk();
        $response->assertSee('year=' . now()->year);
    }

    public function test_admin_dashboard_has_pagination_on_recent_orders(): void
    {
        $admin = $this->createUserWithRole('admin');

        $pelanggan = User::factory()->create();
        $this->createOrders(10, $pelanggan);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('recent_orders_page=2');
    }
}
