<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\UserAddress;
use App\Services\RajaOngkirService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Throwable;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $isCustomer = $user->hasRole('pelanggan');

        $wishlistItems = $isCustomer
            ? $user
                ->wishlists()
                ->with(['product.images', 'product.category'])
                ->latest()
                ->get()
            : collect();

        $addresses = $isCustomer
            ? $user
                ->addresses()
                ->with(['province', 'regency', 'district', 'village'])
                ->orderByDesc('is_main')
                ->latest()
                ->get()
            : collect();

        $editingAddress = null;

        if ($isCustomer && $request->filled('address')) {
            $editingAddress = $user
                ->addresses()
                ->whereKey($request->integer('address'))
                ->first();
        }

        return view('profile.edit', [
            'user' => $user,
            'isCustomer' => $isCustomer,
            'dashboardUrl' => url($user->redirectBasedOnRole()),
            'roleName' => ucwords(str_replace('_', ' ', $user->getRoleNames()->first() ?? '-')),
            'wishlistItems' => $wishlistItems,
            'addresses' => $addresses,
            'editingAddress' => $editingAddress,
            'hasRajaOngkirConfig' => app(RajaOngkirService::class)->isConfigured(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        if ($request->filled('redirect_to')) {
            $allowedRedirects = collect([
                'admin.settings.index',
                'marketing.settings.index',
                'gm.settings.index',
                'direktur.settings.index',
            ])->filter(fn ($routeName) => Route::has($routeName))
              ->map(fn ($routeName) => route($routeName))
              ->all();

            if (in_array($request->input('redirect_to'), $allowedRedirects, true)) {
                return Redirect::to($request->input('redirect_to'))->with('status', 'profile-updated');
            }
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Store a new user address.
     */
    public function storeAddress(Request $request): RedirectResponse
    {
        $validated = $this->validateAddress($request);
        $makeMain = $request->boolean('is_main') || ! $request->user()->addresses()->exists();

        DB::transaction(function () use ($request, $validated, $makeMain) {
            if ($makeMain) {
                $request->user()->addresses()->update(['is_main' => false]);
            }

            $request->user()->addresses()->create([
                ...$validated,
                'is_main' => $makeMain,
            ]);
        });

        return Redirect::route('profile.edit')
            ->with('status', 'address-created');
    }

    /**
     * Update an existing user address.
     */
    public function updateAddress(Request $request, UserAddress $address): RedirectResponse
    {
        $this->ensureAddressOwner($request, $address);

        $validated = $this->validateAddress($request);

        DB::transaction(function () use ($request, $address, $validated) {
            if ($request->boolean('is_main')) {
                $request->user()->addresses()->whereKeyNot($address->id)->update(['is_main' => false]);
            }

            $address->update([
                ...$validated,
                'is_main' => $request->boolean('is_main') || $address->is_main,
            ]);
        });

        return Redirect::route('profile.edit')
            ->with('status', 'address-updated');
    }

    /**
     * Delete an existing user address.
     */
    public function destroyAddress(Request $request, UserAddress $address): RedirectResponse
    {
        $this->ensureAddressOwner($request, $address);

        DB::transaction(function () use ($request, $address) {
            $wasMain = $address->is_main;

            $address->delete();

            if ($wasMain) {
                $request->user()
                    ->addresses()
                    ->latest()
                    ->first()
                    ?->update(['is_main' => true]);
            }
        });

        return Redirect::route('profile.edit')
            ->with('status', 'address-deleted');
    }

    private function validateAddress(Request $request): array
    {
        $regionSnapshot = [];

        $validator = Validator::make($request->all(), [
            'label' => ['required', 'string', 'max:50'],
            'receiver_name' => ['required', 'string', 'max:100'],
            'receiver_phone' => ['required', 'string', 'max:20'],
            'full_address' => ['required', 'string'],
            'province_id' => ['required'],
            'regency_id' => ['required'],
            'district_id' => ['required'],
            'village_id' => ['required'],
            'postal_code' => ['required', 'string', 'max:10'],
            'is_main' => ['nullable', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request, &$regionSnapshot) {
            if ($validator->errors()->any()) {
                return;
            }

            $rajaOngkir = app(RajaOngkirService::class);

            if (! $rajaOngkir->isConfigured()) {
                $validator->errors()->add('province_id', 'API key RajaOngkir belum dikonfigurasi.');

                return;
            }

            try {
                $provinceId = (string) $request->input('province_id');
                $regencyId = (string) $request->input('regency_id');
                $districtId = (string) $request->input('district_id');
                $villageId = (string) $request->input('village_id');

                foreach ([
                    'province_id' => $provinceId,
                    'regency_id' => $regencyId,
                    'district_id' => $districtId,
                    'village_id' => $villageId,
                ] as $field => $value) {
                    if (strlen($value) > 32) {
                        $validator->errors()->add($field, 'ID wilayah tidak valid.');
                    }
                }

                if ($validator->errors()->any()) {
                    return;
                }

                $province = $rajaOngkir->findProvince($provinceId);
                $regency = $province ? $rajaOngkir->findCity($provinceId, $regencyId) : null;
                $district = $regency ? $rajaOngkir->findDistrict($regencyId, $districtId) : null;
                $village = $district ? $rajaOngkir->findSubdistrict($districtId, $villageId) : null;

                if (! $province) {
                    $validator->errors()->add('province_id', 'Provinsi tidak ditemukan di RajaOngkir.');
                }

                if (! $regency) {
                    $validator->errors()->add('regency_id', 'Kabupaten/kota tidak sesuai dengan provinsi.');
                }

                if (! $district) {
                    $validator->errors()->add('district_id', 'Kecamatan tidak sesuai dengan kabupaten/kota.');
                }

                if (! $village) {
                    $validator->errors()->add('village_id', 'Desa/kelurahan tidak sesuai dengan kecamatan.');
                }

                if ($validator->errors()->any()) {
                    return;
                }

                $regionSnapshot = [
                    'province_name' => $province['name'],
                    'city_name' => $regency['name'],
                    'district_name' => $district['name'],
                    'village_name' => $village['name'],
                    'region_source' => 'rajaongkir',
                ];
            } catch (Throwable) {
                $validator->errors()->add('province_id', 'Gagal memvalidasi wilayah ke RajaOngkir. Coba beberapa saat lagi.');
            }
        });

        return [
            ...$validator->validate(),
            ...$regionSnapshot,
        ];
    }

    private function ensureAddressOwner(Request $request, UserAddress $address): void
    {
        abort_unless($address->user_id === $request->user()->id, 404);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
