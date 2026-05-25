<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_can_be_rendered(): void
    {
        $role = Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole($role->name);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Revenue Verified');
        $response->assertSee('Recent Orders');
        $response->assertSee('Payment Health');
    }
}
