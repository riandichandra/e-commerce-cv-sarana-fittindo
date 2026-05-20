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
        $orders = Order::with(['items', 'payment', 'paymentMethod'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        $unpaidOrders = $orders->filter(function (Order $order) {
            return $order->payment?->status !== 'verified';
        });

        $paidOrders = $orders->filter(function (Order $order) {
            return $order->payment?->status === 'verified';
        });

        return view('pelanggan.orders.index', compact('unpaidOrders', 'paidOrders'));
    }

    public function paymentProofForm(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $order->load(['items', 'payment', 'paymentMethod']);

        if ($order->payment?->status === 'verified') {
            return redirect()
                ->route('pelanggan.orders.index')
                ->with('error', 'Pesanan ini sudah dibayar.');
        }

        return view('pelanggan.orders.payment-proof', compact('order'));
    }

    public function uploadPaymentProof(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $order->load('payment');

        if ($order->payment?->status === 'verified') {
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
                'status' => 'pending',
                'verified_by' => null,
                'verified_at' => null,
                'rejection_reason' => null,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        $order->update(['status' => 'waiting_payment_confirmation']);

        return redirect()
            ->route('pelanggan.orders.index')
            ->with('success', 'Bukti pembayaran berhasil diupload dan menunggu verifikasi admin.');
    }
}
