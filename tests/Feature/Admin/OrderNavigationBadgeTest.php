<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderNavigationBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_menu_shows_paid_unprocessed_order_count(): void
    {
        $admin = $this->makeAdmin();
        $customer = User::factory()->create();

        $this->makeOrder($customer, 'pembayaran_dikonfirmasi');
        $this->makeOrder($customer, 'pembayaran_dikonfirmasi');
        $this->makeOrder($customer, 'diproses');
        $this->makeOrder($customer, 'dikirim');
        $this->makeOrder($customer, 'selesai');
        $this->makeOrder($customer, 'belum_dibayar');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('data-testid="paid-unprocessed-order-count"', false);
        $this->assertMatchesRegularExpression(
            '/data-testid="paid-unprocessed-order-count"[^>]*>\s*2\s*<\/span>/',
            $response->getContent()
        );
    }

    public function test_order_menu_hides_paid_unprocessed_badge_when_count_is_zero(): void
    {
        $admin = $this->makeAdmin();
        $customer = User::factory()->create();

        $this->makeOrder($customer, 'diproses');
        $this->makeOrder($customer, 'dikirim');
        $this->makeOrder($customer, 'selesai');
        $this->makeOrder($customer, 'belum_dibayar');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('data-testid="paid-unprocessed-order-count"', false);
    }

    private function makeAdmin(): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['guard_name' => 'web']
        );

        $admin = User::factory()->create();
        $admin->assignRole($role->name);

        return $admin;
    }

    private function makeOrder(User $customer, string $status): Order
    {
        return Order::create([
            'user_id' => $customer->id,
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'status' => $status,
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
