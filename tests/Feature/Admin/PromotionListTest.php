<?php

namespace Tests\Feature\Admin;

use App\Models\Promotion;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionListTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_only_view_promotion_list(): void
    {
        $admin = $this->makeAdmin();
        $marketing = User::factory()->create(['name' => 'Marketing User']);

        Promotion::create([
            'name' => 'Promo Idul Adha',
            'code' => 'ADHA10',
            'description' => 'Promo besar-besaran',
            'type' => 'percent',
            'value' => 10,
            'min_purchase' => 100000,
            'max_discount' => 50000,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(7),
            'is_active' => true,
            'banner_image' => 'promotions/banner.jpg',
            'created_by' => $marketing->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.promotions.index'));

        $response->assertOk();
        $response->assertSee('LIST PROMOSI');
        $response->assertSee('No.');
        $response->assertSee('Promo Idul Adha');
        $response->assertSee('ADHA10');
        $response->assertSee('Marketing User');
        $response->assertDontSee('ADD PROMOTION');
        $response->assertDontSee('EDIT');
        $response->assertDontSee(route('marketing.promotions.create'), false);
    }

    public function test_admin_promotion_create_and_edit_pages_are_not_available(): void
    {
        $admin = $this->makeAdmin();
        $promotion = Promotion::create([
            'name' => 'Promo Read Only',
            'type' => 'nominal',
            'value' => 25000,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => true,
        ]);

        $this->actingAs($admin)->get('/admin/promotions/create')->assertNotFound();
        $this->actingAs($admin)->get("/admin/promotions/{$promotion->id}/edit")->assertNotFound();
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
}
