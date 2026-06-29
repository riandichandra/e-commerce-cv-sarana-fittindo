<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Services\PromotionDiscountService;
use App\Services\RajaOngkirService;
use App\Services\ShippingQuoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = Cart::getForUser($request->user());
        $cart->load(['items.product.images', 'items.product.category']);

        return view('pelanggan.cart.index', compact('cart'));
    }

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        if (! $product->isAvailable()) {
            return back()->with('error', 'Produk sedang tidak tersedia.');
        }

        $quantity = (int) ($validated['quantity'] ?? 1);

        if ($quantity > $product->stock) {
            return back()->with('error', 'Jumlah melebihi stok tersedia.');
        }

        $cart = Cart::getForUser($request->user());
        $item = CartItem::firstOrNew([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        if ($item->exists && $item->quantity + $quantity > $product->stock) {
            return back()->with('error', 'Jumlah melebihi stok tersedia.');
        }

        $item->quantity = ($item->exists ? $item->quantity : 0) + $quantity;
        $item->save();

        return back()->with('success', 'Produk berhasil ditambahkan ke keranjang.');
    }

    public function update(Request $request, CartItem $cartItem)
    {
        abort_unless($cartItem->cart?->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cartItem->load('product');

        if (! $cartItem->product?->isAvailable()) {
            return back()->with('error', 'Produk sedang tidak tersedia.');
        }

        $quantity = (int) $validated['quantity'];

        if ($quantity > $cartItem->product->stock) {
            return back()->with('error', 'Jumlah melebihi stok tersedia.');
        }

        $cartItem->update(['quantity' => $quantity]);

        return back()->with('success', 'Keranjang berhasil diperbarui.');
    }

    public function destroy(Request $request, CartItem $cartItem)
    {
        abort_unless($cartItem->cart?->user_id === $request->user()->id, 403);

        $cartItem->delete();

        return back()->with('success', 'Produk berhasil dihapus dari keranjang.');
    }

    public function checkoutForm(Request $request, PromotionDiscountService $promotionDiscountService, RajaOngkirService $rajaOngkir)
    {
        $cart = Cart::getForUser($request->user());
        $cart->load(['items.product.images', 'items.product.category']);

        if ($cart->items->isEmpty()) {
            return redirect()
                ->route('pelanggan.cart.index')
                ->with('error', 'Keranjang masih kosong.');
        }

        $paymentMethods = PaymentMethod::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('bank_name')
            ->get();

        $addresses = $request->user()
            ->addresses()
            ->orderByDesc('is_main')
            ->latest()
            ->get();

        if ($addresses->isEmpty()) {
            return redirect()
                ->route('profile.edit')
                ->with('error', 'Tambahkan alamat pengiriman di profil sebelum checkout.');
        }

        $discountSummary = $promotionDiscountService->bestForSubtotal((float) $cart->subtotal);
        $hasRajaOngkirConfig = $rajaOngkir->isConfigured();

        return view('pelanggan.cart.checkout', compact('cart', 'paymentMethods', 'addresses', 'discountSummary', 'hasRajaOngkirConfig'));
    }

    public function checkout(Request $request, ShippingQuoteService $shippingQuotes)
    {
        $validated = $this->validateCheckout($request);

        $cart = Cart::getForUser($request->user());
        $cart->load('items.product');

        if ($cart->items->isEmpty()) {
            return redirect()
                ->route('pelanggan.cart.index')
                ->with('error', 'Keranjang masih kosong.');
        }

        $selectedAddress = $request->user()
            ->addresses()
            ->findOrFail($validated['shipping_address_id']);

        $validated = [
            ...$validated,
            'shipping_name' => $selectedAddress->receiver_name,
            'shipping_phone' => $selectedAddress->receiver_phone,
            'shipping_address' => $selectedAddress->full_address,
            'shipping_province' => $selectedAddress->province_display_name,
            'shipping_city' => $selectedAddress->city_display_name,
            'shipping_district' => $selectedAddress->district_display_name,
            'shipping_village' => $selectedAddress->village_display_name,
            'shipping_postal_code' => $selectedAddress->postal_code,
        ];

        $usesAdminFallback = ($validated['shipping_fallback'] ?? null) === 'admin_manual';
        $destinationDistrictId = filled($selectedAddress->district_id) ? (string) $selectedAddress->district_id : null;

        try {
            $currentWeightGram = $shippingQuotes->weightForCart($cart);
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        $shippingQuote = null;

        if (! $usesAdminFallback) {
            if (! filled($destinationDistrictId)) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'shipping_quote_token' => 'Alamat tersimpan perlu diperbarui ke data RajaOngkir sebelum ongkir otomatis dapat dihitung.',
                    ]);
            }

            $shippingQuote = $shippingQuotes->getQuote($validated['shipping_quote_token'] ?? null);

            if (! $shippingQuote || ! $shippingQuotes->matchesCart($shippingQuote, $request->user(), $cart, $currentWeightGram, $destinationDistrictId)) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'shipping_quote_token' => 'Pilihan ongkir sudah tidak valid atau kedaluwarsa. Silakan pilih layanan pengiriman ulang.',
                    ]);
            }
        }

        try {
            $order = DB::transaction(function () use ($request, $cart, $validated, $shippingQuote, $destinationDistrictId) {
                $items = $cart->items;
                $products = Product::whereIn('id', $items->pluck('product_id'))
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($items as $item) {
                    $product = $products->get($item->product_id);

                    if (! $product || ! $product->isAvailable() || $product->stock < $item->quantity) {
                        throw new \RuntimeException('Produk ' . ($product?->name ?? 'tidak tersedia') . ' sedang tidak tersedia.');
                    }
                }

                $lockedWeightGram = $items->sum(function (CartItem $item) use ($products): int {
                    $product = $products->get($item->product_id);

                    if (! $product || (float) $product->weight <= 0) {
                        throw new \RuntimeException('Berat produk ' . ($product?->name ?? 'tidak diketahui') . ' belum valid.');
                    }

                    return (int) ceil((float) $product->weight * (int) $item->quantity);
                });

                if ($shippingQuote && (int) ($shippingQuote['weight_gram'] ?? 0) !== (int) $lockedWeightGram) {
                    throw new \RuntimeException('Pilihan ongkir sudah tidak sesuai dengan berat keranjang terbaru. Silakan pilih layanan pengiriman ulang.');
                }

                $subtotal = $items->sum(function (CartItem $item) use ($products) {
                    $product = $products->get($item->product_id);

                    return $product->price * $item->quantity;
                });

                $discountSummary = app(PromotionDiscountService::class)->bestForSubtotal((float) $subtotal);
                $promotion = $discountSummary['promotion'];
                $discountAmount = (float) $discountSummary['discount_amount'];
                $subtotalAfterDiscount = (float) $discountSummary['subtotal_after_discount'];
                $shippingCost = $shippingQuote ? (float) ($shippingQuote['cost'] ?? 0) : 0;
                $totalAmount = $subtotalAfterDiscount + $shippingCost;
                $shippingCostStatus = $shippingQuote ? 'calculated' : 'waiting_admin';
                $shippingCostSource = $shippingQuote ? 'rajaongkir' : 'admin_manual';
                $orderStatus = $shippingQuote ? 'belum_dibayar' : 'menunggu_konfirmasi_ongkir';

                $user = $request->user();
                $order = Order::create([
                    'user_id' => $user->id,
                    'order_number' => Order::generateOrderNumber(),
                    'status' => $orderStatus,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'promotion_id' => $promotion?->id,
                    'promotion_name' => $promotion?->name,
                    'promotion_type' => $promotion?->type,
                    'promotion_value' => $promotion?->value,
                    'shipping_cost' => $shippingCost,
                    'shipping_cost_status' => $shippingCostStatus,
                    'shipping_cost_source' => $shippingCostSource,
                    'shipping_origin_district_id' => $shippingQuote['origin_district_id'] ?? null,
                    'shipping_destination_district_id' => $shippingQuote['destination_district_id'] ?? $destinationDistrictId,
                    'shipping_weight_gram' => $shippingQuote['weight_gram'] ?? $lockedWeightGram,
                    'shipping_courier_code' => $shippingQuote['courier_code'] ?? null,
                    'shipping_courier_name' => $shippingQuote['courier_name'] ?? null,
                    'shipping_service' => $shippingQuote['service'] ?? null,
                    'shipping_service_description' => $shippingQuote['description'] ?? null,
                    'shipping_etd' => $shippingQuote['etd'] ?? null,
                    'shipping_rate_snapshot' => $shippingQuote['rate_snapshot'] ?? null,
                    'shipping_cost_confirmed_at' => $shippingQuote ? now() : null,
                    'total_amount' => $totalAmount,
                    'payment_method_id' => $validated['payment_method_id'],
                    'shipping_name' => $validated['shipping_name'],
                    'shipping_phone' => $validated['shipping_phone'],
                    'shipping_address' => $validated['shipping_address'],
                    'shipping_province' => $validated['shipping_province'],
                    'shipping_city' => $validated['shipping_city'],
                    'shipping_district' => $validated['shipping_district'],
                    'shipping_village' => $validated['shipping_village'] ?? null,
                    'shipping_postal_code' => $validated['shipping_postal_code'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);

                foreach ($items as $item) {
                    $product = $products->get($item->product_id);
                    $lineSubtotal = $product->price * $item->quantity;

                    $order->items()->create([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_price' => $product->price,
                        'quantity' => $item->quantity,
                        'subtotal' => $lineSubtotal,
                    ]);

                    $product->reduceStock($item->quantity);
                    $product->save();
                }

                Payment::create([
                    'order_id' => $order->id,
                    'payment_method_id' => $validated['payment_method_id'],
                    'amount' => $totalAmount,
                    'status' => 'menunggu',
                    'notes' => $shippingQuote
                        ? 'Menunggu konfirmasi pembayaran pelanggan.'
                        : 'Menunggu konfirmasi ongkos kirim admin.',
                ]);

                $cart->items()->delete();

                return $order;
            });
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if ($shippingQuote) {
            $shippingQuotes->forgetQuote($validated['shipping_quote_token'] ?? null);
        }

        $message = $order->isWaitingForShippingCost()
            ? 'Checkout berhasil. Ongkos kirim untuk alamat Anda akan dikonfirmasi admin sebelum pembayaran.'
            : 'Checkout berhasil. Silakan lakukan pembayaran sesuai total pesanan.';

        return redirect()
            ->route('pelanggan.orders.show', $order)
            ->with('success', $message);
    }

    private function validateCheckout(Request $request): array
    {
        return $request->validate([
            'shipping_address_id' => [
                'required',
                Rule::exists('user_addresses', 'id')->where('user_id', $request->user()->id),
            ],
            'payment_method_id' => [
                'required',
                Rule::exists('payment_methods', 'id')->where('is_active', true),
            ],
            'shipping_quote_token' => ['required_unless:shipping_fallback,admin_manual', 'nullable', 'string', 'max:120'],
            'shipping_fallback' => ['nullable', Rule::in(['admin_manual'])],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
