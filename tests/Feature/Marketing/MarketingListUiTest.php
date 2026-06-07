<?php

namespace Tests\Feature\Marketing;

use App\Models\Promotion;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingListUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketing_promotion_list_uses_clean_table_labels(): void
    {
        $marketing = $this->makeUserWithRole('marketing', ['name' => 'Marketing Staff']);

        Promotion::create([
            'name' => 'Promo Akhir Pekan',
            'code' => 'WEEKEND',
            'description' => 'Diskon khusus akhir pekan.',
            'type' => 'percent',
            'value' => 15,
            'min_purchase' => 100000,
            'max_discount' => 75000,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(3),
            'is_active' => true,
            'banner_image' => 'promotions/weekend.jpg',
            'created_by' => $marketing->id,
        ]);

        $response = $this->actingAs($marketing)->get(route('marketing.promotions.index'));

        $response->assertOk();
        $response->assertSee('LIST PROMOSI');
        $response->assertSee('No.');
        $response->assertSee('Promo Akhir Pekan');
        $response->assertSee('WEEKEND');
        $response->assertSee('Berjalan');
        $response->assertSee('EDIT');
        $response->assertDontSee('>#<', false);
    }

    public function test_marketing_customer_list_uses_clean_table_labels(): void
    {
        $marketing = $this->makeUserWithRole('marketing');
        $customer = $this->makeUserWithRole('pelanggan', [
            'name' => 'Pelanggan Rapi',
            'phone' => '08123456789',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($marketing)->get(route('marketing.users.index'));

        $response->assertOk();
        $response->assertSee('LIST PELANGGAN');
        $response->assertSee('No.');
        $response->assertSee('Pelanggan Rapi');
        $response->assertSee($customer->email);
        $response->assertSee('Terverifikasi');
        $response->assertDontSee('>#<', false);
    }

    public function test_marketing_dashboard_list_sections_still_render(): void
    {
        $marketing = $this->makeUserWithRole('marketing');
        $this->makeUserWithRole('pelanggan', ['name' => 'Pelanggan Dashboard']);

        Promotion::create([
            'name' => 'Promo Dashboard',
            'type' => 'nominal',
            'value' => 50000,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => true,
            'created_by' => $marketing->id,
        ]);

        $response = $this->actingAs($marketing)->get(route('marketing.dashboard'));

        $response->assertOk();
        $response->assertSee('Promosi Terbaru');
        $response->assertSee('Pelanggan Terbaru');
        $response->assertSee('Promo Dashboard');
        $response->assertSee('Pelanggan Dashboard');
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
