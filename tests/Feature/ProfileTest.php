<?php

namespace Tests\Feature;

use App\Services\RajaOngkirService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_user_can_create_update_and_delete_addresses(): void
    {
        $user = User::factory()->create();
        $location = $this->createLocation();
        $this->mockRajaOngkirRegions();

        $createResponse = $this
            ->actingAs($user)
            ->post('/profile/addresses', [
                'label' => 'Rumah',
                'receiver_name' => 'Budi',
                'receiver_phone' => '08123456789',
                'full_address' => 'Jalan Mawar No. 10',
                'province_id' => $location['province_id'],
                'regency_id' => $location['regency_id'],
                'district_id' => $location['district_id'],
                'village_id' => $location['village_id'],
                'postal_code' => '12345',
            ]);

        $createResponse
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $address = $user->addresses()->first();

        $this->assertNotNull($address);
        $this->assertTrue($address->is_main);
        $this->assertSame('DKI Jakarta', $address->province_name);
        $this->assertSame('Jakarta Selatan', $address->city_name);
        $this->assertSame('Kebayoran Baru', $address->district_name);
        $this->assertSame('Senayan', $address->village_name);
        $this->assertSame('rajaongkir', $address->region_source);

        $updateResponse = $this
            ->actingAs($user)
            ->patch("/profile/addresses/{$address->id}", [
                'label' => 'Kantor',
                'receiver_name' => 'Budi Santoso',
                'receiver_phone' => '08123456780',
                'full_address' => 'Jalan Melati No. 20',
                'province_id' => $location['province_id'],
                'regency_id' => $location['regency_id'],
                'district_id' => $location['district_id'],
                'village_id' => $location['village_id'],
                'postal_code' => '54321',
                'is_main' => '1',
            ]);

        $updateResponse
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame('Kantor', $address->refresh()->label);

        $deleteResponse = $this
            ->actingAs($user)
            ->delete("/profile/addresses/{$address->id}");

        $deleteResponse
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertDatabaseMissing('user_addresses', [
            'id' => $address->id,
        ]);
    }

    public function test_profile_address_section_renders_custom_delete_notification_ui(): void
    {
        $user = $this->makeCustomer();
        $location = $this->createLocation();

        $user->addresses()->create([
            'label' => 'Rumah',
            'receiver_name' => 'Budi',
            'receiver_phone' => '08123456789',
            'full_address' => 'Jalan Mawar No. 10',
            'province_id' => $location['province_id'],
            'regency_id' => $location['regency_id'],
            'district_id' => $location['district_id'],
            'village_id' => $location['village_id'],
            'postal_code' => '12345',
            'is_main' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['status' => 'address-deleted'])
            ->get('/profile');

        $response
            ->assertOk()
            ->assertSee('Alamat berhasil dihapus')
            ->assertSee('Hapus alamat ini?')
            ->assertSee('Ya, Hapus Alamat')
            ->assertSee('Riwayat pesanan yang sudah dibuat tidak akan berubah.');
    }

    public function test_user_cannot_update_another_users_address(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $location = $this->createLocation();
        $address = $otherUser->addresses()->create([
            'label' => 'Rumah',
            'receiver_name' => 'Sari',
            'receiver_phone' => '08111111111',
            'full_address' => 'Jalan Kenanga No. 1',
            'province_id' => $location['province_id'],
            'regency_id' => $location['regency_id'],
            'district_id' => $location['district_id'],
            'village_id' => $location['village_id'],
            'postal_code' => '11111',
            'is_main' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch("/profile/addresses/{$address->id}", [
                'label' => 'Ambil Alih',
                'receiver_name' => 'Sari',
                'receiver_phone' => '08111111111',
                'full_address' => 'Jalan Kenanga No. 1',
                'province_id' => $location['province_id'],
                'regency_id' => $location['regency_id'],
                'district_id' => $location['district_id'],
                'village_id' => $location['village_id'],
                'postal_code' => '11111',
            ]);

        $response->assertNotFound();
        $this->assertSame('Rumah', $address->refresh()->label);
    }

    public function test_user_cannot_delete_another_users_address(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $location = $this->createLocation();
        $address = $otherUser->addresses()->create([
            'label' => 'Rumah',
            'receiver_name' => 'Sari',
            'receiver_phone' => '08111111111',
            'full_address' => 'Jalan Kenanga No. 1',
            'province_id' => $location['province_id'],
            'regency_id' => $location['regency_id'],
            'district_id' => $location['district_id'],
            'village_id' => $location['village_id'],
            'postal_code' => '11111',
            'is_main' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete("/profile/addresses/{$address->id}");

        $response->assertNotFound();
        $this->assertDatabaseHas('user_addresses', [
            'id' => $address->id,
        ]);
    }

    public function test_setting_an_address_as_main_unsets_other_addresses(): void
    {
        $user = User::factory()->create();
        $location = $this->createLocation();
        $this->mockRajaOngkirRegions();
        $firstAddress = $user->addresses()->create([
            'label' => 'Rumah',
            'receiver_name' => 'Budi',
            'receiver_phone' => '08123456789',
            'full_address' => 'Jalan Mawar No. 10',
            'province_id' => $location['province_id'],
            'regency_id' => $location['regency_id'],
            'district_id' => $location['district_id'],
            'village_id' => $location['village_id'],
            'postal_code' => '12345',
            'is_main' => true,
        ]);
        $secondAddress = $user->addresses()->create([
            'label' => 'Kantor',
            'receiver_name' => 'Budi',
            'receiver_phone' => '08123456780',
            'full_address' => 'Jalan Melati No. 20',
            'province_id' => $location['province_id'],
            'regency_id' => $location['regency_id'],
            'district_id' => $location['district_id'],
            'village_id' => $location['village_id'],
            'postal_code' => '54321',
            'is_main' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch("/profile/addresses/{$secondAddress->id}", [
                'label' => 'Kantor',
                'receiver_name' => 'Budi',
                'receiver_phone' => '08123456780',
                'full_address' => 'Jalan Melati No. 20',
                'province_id' => $location['province_id'],
                'regency_id' => $location['regency_id'],
                'district_id' => $location['district_id'],
                'village_id' => $location['village_id'],
                'postal_code' => '54321',
                'is_main' => '1',
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertFalse($firstAddress->refresh()->is_main);
        $this->assertTrue($secondAddress->refresh()->is_main);
    }

    public function test_deleting_main_address_promotes_latest_remaining_address(): void
    {
        $user = User::factory()->create();
        $location = $this->createLocation();
        $firstAddress = $user->addresses()->create([
            'label' => 'Rumah',
            'receiver_name' => 'Budi',
            'receiver_phone' => '08123456789',
            'full_address' => 'Jalan Mawar No. 10',
            'province_id' => $location['province_id'],
            'regency_id' => $location['regency_id'],
            'district_id' => $location['district_id'],
            'village_id' => $location['village_id'],
            'postal_code' => '12345',
            'is_main' => true,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
        $secondAddress = $user->addresses()->create([
            'label' => 'Kantor',
            'receiver_name' => 'Budi',
            'receiver_phone' => '08123456780',
            'full_address' => 'Jalan Melati No. 20',
            'province_id' => $location['province_id'],
            'regency_id' => $location['regency_id'],
            'district_id' => $location['district_id'],
            'village_id' => $location['village_id'],
            'postal_code' => '54321',
            'is_main' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->delete("/profile/addresses/{$firstAddress->id}");

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertDatabaseMissing('user_addresses', [
            'id' => $firstAddress->id,
        ]);
        $this->assertTrue($secondAddress->refresh()->is_main);
    }

    public function test_user_can_delete_saved_address_without_changing_order_shipping_snapshot(): void
    {
        $user = User::factory()->create();
        $location = $this->createLocation();
        $address = $user->addresses()->create([
            'label' => 'Rumah',
            'receiver_name' => 'Budi',
            'receiver_phone' => '08123456789',
            'full_address' => 'Jalan Mawar No. 10',
            'province_id' => $location['province_id'],
            'regency_id' => $location['regency_id'],
            'district_id' => $location['district_id'],
            'village_id' => $location['village_id'],
            'postal_code' => '12345',
            'is_main' => true,
        ]);

        DB::table('orders')->insert([
            'user_id' => $user->id,
            'order_number' => 'TEST-ADDRESS-DELETE',
            'subtotal' => 100000,
            'discount_amount' => 0,
            'shipping_cost' => 0,
            'shipping_cost_status' => 'fixed',
            'total_amount' => 100000,
            'shipping_name' => 'Budi',
            'shipping_phone' => '08123456789',
            'shipping_address' => 'Jalan Mawar No. 10',
            'shipping_province' => 'DKI Jakarta',
            'shipping_city' => 'Jakarta Selatan',
            'shipping_district' => 'Kebayoran Baru',
            'shipping_village' => 'Senayan',
            'shipping_postal_code' => '12345',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->delete("/profile/addresses/{$address->id}");

        $response
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'address-deleted');

        $this->assertDatabaseMissing('user_addresses', [
            'id' => $address->id,
        ]);
        $this->assertDatabaseHas('orders', [
            'order_number' => 'TEST-ADDRESS-DELETE',
            'shipping_name' => 'Budi',
            'shipping_phone' => '08123456789',
            'shipping_address' => 'Jalan Mawar No. 10',
            'shipping_province' => 'DKI Jakarta',
            'shipping_city' => 'Jakarta Selatan',
            'shipping_district' => 'Kebayoran Baru',
            'shipping_village' => 'Senayan',
            'shipping_postal_code' => '12345',
        ]);
    }

    public function test_region_dropdown_endpoints_return_only_child_regions(): void
    {
        $user = User::factory()->create();
        $this->mockRajaOngkirRegions();

        $this->actingAs($user)
            ->getJson('/regions/provinces')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['name' => 'DKI Jakarta'])
            ->assertJsonFragment(['name' => 'Jawa Barat']);

        $this->actingAs($user)
            ->getJson('/regions/provinces/1/regencies')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['name' => 'Jakarta Selatan'])
            ->assertJsonFragment(['name' => 'Jakarta Timur'])
            ->assertJsonMissing(['name' => 'Bandung']);

        $this->actingAs($user)
            ->getJson('/regions/regencies/1/districts')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['name' => 'Kebayoran Baru'])
            ->assertJsonFragment(['name' => 'Setiabudi'])
            ->assertJsonMissing(['name' => 'Coblong']);

        $this->actingAs($user)
            ->getJson('/regions/districts/1/villages')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['name' => 'Senayan'])
            ->assertJsonFragment(['name' => 'Gandaria Utara'])
            ->assertJsonMissing(['name' => 'Dago']);
    }

    private function createLocation(): array
    {
        DB::table('provinces')->insert(['id' => 1, 'name' => 'DKI Jakarta']);
        DB::table('regencies')->insert(['id' => 1, 'province_id' => 1, 'name' => 'Jakarta Selatan']);
        DB::table('districts')->insert(['id' => 1, 'regency_id' => 1, 'name' => 'Kebayoran Baru']);
        DB::table('villages')->insert(['id' => 1, 'district_id' => 1, 'name' => 'Senayan']);

        return [
            'province_id' => 1,
            'regency_id' => 1,
            'district_id' => 1,
            'village_id' => 1,
        ];
    }

    private function mockRajaOngkirRegions(): void
    {
        $this->mock(RajaOngkirService::class, function ($mock): void {
            $mock->shouldReceive('isConfigured')->zeroOrMoreTimes()->andReturn(true);
            $mock->shouldReceive('provinces')->zeroOrMoreTimes()->andReturn([
                ['id' => '1', 'name' => 'DKI Jakarta', 'postal_code' => null],
                ['id' => '2', 'name' => 'Jawa Barat', 'postal_code' => null],
            ]);
            $mock->shouldReceive('cities')->zeroOrMoreTimes()->with('1')->andReturn([
                ['id' => '1', 'name' => 'Jakarta Selatan', 'postal_code' => null],
                ['id' => '2', 'name' => 'Jakarta Timur', 'postal_code' => null],
            ]);
            $mock->shouldReceive('districts')->zeroOrMoreTimes()->with('1')->andReturn([
                ['id' => '1', 'name' => 'Kebayoran Baru', 'postal_code' => null],
                ['id' => '2', 'name' => 'Setiabudi', 'postal_code' => null],
            ]);
            $mock->shouldReceive('subdistricts')->zeroOrMoreTimes()->with('1')->andReturn([
                ['id' => '1', 'name' => 'Senayan', 'postal_code' => '12345'],
                ['id' => '2', 'name' => 'Gandaria Utara', 'postal_code' => '12140'],
            ]);
            $mock->shouldReceive('findProvince')->zeroOrMoreTimes()->with('1')->andReturn([
                'id' => '1',
                'name' => 'DKI Jakarta',
                'postal_code' => null,
            ]);
            $mock->shouldReceive('findCity')->zeroOrMoreTimes()->with('1', '1')->andReturn([
                'id' => '1',
                'name' => 'Jakarta Selatan',
                'postal_code' => null,
            ]);
            $mock->shouldReceive('findDistrict')->zeroOrMoreTimes()->with('1', '1')->andReturn([
                'id' => '1',
                'name' => 'Kebayoran Baru',
                'postal_code' => null,
            ]);
            $mock->shouldReceive('findSubdistrict')->zeroOrMoreTimes()->with('1', '1')->andReturn([
                'id' => '1',
                'name' => 'Senayan',
                'postal_code' => '12345',
            ]);
        });
    }

    private function makeCustomer(): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'pelanggan'],
            ['guard_name' => 'web']
        );
        $user = User::factory()->create();

        $user->assignRole($role->name);

        return $user;
    }
}
