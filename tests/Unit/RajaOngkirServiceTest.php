<?php

namespace Tests\Unit;

use App\Services\RajaOngkirService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class RajaOngkirServiceTest extends TestCase
{
    public function test_calculate_district_domestic_cost_posts_form_request_and_normalizes_response(): void
    {
        config([
            'services.rajaongkir.api_key' => 'test-key',
            'services.rajaongkir.base_url' => 'https://rajaongkir.komerce.id/api/v1',
            'services.rajaongkir.default_couriers' => 'jne:pos',
        ]);

        Http::fake([
            'https://rajaongkir.komerce.id/api/v1/calculate/district/domestic-cost' => Http::response([
                'meta' => [
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'Success Calculate Domestic Shipping cost',
                ],
                'data' => [
                    [
                        'name' => 'Jalur Nugraha Ekakurir (JNE)',
                        'code' => 'jne',
                        'service' => 'REG',
                        'description' => 'Regular Service',
                        'cost' => 45000,
                        'etd' => '1-2 day',
                    ],
                ],
            ], 200),
        ]);

        $options = app(RajaOngkirService::class)->calculateDistrictDomesticCost('1391', '3', 2000);

        $this->assertSame([
            [
                'courier_name' => 'Jalur Nugraha Ekakurir (JNE)',
                'courier_code' => 'jne',
                'service' => 'REG',
                'description' => 'Regular Service',
                'cost' => 45000,
                'etd' => '1-2 day',
            ],
        ], $options);

        Http::assertSent(function ($request): bool {
            return $request->method() === 'POST'
                && $request->hasHeader('key', 'test-key')
                && $request->data()['origin'] === '1391'
                && $request->data()['destination'] === '3'
                && $request->data()['weight'] === 2000
                && $request->data()['courier'] === 'jne:pos'
                && $request->data()['price'] === 'lowest';
        });
    }

    public function test_calculate_district_domestic_cost_throws_safe_message_on_api_error(): void
    {
        config([
            'services.rajaongkir.api_key' => 'test-key',
            'services.rajaongkir.base_url' => 'https://rajaongkir.komerce.id/api/v1',
        ]);

        Http::fake([
            'https://rajaongkir.komerce.id/api/v1/calculate/district/domestic-cost' => Http::response([
                'meta' => [
                    'code' => 422,
                    'status' => 'error',
                    'message' => 'Kode kurir tidak valid.',
                ],
                'data' => null,
            ], 422),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Kode kurir tidak valid.');

        app(RajaOngkirService::class)->calculateDistrictDomesticCost('1391', '3', 2000);
    }
}
