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
        $pageName = 'Orders';

        $statuses = [
            'pending_payment',
            'waiting_payment_confirmation',
            'payment_confirmed',
            'processing',
            'shipped',
            'completed',
            'cancelled',
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
        $pageName = 'Order Detail';

        $order->load(['user', 'paymentMethod', 'payment', 'delivery', 'items.product']);

        return view('admin.orders.show', compact('pagePath', 'pageName', 'order'));
    }

    public function update(Request $request, Order $order)
    {
        if (! in_array($order->status, ['payment_confirmed', 'processing'])) {
            return redirect()->route('admin.orders.index')->with('error', 'Status order tidak dapat diubah dari status saat ini.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['processing', 'shipped'])],
        ]);

        $order->update(['status' => $validated['status']]);

        return redirect()->route('admin.orders.index')->with('success', 'Status order berhasil diperbarui menjadi ' . ucwords(str_replace('_', ' ', $validated['status'])) . '.');
    }
}
