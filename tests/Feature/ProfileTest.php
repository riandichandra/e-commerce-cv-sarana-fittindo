<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_setting_an_address_as_main_unsets_other_addresses(): void
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
}
