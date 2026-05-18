<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        $pagePath = 'ADMIN/PAYMENTS';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Payments';

        $payments = Payment::with(['order.user', 'paymentMethod', 'verifiedBy'])
            ->latest()
            ->paginate(10);

        return view('admin.payments.index', compact('pagePath', 'pageName', 'payments'));
    }

    public function verify(Payment $payment)
    {
        if ($payment->status !== 'pending') {
            return redirect()->route('admin.payments.index')->with('error', 'Payment sudah diproses.');
        }

        $payment->update([
            'status' => 'verified',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'rejection_reason' => null,
        ]);

        if ($payment->order) {
            $payment->order->update(['status' => 'payment_confirmed']);
        }

        return redirect()->route('admin.payments.index')->with('success', 'Pembayaran berhasil diverifikasi dan status order diperbarui.');
    }

    public function reject(Request $request, Payment $payment)
    {
        if ($payment->status !== 'pending') {
            return redirect()->route('admin.payments.index')->with('error', 'Payment sudah diproses.');
        }

        $payment->update([
            'status' => 'rejected',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        if ($payment->order) {
            $payment->order->update(['status' => 'cancelled']);
        }

        return redirect()->route('admin.payments.index')->with('success', 'Pembayaran ditolak dan status order diubah menjadi dibatalkan.');
    }
}
