<?php

namespace Tests\Feature\Marketing;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserListTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketing_can_view_customer_list(): void
    {
        $marketing = $this->makeUserWithRole('marketing');
        $customer = $this->makeUserWithRole('pelanggan', ['name' => 'Pelanggan Test']);

        $response = $this->actingAs($marketing)->get(route('marketing.users.index'));

        $response->assertOk();
        $response->assertSee('No.');
        $response->assertSee('Pelanggan Test');
        $response->assertSee($customer->email);
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
}
