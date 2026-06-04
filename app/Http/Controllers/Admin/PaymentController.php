<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(\App\Http\Requests\Admin\PaymentSearchRequest $request)
    {
        $pagePath = 'ADMIN/PAYMENTS';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Pembayaran';

        $statuses = ['menunggu', 'terverifikasi', 'ditolak'];

        $payments = Payment::with(['order.user', 'paymentMethod', 'verifiedBy'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->q, function ($q) use ($request) {
                $kw = $request->q;
                $q->where(function ($sub) use ($kw) {
                    $sub->whereHas('order', fn($o) => $o->where('order_number', 'like', "%{$kw}%"))
                        ->orWhereHas('order.user', fn($u) => $u->where('name', 'like', "%{$kw}%"));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.payments.index', compact('pagePath', 'pageName', 'payments', 'statuses'));
    }

    public function verify(Payment $payment)
    {
        if ($payment->status !== 'menunggu') {
            return redirect()->route('admin.payments.index')->with('error', 'Payment sudah diproses.');
        }

        $payment->update([
            'status' => 'terverifikasi',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'rejection_reason' => null,
        ]);

        if ($payment->order) {
            $payment->order->update(['status' => 'pembayaran_dikonfirmasi']);
        }

        return redirect()->route('admin.payments.index')->with('success', 'Pembayaran berhasil diverifikasi dan status order diperbarui.');
    }

    public function reject(Request $request, Payment $payment)
    {
        if ($payment->status !== 'menunggu') {
            return redirect()->route('admin.payments.index')->with('error', 'Payment sudah diproses.');
        }

        $payment->update([
            'status' => 'ditolak',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        if ($payment->order) {
            $payment->order->update(['status' => 'dibatalkan']);
        }

        return redirect()->route('admin.payments.index')->with('success', 'Pembayaran ditolak dan status order diubah menjadi dibatalkan.');
    }
}
