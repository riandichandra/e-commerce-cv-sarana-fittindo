<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\UserAddress;
use App\Models\Village;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $wishlistItems = $request->user()
            ->wishlists()
            ->with(['product.images', 'product.category'])
            ->latest()
            ->get();

        $addresses = $request->user()
            ->addresses()
            ->with(['province', 'regency', 'district', 'village'])
            ->orderByDesc('is_main')
            ->latest()
            ->get();

        $editingAddress = null;

        if ($request->filled('address')) {
            $editingAddress = $request->user()
                ->addresses()
                ->whereKey($request->integer('address'))
                ->first();
        }

        return view('profile.edit', [
            'user' => $request->user(),
            'wishlistItems' => $wishlistItems,
            'addresses' => $addresses,
            'editingAddress' => $editingAddress,
            'provinces' => Province::orderBy('name')->get(),
            'regencies' => Regency::orderBy('name')->get(),
            'districts' => District::orderBy('name')->get(),
            'villages' => Village::orderBy('name')->get(),
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

        if ($request->filled('redirect_to') && $request->input('redirect_to') === route('admin.settings.index')) {
            return Redirect::route('admin.settings.index')->with('status', 'profile-updated');
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
        $validator = Validator::make($request->all(), [
            'label' => ['required', 'string', 'max:50'],
            'receiver_name' => ['required', 'string', 'max:100'],
            'receiver_phone' => ['required', 'string', 'max:20'],
            'full_address' => ['required', 'string'],
            'province_id' => ['required', 'integer', 'exists:provinces,id'],
            'regency_id' => ['required', 'integer', 'exists:regencies,id'],
            'district_id' => ['required', 'integer', 'exists:districts,id'],
            'village_id' => ['required', 'integer', 'exists:villages,id'],
            'postal_code' => ['required', 'string', 'max:10'],
            'is_main' => ['nullable', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $regencyBelongsToProvince = Regency::whereKey($request->integer('regency_id'))
                ->where('province_id', $request->integer('province_id'))
                ->exists();
            $districtBelongsToRegency = District::whereKey($request->integer('district_id'))
                ->where('regency_id', $request->integer('regency_id'))
                ->exists();
            $villageBelongsToDistrict = Village::whereKey($request->integer('village_id'))
                ->where('district_id', $request->integer('district_id'))
                ->exists();

            if (! $regencyBelongsToProvince) {
                $validator->errors()->add('regency_id', 'Kabupaten/kota tidak sesuai dengan provinsi.');
            }

            if (! $districtBelongsToRegency) {
                $validator->errors()->add('district_id', 'Kecamatan tidak sesuai dengan kabupaten/kota.');
            }

            if (! $villageBelongsToDistrict) {
                $validator->errors()->add('village_id', 'Desa/kelurahan tidak sesuai dengan kecamatan.');
            }
        });

        return $validator->validate();
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
