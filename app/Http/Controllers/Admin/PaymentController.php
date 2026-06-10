<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $isRejected = DB::transaction(function () use ($payment, $validated): bool {
            $lockedPayment = Payment::whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status !== 'menunggu') {
                return false;
            }

            $order = $lockedPayment->order()
                ->lockForUpdate()
                ->first();

            $lockedPayment->update([
                'status' => 'ditolak',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
            ]);

            if ($order) {
                if (! $order->stock_restored_at) {
                    $items = $order->items()->get();
                    $products = Product::withTrashed()
                        ->whereIn('id', $items->pluck('product_id')->filter()->unique())
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');

                    foreach ($items as $item) {
                        $product = $products->get($item->product_id);

                        if (! $product) {
                            continue;
                        }

                        $product->restoreStock((int) $item->quantity);
                        $product->save();
                    }

                    $order->stock_restored_at = now();
                    $order->stock_restored_by = auth()->id();
                }

                $order->status = 'dibatalkan';
                $order->cancelled_by = auth()->id();
                $order->cancellation_reason = 'Pembayaran ditolak: ' . $validated['rejection_reason'];
                $order->cancelled_at = now();
                $order->save();
            }

            return true;
        });

        if (! $isRejected) {
            return redirect()->route('admin.payments.index')->with('error', 'Payment sudah diproses.');
        }

        return redirect()->route('admin.payments.index')->with('success', 'Pembayaran ditolak, stok dikembalikan, dan status order diubah menjadi dibatalkan.');
    }
}
