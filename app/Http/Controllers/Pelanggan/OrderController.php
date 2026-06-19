<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::nonDummy()
            ->with(['items', 'payment', 'paymentMethod'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        $unpaidOrders = $orders->filter(function (Order $order) {
            return $order->payment?->status !== 'terverifikasi';
        });

        $paidOrders = $orders->filter(function (Order $order) {
            return $order->payment?->status === 'terverifikasi';
        });

        return view('pelanggan.orders.index', compact('unpaidOrders', 'paidOrders'));
    }

    public function history(Request $request)
    {
        $query = Order::nonDummy()
            ->with(['items', 'payment', 'paymentMethod'])
            ->where('user_id', $request->user()->id);

        // Search by order number
        if ($request->filled('search')) {
            $query->where('order_number', 'like', '%' . $request->input('search') . '%');
        }

        // Filter by status
        if ($request->filled('status') && $request->input('status') !== '') {
            $query->where('status', $request->input('status'));
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $orders = $query->latest()->paginate(10)->withQueryString();

        return view('pelanggan.orders.history', compact('orders'));
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $order->load(['items', 'payment', 'paymentMethod']);

        return view('pelanggan.orders.show', compact('order'));
    }

    public function paymentProofForm(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $order->load(['items', 'payment', 'paymentMethod']);

        if ($order->status === 'dibatalkan' || $order->payment?->status === 'ditolak') {
            return redirect()
                ->route('pelanggan.orders.show', $order)
                ->with('error', 'Pesanan sudah dibatalkan karena pembayaran ditolak. Silakan buat pesanan baru.');
        }

        if ($order->isWaitingForShippingCost()) {
            return redirect()
                ->route('pelanggan.orders.show', $order)
                ->with('error', 'Ongkos kirim belum dikonfirmasi admin. Silakan tunggu total pembayaran final.');
        }

        if ($order->payment?->status === 'terverifikasi') {
            return redirect()
                ->route('pelanggan.orders.index')
                ->with('error', 'Pesanan ini sudah dibayar.');
        }

        return view('pelanggan.orders.payment-proof', compact('order'));
    }

    public function complete(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if ($order->status !== 'dikirim') {
            return redirect()
                ->route('pelanggan.orders.index')
                ->with('error', 'Pesanan hanya dapat diselesaikan setelah dikirim.');
        }

        $validated = $request->validate([
            'received_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $receivedImagePath = $request->file('received_image')->store('received-product-photos', 'public');

        if ($order->received_image) {
            Storage::disk('public')->delete($order->received_image);
        }

        $order->update([
            'status' => 'selesai',
            'received_image' => $receivedImagePath,
            'completed_at' => now(),
            'completion_source' => 'customer',
            'completion_notes' => 'Diselesaikan manual oleh pelanggan.',
        ]);

        return redirect()
            ->route('pelanggan.orders.index')
            ->with('success', 'Pesanan berhasil ditandai selesai dan foto produk diterima tersimpan.');
    }

    public function cancel(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if ($order->status !== 'dikirim') {
            return redirect()
                ->route('pelanggan.orders.index')
                ->with('error', 'Pengembalian hanya dapat diajukan saat pesanan sudah dikirim.');
        }

        $order->update([
            'status' => 'dibatalkan',
            'cancelled_by' => $request->user()->id,
            'cancellation_reason' => 'Pengembalian diajukan oleh pelanggan.',
            'cancelled_at' => now(),
        ]);

        return redirect()
            ->route('pelanggan.orders.index')
            ->with('success', 'Pengembalian pesanan berhasil diajukan.');
    }

    public function uploadPaymentProof(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $order->load('payment');

        if ($order->status === 'dibatalkan' || $order->payment?->status === 'ditolak') {
            return redirect()
                ->route('pelanggan.orders.show', $order)
                ->with('error', 'Pesanan sudah dibatalkan karena pembayaran ditolak. Silakan buat pesanan baru.');
        }

        if ($order->isWaitingForShippingCost()) {
            return redirect()
                ->route('pelanggan.orders.show', $order)
                ->with('error', 'Ongkos kirim belum dikonfirmasi admin. Silakan tunggu total pembayaran final.');
        }

        if ($order->payment?->status === 'terverifikasi') {
            return redirect()
                ->route('pelanggan.orders.index')
                ->with('error', 'Pesanan ini sudah dibayar.');
        }

        $validated = $request->validate([
            'sender_name' => ['required', 'string', 'max:100'],
            'transfer_date' => ['required', 'date', 'before_or_equal:today'],
            'proof_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'notes' => ['nullable', 'string'],
        ]);

        $proofPath = $request->file('proof_image')->store('payment-proofs', 'public');
        $payment = $order->payment;

        if ($payment?->proof_image) {
            Storage::disk('public')->delete($payment->proof_image);
        }

        Payment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'payment_method_id' => $order->payment_method_id,
                'amount' => $order->total_amount,
                'proof_image' => $proofPath,
                'transfer_date' => $validated['transfer_date'],
                'sender_name' => $validated['sender_name'],
                'status' => 'menunggu',
                'verified_by' => null,
                'verified_at' => null,
                'rejection_reason' => null,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        $order->update(['status' => 'menunggu_verifikasi_pembayaran']);

        return redirect()
            ->route('pelanggan.orders.index')
            ->with('success', 'Bukti pembayaran berhasil diupload dan menunggu verifikasi admin.');
    }
}
