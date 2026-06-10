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

    private function createOrderItem(Order $order, string $productName): void
    {
        $category = \App\Models\ProductCategory::firstOrCreate(
            ['name' => 'Pagination Category'],
            ['slug' => 'pagination-category', 'is_active' => true]
        );

        $product = \App\Models\Product::create([
            'category_id' => $category->id,
            'name' => $productName,
            'slug' => 'pagination-product-' . uniqid(),
            'price' => 10000,
            'weight' => 1000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $productName,
            'product_price' => 10000,
            'quantity' => 1,
            'subtotal' => 10000,
        ]);
    }

    public function test_gm_dashboard_has_pagination_links(): void
    {
        $gm = $this->createUserWithRole('gm');

        $pelanggan = User::factory()->create();
        $this->createOrders(10, $pelanggan);

        $response = $this->actingAs($gm)->get(route('gm.dashboard'));

        $response->assertOk();
        $response->assertSee('detail_orders_page=2');
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

    public function test_gm_dashboard_detail_order_pagination_keeps_filters(): void
    {
        $gm = $this->createUserWithRole('gm');

        $pelanggan = User::factory()->create();
        $this->createOrders(10, $pelanggan);

        $response = $this->actingAs($gm)->get(route('gm.dashboard', [
            'card_order_start_date' => now()->startOfMonth()->toDateString(),
            'card_order_end_date' => now()->endOfMonth()->toDateString(),
            'card_order_status' => 'selesai',
        ]));

        $response->assertOk();
        $response->assertSee('detail_orders_page=2');
        $response->assertSee('href="' . route('gm.dashboard', ['detail_orders_page' => 2]) . '"', false);
        $response->assertDontSee('detail_orders_page=2&amp;card_order_status=selesai', false);
        $response->assertDontSee('detail_orders_page=2&amp;card_order_start_date=' . now()->startOfMonth()->toDateString(), false);
    }

    public function test_gm_dashboard_top_pagination_keeps_period_filters(): void
    {
        $gm = $this->createUserWithRole('gm');

        for ($i = 0; $i < 6; $i++) {
            $pelanggan = User::factory()->create();
            $day = str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
            $order = $this->createOrder($pelanggan, [
                'created_at' => '2026-06-' . $day . ' 10:00:00',
                'updated_at' => '2026-06-' . $day . ' 10:00:00',
            ]);

            $this->createOrderItem($order, 'Produk Top ' . $i);
        }

        $response = $this->actingAs($gm)->get(route('gm.dashboard', [
            'top_start_date' => '2026-06-01',
            'top_end_date' => '2026-06-30',
        ]));

        $response->assertOk();
        $response->assertSee('top_customers_page=2');
        $response->assertSee('top_products_page=2');
        $response->assertSee('top_start_date=2026-06-01');
        $response->assertSee('top_end_date=2026-06-30');
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
