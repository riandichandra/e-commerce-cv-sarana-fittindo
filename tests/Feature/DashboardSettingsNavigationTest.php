<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardSettingsNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function createRole(string $name): Role
    {
        return Role::create([
            'name' => $name,
            'guard_name' => 'web',
        ]);
    }

    private function createUserWithRole(string $roleName): User
    {
        $role = $this->createRole($roleName);
        $user = User::factory()->create();
        $user->assignRole($role->name);

        return $user;
    }

    public function test_marketing_can_open_settings_page(): void
    {
        $user = $this->createUserWithRole('marketing');

        $response = $this->actingAs($user)->get(route('marketing.settings.index'));

        $response->assertOk();
        $response->assertSee('Pengaturan');
        $response->assertSee('UPDATE PROFILE');
        $response->assertSee('UBAH KATA SANDI');
    }

    public function test_gm_can_open_settings_page(): void
    {
        $user = $this->createUserWithRole('gm');

        $response = $this->actingAs($user)->get(route('gm.settings.index'));

        $response->assertOk();
        $response->assertSee('Pengaturan');
        $response->assertSee('UPDATE PROFILE');
        $response->assertSee('UBAH KATA SANDI');
    }

    public function test_direktur_can_open_settings_page(): void
    {
        $user = $this->createUserWithRole('direktur');

        $response = $this->actingAs($user)->get(route('direktur.settings.index'));

        $response->assertOk();
        $response->assertSee('Pengaturan');
        $response->assertSee('UPDATE PROFILE');
        $response->assertSee('UBAH KATA SANDI');
    }

    public function test_marketing_cannot_access_admin_settings(): void
    {
        $user = $this->createUserWithRole('marketing');

        $response = $this->actingAs($user)->get(route('admin.settings.index'));

        $response->assertForbidden();
    }

    public function test_gm_cannot_access_admin_settings(): void
    {
        $user = $this->createUserWithRole('gm');

        $response = $this->actingAs($user)->get(route('admin.settings.index'));

        $response->assertForbidden();
    }

    public function test_direktur_cannot_access_admin_settings(): void
    {
        $user = $this->createUserWithRole('direktur');

        $response = $this->actingAs($user)->get(route('admin.settings.index'));

        $response->assertForbidden();
    }

    public function test_profile_update_from_marketing_settings_redirects_back(): void
    {
        $user = $this->createUserWithRole('marketing');

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Updated Name',
            'email' => $user->email,
            'redirect_to' => route('marketing.settings.index'),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('marketing.settings.index'));
        $response->assertSessionHas('status', 'profile-updated');

        $this->assertSame('Updated Name', $user->fresh()->name);
    }

    public function test_profile_update_from_gm_settings_redirects_back(): void
    {
        $user = $this->createUserWithRole('gm');

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Updated Name',
            'email' => $user->email,
            'redirect_to' => route('gm.settings.index'),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('gm.settings.index'));
        $response->assertSessionHas('status', 'profile-updated');

        $this->assertSame('Updated Name', $user->fresh()->name);
    }

    public function test_profile_update_from_direktur_settings_redirects_back(): void
    {
        $user = $this->createUserWithRole('direktur');

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Updated Name',
            'email' => $user->email,
            'redirect_to' => route('direktur.settings.index'),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('direktur.settings.index'));
        $response->assertSessionHas('status', 'profile-updated');

        $this->assertSame('Updated Name', $user->fresh()->name);
    }

    public function test_profile_update_with_unknown_redirect_falls_back_to_profile(): void
    {
        $user = $this->createUserWithRole('marketing');

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Updated Name',
            'email' => $user->email,
            'redirect_to' => 'https://malicious-site.com',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('profile.edit'));
    }
}
