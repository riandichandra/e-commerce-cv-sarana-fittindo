<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminListUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_list_pages_use_no_label_for_number_column(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeOrder(User::factory()->create());

        $routes = [
            route('admin.users.index'),
            route('admin.products.index'),
            route('admin.categories.index'),
            route('admin.brands.index'),
            route('admin.orders.index'),
            route('admin.orders.show', $order),
            route('admin.pending-shipping-costs.index'),
            route('admin.payments.index'),
            route('admin.payment-methods.index'),
            route('admin.promotions.index'),
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($admin)->get($url);

            $response->assertOk();
            $response->assertSee('No.');
            $response->assertDontSee('>#<', false);
        }
    }

    private function makeAdmin(): User
    {
        $role = Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole($role->name);

        return $admin;
    }

    private function makeOrder(User $customer): Order
    {
        return Order::create([
            'user_id' => $customer->id,
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'status' => 'belum_dibayar',
            'subtotal' => 100000,
            'discount_amount' => 0,
            'shipping_cost' => 0,
            'total_amount' => 100000,
            'shipping_name' => $customer->name,
            'shipping_phone' => '08123456789',
            'shipping_address' => 'Jl. Test',
            'shipping_province' => 'Sumatera Selatan',
            'shipping_city' => 'Palembang',
            'shipping_district' => 'Ilir Timur',
            'shipping_village' => 'Test',
            'shipping_postal_code' => '30111',
        ]);
    }
}
