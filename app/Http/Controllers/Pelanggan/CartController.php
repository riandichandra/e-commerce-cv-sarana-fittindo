<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

    public function checkoutForm(Request $request)
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
            ->with(['province', 'regency', 'district', 'village'])
            ->orderByDesc('is_main')
            ->latest()
            ->get();

        return view('pelanggan.cart.checkout', compact('cart', 'paymentMethods', 'addresses'));
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'shipping_address_id' => [
                'nullable',
                Rule::exists('user_addresses', 'id')->where('user_id', $request->user()->id),
            ],
            'shipping_name' => ['required_without:shipping_address_id', 'nullable', 'string', 'max:100'],
            'shipping_phone' => ['required_without:shipping_address_id', 'nullable', 'string', 'max:20'],
            'shipping_address' => ['required_without:shipping_address_id', 'nullable', 'string'],
            'shipping_province' => ['required_without:shipping_address_id', 'nullable', 'string', 'max:100'],
            'shipping_city' => ['required_without:shipping_address_id', 'nullable', 'string', 'max:100'],
            'shipping_district' => ['required_without:shipping_address_id', 'nullable', 'string', 'max:100'],
            'shipping_village' => ['nullable', 'string', 'max:100'],
            'shipping_postal_code' => ['nullable', 'string', 'max:10'],
            'payment_method_id' => [
                'required',
                Rule::exists('payment_methods', 'id')->where('is_active', true),
            ],
            'notes' => ['nullable', 'string'],
        ]);

        $cart = Cart::getForUser($request->user());
        $cart->load('items.product');

        if ($cart->items->isEmpty()) {
            return redirect()
                ->route('pelanggan.cart.index')
                ->with('error', 'Keranjang masih kosong.');
        }

        if (! empty($validated['shipping_address_id'])) {
            $selectedAddress = $request->user()
                ->addresses()
                ->with(['province', 'regency', 'district', 'village'])
                ->findOrFail($validated['shipping_address_id']);

            $validated = [
                ...$validated,
                'shipping_name' => $selectedAddress->receiver_name,
                'shipping_phone' => $selectedAddress->receiver_phone,
                'shipping_address' => $selectedAddress->full_address,
                'shipping_province' => $selectedAddress->province?->name,
                'shipping_city' => $selectedAddress->regency?->name,
                'shipping_district' => $selectedAddress->district?->name,
                'shipping_village' => $selectedAddress->village?->name,
                'shipping_postal_code' => $selectedAddress->postal_code,
            ];
        }

        try {
            $order = DB::transaction(function () use ($request, $cart, $validated) {
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

                $subtotal = $items->sum(function (CartItem $item) use ($products) {
                    $product = $products->get($item->product_id);

                    return $product->price * $item->quantity;
                });

                $user = $request->user();
                $order = Order::create([
                    'user_id' => $user->id,
                    'order_number' => Order::generateOrderNumber(),
                    'status' => 'pending_payment',
                    'subtotal' => $subtotal,
                    'discount_amount' => 0,
                    'shipping_cost' => 0,
                    'total_amount' => $subtotal,
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
                    'amount' => $subtotal,
                    'status' => 'pending',
                    'notes' => 'Menunggu konfirmasi pembayaran pelanggan.',
                ]);

                $cart->items()->delete();

                return $order;
            });
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('pelanggan.products.index')
            ->with('success', 'Checkout berhasil. Order ' . $order->order_number . ' sudah tersimpan.');
    }
}
