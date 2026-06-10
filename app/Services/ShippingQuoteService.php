<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class ShippingQuoteService
{
    public function weightForCart(Cart $cart): int
    {
        $cart->loadMissing('items.product');

        if ($cart->items->isEmpty()) {
            throw new RuntimeException('Keranjang masih kosong.');
        }

        $weight = $cart->items->sum(function (CartItem $item): int {
            $product = $item->product;

            if (! $product || (float) $product->weight <= 0) {
                throw new RuntimeException('Berat produk ' . ($product?->name ?? 'tidak diketahui') . ' belum valid.');
            }

            return (int) ceil((float) $product->weight * (int) $item->quantity);
        });

        return max(1, (int) $weight);
    }

    public function storeQuote(
        User $user,
        Cart $cart,
        array $option,
        string $originDistrictId,
        string $destinationDistrictId,
        int $weightGram
    ): string {
        $token = Str::random(48);
        $ttl = $this->ttl();

        Cache::put($this->cacheKey($token), [
            'user_id' => $user->id,
            'cart_id' => $cart->id,
            'origin_district_id' => $originDistrictId,
            'destination_district_id' => $destinationDistrictId,
            'weight_gram' => $weightGram,
            'courier_name' => (string) ($option['courier_name'] ?? ''),
            'courier_code' => (string) ($option['courier_code'] ?? ''),
            'service' => (string) ($option['service'] ?? ''),
            'description' => (string) ($option['description'] ?? ''),
            'cost' => (int) ($option['cost'] ?? 0),
            'etd' => isset($option['etd']) ? (string) $option['etd'] : null,
            'rate_snapshot' => $option,
            'expires_at' => now()->addSeconds($ttl)->toIso8601String(),
        ], $ttl);

        return $token;
    }

    public function getQuote(?string $token): ?array
    {
        if (! filled($token)) {
            return null;
        }

        $quote = Cache::get($this->cacheKey((string) $token));

        if (! is_array($quote)) {
            return null;
        }

        if (isset($quote['expires_at']) && now()->greaterThanOrEqualTo(Carbon::parse($quote['expires_at']))) {
            $this->forgetQuote((string) $token);

            return null;
        }

        return $quote;
    }

    public function forgetQuote(?string $token): void
    {
        if (filled($token)) {
            Cache::forget($this->cacheKey((string) $token));
        }
    }

    public function matchesCart(array $quote, User $user, Cart $cart, int $weightGram, ?string $destinationDistrictId = null): bool
    {
        if ((int) ($quote['user_id'] ?? 0) !== (int) $user->id) {
            return false;
        }

        if ((int) ($quote['cart_id'] ?? 0) !== (int) $cart->id) {
            return false;
        }

        if ((int) ($quote['weight_gram'] ?? 0) !== $weightGram) {
            return false;
        }

        if ($destinationDistrictId !== null && (string) ($quote['destination_district_id'] ?? '') !== (string) $destinationDistrictId) {
            return false;
        }

        return true;
    }

    public function ttl(): int
    {
        return app(RajaOngkirService::class)->shippingQuoteTtl();
    }

    private function cacheKey(string $token): string
    {
        return "shipping_quotes:{$token}";
    }
}
