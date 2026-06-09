<?php

namespace App\Http\Controllers;

use App\Services\RajaOngkirService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class RajaOngkirRegionController extends Controller
{
    public function provinces(RajaOngkirService $rajaOngkir): JsonResponse
    {
        return $this->respond($rajaOngkir, fn () => $rajaOngkir->provinces());
    }

    public function regencies(RajaOngkirService $rajaOngkir, string $province): JsonResponse
    {
        return $this->respond($rajaOngkir, fn () => $rajaOngkir->cities($province));
    }

    public function districts(RajaOngkirService $rajaOngkir, string $regency): JsonResponse
    {
        return $this->respond($rajaOngkir, fn () => $rajaOngkir->districts($regency));
    }

    public function villages(RajaOngkirService $rajaOngkir, string $district): JsonResponse
    {
        return $this->respond($rajaOngkir, fn () => $rajaOngkir->subdistricts($district));
    }

    private function respond(RajaOngkirService $rajaOngkir, Closure $callback): JsonResponse
    {
        if (! $rajaOngkir->isConfigured()) {
            return response()->json([
                'message' => 'API key RajaOngkir belum dikonfigurasi.',
            ], 503);
        }

        try {
            return response()->json($callback());
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat data wilayah RajaOngkir.', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Gagal memuat data wilayah RajaOngkir.',
            ], 502);
        }
    }
}
