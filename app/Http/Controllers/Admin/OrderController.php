<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(\App\Http\Requests\Admin\OrderSearchRequest $request)
    {
        $pagePath = 'ADMIN/ORDERS';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Pesanan';

        $statuses = [
            'menunggu_konfirmasi_ongkir',
            'belum_dibayar',
            'menunggu_verifikasi_pembayaran',
            'pembayaran_dikonfirmasi',
            'diproses',
            'dikirim',
            'selesai',
            'dibatalkan',
        ];

        $orders = Order::with(['user', 'paymentMethod', 'payment', 'delivery'])
            ->withCount('items')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->q, function ($q) use ($request) {
                $keyword = $request->q;
                $q->where(function ($sq) use ($keyword) {
                    $sq->where('order_number', 'like', "%{$keyword}%")
                        ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$keyword}%"));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.orders.index', compact('pagePath', 'pageName', 'orders', 'statuses'));
    }

    public function show(Order $order)
    {
        $pagePath = 'ADMIN/ORDERS/DETAIL';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Detail Pesanan';

        $order->load(['user', 'paymentMethod', 'payment', 'delivery']);
        $items = $order->items()
            ->with('product')
            ->paginate(10, ['*'], 'items_page')
            ->withQueryString();

        return view('admin.orders.show', compact('pagePath', 'pageName', 'order', 'items'));
    }

    public function update(Request $request, Order $order)
    {
        if (! in_array($order->status, ['pembayaran_dikonfirmasi', 'diproses'])) {
            return redirect()->route('admin.orders.index')->with('error', 'Status order tidak dapat diubah dari status saat ini.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['diproses', 'dikirim'])],
        ]);

        $order->update(['status' => $validated['status']]);

        return redirect()->route('admin.orders.index')->with('success', 'Status order berhasil diperbarui menjadi ' . ucwords(str_replace('_', ' ', $validated['status'])) . '.');
    }

    public function pendingShippingCosts()
    {
        $pagePath = 'ADMIN/PENDING-SHIPPING';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Pesanan Menunggu Konfirmasi Ongkir';

        $orders = Order::with(['user', 'paymentMethod', 'items'])
            ->withCount('items')
            ->where(function ($q) {
                $q->where('status', 'menunggu_konfirmasi_ongkir')
                    ->orWhere('shipping_cost_status', 'waiting_admin');
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.pending-shipping-costs.index', compact('pagePath', 'pageName', 'orders'));
    }

    public function updateShippingCost(Request $request, Order $order)
    {
        if (! $order->isWaitingForShippingCost()) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->with('error', 'Ongkos kirim pesanan ini sudah final.');
        }

        $validated = $request->validate([
            'shipping_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $shippingCost = (float) $validated['shipping_cost'];
        $totalAmount = max((float) $order->subtotal - (float) $order->discount_amount, 0) + $shippingCost;

        $order->update([
            'shipping_cost' => $shippingCost,
            'shipping_cost_status' => 'confirmed',
            'shipping_cost_confirmed_at' => now(),
            'shipping_cost_confirmed_by' => auth()->id(),
            'total_amount' => $totalAmount,
            'status' => 'belum_dibayar',
        ]);

        if ($order->payment) {
            $order->payment->update([
                'amount' => $totalAmount,
                'notes' => 'Menunggu pembayaran pelanggan setelah ongkos kirim dikonfirmasi.',
            ]);
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Ongkos kirim berhasil dikonfirmasi.');
    }
}
