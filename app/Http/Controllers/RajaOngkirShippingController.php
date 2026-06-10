<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Services\RajaOngkirService;
use App\Services\ShippingQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class RajaOngkirShippingController extends Controller
{
    public function domesticCost(
        Request $request,
        RajaOngkirService $rajaOngkir,
        ShippingQuoteService $shippingQuotes
    ): JsonResponse {
        if (! $rajaOngkir->isConfigured()) {
            return response()->json([
                'message' => 'API key RajaOngkir belum dikonfigurasi.',
            ], 503);
        }

        if (! $rajaOngkir->canCalculateShipping()) {
            return response()->json([
                'message' => 'Origin pengiriman RajaOngkir belum dikonfigurasi.',
            ], 503);
        }

        $validated = $request->validate([
            'shipping_address_id' => [
                'nullable',
                Rule::exists('user_addresses', 'id')->where('user_id', $request->user()->id),
            ],
            'shipping_province_id' => ['required_without:shipping_address_id', 'nullable', 'string', 'max:32'],
            'shipping_city_id' => ['required_without:shipping_address_id', 'nullable', 'string', 'max:32'],
            'shipping_district_id' => ['required_without:shipping_address_id', 'nullable', 'string', 'max:32'],
            'shipping_village_id' => ['required_without:shipping_address_id', 'nullable', 'string', 'max:32'],
        ]);

        $cart = Cart::getForUser($request->user());
        $cart->load('items.product');

        try {
            $weightGram = $shippingQuotes->weightForCart($cart);
            $destinationDistrictId = $this->resolveDestinationDistrictId($request, $validated, $rajaOngkir);
            $originDistrictId = (string) $rajaOngkir->originDistrictId();

            $options = $rajaOngkir->calculateDistrictDomesticCost(
                $originDistrictId,
                $destinationDistrictId,
                $weightGram
            );

            if ($options === []) {
                return response()->json([
                    'message' => 'Tidak ada layanan pengiriman tersedia untuk alamat ini.',
                ], 404);
            }

            $data = collect($options)
                ->map(function (array $option) use ($request, $cart, $shippingQuotes, $originDistrictId, $destinationDistrictId, $weightGram): array {
                    $token = $shippingQuotes->storeQuote(
                        $request->user(),
                        $cart,
                        $option,
                        $originDistrictId,
                        $destinationDistrictId,
                        $weightGram
                    );

                    return [
                        ...$option,
                        'quote_token' => $token,
                        'weight_gram' => $weightGram,
                    ];
                })
                ->values()
                ->all();

            return response()->json([
                'data' => $data,
                'meta' => [
                    'origin' => $rajaOngkir->originLabel(),
                    'origin_district_id' => $originDistrictId,
                    'destination_district_id' => $destinationDistrictId,
                    'weight_gram' => $weightGram,
                    'expires_in' => $shippingQuotes->ttl(),
                ],
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            Log::warning('Gagal menghitung ongkos kirim RajaOngkir.', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Gagal menghitung ongkos kirim. Coba beberapa saat lagi.',
            ], 502);
        }
    }

    private function resolveDestinationDistrictId(Request $request, array $validated, RajaOngkirService $rajaOngkir): string
    {
        if (! empty($validated['shipping_address_id'])) {
            $address = $request->user()
                ->addresses()
                ->findOrFail($validated['shipping_address_id']);

            if ($address->region_source !== 'rajaongkir' || ! filled($address->district_id)) {
                throw new RuntimeException('Alamat tersimpan perlu diperbarui ke data RajaOngkir sebelum ongkir otomatis dapat dihitung.');
            }

            return (string) $address->district_id;
        }

        $regions = $rajaOngkir->resolveRegionChain(
            (string) $validated['shipping_province_id'],
            (string) $validated['shipping_city_id'],
            (string) $validated['shipping_district_id'],
            (string) $validated['shipping_village_id'],
        );

        if (! $regions['province']) {
            throw new RuntimeException('Provinsi tidak ditemukan di RajaOngkir.');
        }

        if (! $regions['city']) {
            throw new RuntimeException('Kabupaten/kota tidak sesuai dengan provinsi.');
        }

        if (! $regions['district']) {
            throw new RuntimeException('Kecamatan tidak sesuai dengan kabupaten/kota.');
        }

        if (! $regions['subdistrict']) {
            throw new RuntimeException('Desa/kelurahan tidak sesuai dengan kecamatan.');
        }

        return (string) $validated['shipping_district_id'];
    }
}
