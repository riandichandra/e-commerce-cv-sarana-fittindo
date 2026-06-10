<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RajaOngkirService
{
    public function isConfigured(): bool
    {
        return filled($this->apiKey());
    }

    public function canCalculateShipping(): bool
    {
        return $this->isConfigured() && filled($this->originDistrictId());
    }

    public function originDistrictId(): ?string
    {
        $originDistrictId = config('services.rajaongkir.origin_district_id');

        return filled($originDistrictId) ? (string) $originDistrictId : null;
    }

    public function originLabel(): string
    {
        return filled(config('services.rajaongkir.origin_label'))
            ? (string) config('services.rajaongkir.origin_label')
            : 'Gudang utama';
    }

    public function defaultCouriers(): string
    {
        return filled(config('services.rajaongkir.default_couriers'))
            ? (string) config('services.rajaongkir.default_couriers')
            : 'jne:sicepat:jnt:tiki:pos';
    }

    public function shippingQuoteTtl(): int
    {
        return max(60, (int) config('services.rajaongkir.shipping_quote_ttl', 600));
    }

    public function provinces(): array
    {
        return $this->remember('provinces', fn () => $this->getRegions('destination/province'));
    }

    public function cities(string $provinceId): array
    {
        return $this->remember("cities:{$provinceId}", fn () => $this->getRegions("destination/city/{$provinceId}"));
    }

    public function districts(string $cityId): array
    {
        return $this->remember("districts:{$cityId}", fn () => $this->getRegions("destination/district/{$cityId}"));
    }

    public function subdistricts(string $districtId): array
    {
        return $this->remember("subdistricts:{$districtId}", fn () => $this->getRegions("destination/sub-district/{$districtId}"));
    }

    public function findProvince(string $provinceId): ?array
    {
        return $this->findById($this->provinces(), $provinceId);
    }

    public function findCity(string $provinceId, string $cityId): ?array
    {
        return $this->findById($this->cities($provinceId), $cityId);
    }

    public function findDistrict(string $cityId, string $districtId): ?array
    {
        return $this->findById($this->districts($cityId), $districtId);
    }

    public function findSubdistrict(string $districtId, string $subdistrictId): ?array
    {
        return $this->findById($this->subdistricts($districtId), $subdistrictId);
    }

    public function resolveRegionChain(string $provinceId, string $cityId, string $districtId, string $subdistrictId): array
    {
        $province = $this->findProvince($provinceId);
        $city = $province ? $this->findCity($provinceId, $cityId) : null;
        $district = $city ? $this->findDistrict($cityId, $districtId) : null;
        $subdistrict = $district ? $this->findSubdistrict($districtId, $subdistrictId) : null;

        return [
            'province' => $province,
            'city' => $city,
            'district' => $district,
            'subdistrict' => $subdistrict,
        ];
    }

    public function calculateDistrictDomesticCost(
        string $originDistrictId,
        string $destinationDistrictId,
        int $weightGram,
        ?string $couriers = null,
        string $price = 'lowest'
    ): array {
        if (! $this->isConfigured()) {
            throw new RuntimeException('API key RajaOngkir belum dikonfigurasi.');
        }

        if (! filled($originDistrictId)) {
            throw new RuntimeException('Origin district RajaOngkir belum dikonfigurasi.');
        }

        if (! filled($destinationDistrictId)) {
            throw new RuntimeException('Destination district RajaOngkir belum tersedia.');
        }

        if ($weightGram < 1) {
            throw new RuntimeException('Berat pengiriman harus lebih dari 0 gram.');
        }

        $response = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->asForm()
            ->withHeaders(['key' => $this->apiKey()])
            ->timeout($this->timeout())
            ->retry(2, 200, null, false)
            ->post('calculate/district/domestic-cost', [
                'origin' => $originDistrictId,
                'destination' => $destinationDistrictId,
                'weight' => $weightGram,
                'courier' => $couriers ?: $this->defaultCouriers(),
                'price' => $price,
            ]);

        $payload = $response->json();
        $metaCode = data_get($payload, 'meta.code');

        if (! $response->successful() || ($metaCode && (int) $metaCode >= 400)) {
            $message = data_get($payload, 'meta.message', 'Gagal menghitung ongkos kirim RajaOngkir.');

            throw new RuntimeException($message, $response->status());
        }

        $data = data_get($payload, 'data', []);

        if (! is_array($data)) {
            return [];
        }

        return $this->normalizeShippingOptions($data);
    }

    public function normalizeShippingOptions(array $options): array
    {
        return collect($options)
            ->map(function (array $option): array {
                return [
                    'courier_name' => (string) ($option['name'] ?? ''),
                    'courier_code' => (string) ($option['code'] ?? ''),
                    'service' => (string) ($option['service'] ?? ''),
                    'description' => (string) ($option['description'] ?? ''),
                    'cost' => (int) ($option['cost'] ?? 0),
                    'etd' => isset($option['etd']) ? (string) $option['etd'] : null,
                ];
            })
            ->filter(function (array $option): bool {
                return $option['courier_code'] !== ''
                    && $option['service'] !== ''
                    && $option['cost'] >= 0;
            })
            ->values()
            ->all();
    }

    private function getRegions(string $path): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('API key RajaOngkir belum dikonfigurasi.');
        }

        $response = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->withHeaders(['key' => $this->apiKey()])
            ->timeout($this->timeout())
            ->retry(2, 200, null, false)
            ->get($path);

        $payload = $response->json();
        $metaCode = data_get($payload, 'meta.code');

        if (! $response->successful() || ($metaCode && (int) $metaCode >= 400)) {
            $message = data_get($payload, 'meta.message', 'Gagal mengambil data wilayah RajaOngkir.');

            throw new RuntimeException($message, $response->status());
        }

        $data = data_get($payload, 'data', []);

        if (! is_array($data)) {
            return [];
        }

        return $this->normalizeRegions($data);
    }

    private function normalizeRegions(array $regions): array
    {
        return collect($regions)
            ->map(function (array $region): array {
                $id = $region['id']
                    ?? $region['province_id']
                    ?? $region['city_id']
                    ?? $region['district_id']
                    ?? $region['subdistrict_id']
                    ?? null;

                $name = $region['name']
                    ?? $region['province_name']
                    ?? $region['city_name']
                    ?? $region['district_name']
                    ?? $region['subdistrict_name']
                    ?? null;

                return [
                    'id' => $id === null ? '' : (string) $id,
                    'name' => $name === null ? '' : (string) $name,
                    'postal_code' => isset($region['zip_code'])
                        ? (string) $region['zip_code']
                        : (isset($region['postal_code']) ? (string) $region['postal_code'] : null),
                ];
            })
            ->filter(fn (array $region): bool => $region['id'] !== '' && $region['name'] !== '')
            ->values()
            ->all();
    }

    private function remember(string $key, callable $callback): array
    {
        return Cache::remember(
            "rajaongkir:regions:{$key}",
            $this->regionCacheTtl(),
            $callback
        );
    }

    private function findById(array $regions, string $id): ?array
    {
        return collect($regions)->first(
            fn (array $region): bool => (string) $region['id'] === (string) $id
        );
    }

    private function apiKey(): ?string
    {
        $apiKey = config('services.rajaongkir.api_key');

        return filled($apiKey) ? (string) $apiKey : null;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.rajaongkir.base_url'), '/');
    }

    private function timeout(): int
    {
        return max(1, (int) config('services.rajaongkir.timeout', 8));
    }

    private function regionCacheTtl(): int
    {
        return max(60, (int) config('services.rajaongkir.region_cache_ttl', 604800));
    }
}
