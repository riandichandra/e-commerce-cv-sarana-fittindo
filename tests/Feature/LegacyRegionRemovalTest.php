<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyRegionRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_region_tables_are_not_present(): void
    {
        $this->assertFalse(Schema::hasTable('provinces'));
        $this->assertFalse(Schema::hasTable('regencies'));
        $this->assertFalse(Schema::hasTable('districts'));
        $this->assertFalse(Schema::hasTable('villages'));
        $this->assertFalse(Schema::hasTable('shipping_costs'));
        $this->assertTrue(Schema::hasTable('user_addresses'));
        $this->assertTrue(Schema::hasTable('products'));
        $this->assertTrue(Schema::hasTable('order_items'));
    }

    public function test_saved_address_uses_rajaongkir_snapshot_without_local_region_tables(): void
    {
        $user = User::factory()->create();
        $address = $user->addresses()->create([
            'label' => 'Rumah',
            'receiver_name' => 'Budi',
            'receiver_phone' => '08123456789',
            'full_address' => 'Jalan Mawar No. 10',
            'province_id' => '26',
            'regency_id' => '480',
            'district_id' => '4558',
            'village_id' => '53278',
            'province_name' => 'SUMATERA SELATAN',
            'city_name' => 'OGAN KOMERING ILIR',
            'district_name' => 'TANJUNG LUBUK',
            'village_name' => 'TANJUNG LUBUK PMD',
            'region_source' => 'rajaongkir',
            'postal_code' => '30671',
            'is_main' => true,
        ]);

        $this->assertSame('SUMATERA SELATAN', $address->province_display_name);
        $this->assertSame('OGAN KOMERING ILIR', $address->city_display_name);
        $this->assertSame('TANJUNG LUBUK', $address->district_display_name);
        $this->assertSame('TANJUNG LUBUK PMD', $address->village_display_name);
        $this->assertSame(
            'TANJUNG LUBUK PMD, TANJUNG LUBUK, OGAN KOMERING ILIR, SUMATERA SELATAN, 30671',
            $address->region_summary
        );
    }
}
