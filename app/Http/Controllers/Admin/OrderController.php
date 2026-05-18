<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index()
    {
        $pagePath = 'ADMIN/ORDERS';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Orders';

        $orders = Order::with(['user', 'paymentMethod', 'payment', 'delivery'])
            ->withCount('items')
            ->latest()
            ->paginate(10);

        return view('admin.orders.index', compact('pagePath', 'pageName', 'orders'));
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
