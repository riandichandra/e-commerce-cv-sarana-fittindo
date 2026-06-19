<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $pagePath = 'Admin/Dashboard';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Dasbor';

        $totalProducts = Product::count();
        $availableProducts = Product::where('is_active', true)
            ->where('status', Product::STATUS_AVAILABLE)
            ->where('stock', '>', 0)
            ->count();
        $totalOrders = Order::nonDummy()->count();
        $waitingConfirmation = Order::nonDummy()->where('status', 'menunggu_verifikasi_pembayaran')->count();
        $pendingPayment = Order::nonDummy()->where('status', 'belum_dibayar')->count();
        $diprosesOrders = Order::nonDummy()->whereIn('status', ['pembayaran_dikonfirmasi', 'diproses', 'dikirim'])->count();
        $totalCustomers = User::whereHas('roles', fn ($query) => $query->where('name', 'pelanggan'))->count();
        $verifiedRevenue = Payment::nonDummyOrder()->where('status', 'terverifikasi')->sum('amount');
        $pendingPaymentAmount = Payment::nonDummyOrder()->where('status', 'menunggu')->sum('amount');

        $recentOrders = Order::nonDummy()
            ->with(['user', 'payment', 'paymentMethod'])
            ->withCount('items')
            ->latest()
            ->paginate(5, ['*'], 'recent_orders_page')
            ->withQueryString();

        $recentPayments = Payment::nonDummyOrder()
            ->with(['order.user', 'paymentMethod'])
            ->latest()
            ->paginate(5, ['*'], 'recent_payments_page')
            ->withQueryString();

        $paymentStatusCounts = [
            'menunggu' => Payment::nonDummyOrder()->where('status', 'menunggu')->count(),
            'terverifikasi' => Payment::nonDummyOrder()->where('status', 'terverifikasi')->count(),
            'ditolak' => Payment::nonDummyOrder()->where('status', 'ditolak')->count(),
        ];

        $monthlyRevenue = collect(range(5, 0))->map(function (int $monthsAgo) {
            $date = now()->subMonths($monthsAgo);

            return [
                'label' => $date->format('M'),
                'amount' => Payment::nonDummyOrder()
                    ->where('status', 'terverifikasi')
                    ->whereYear('verified_at', $date->year)
                    ->whereMonth('verified_at', $date->month)
                    ->sum('amount'),
            ];
        });

        $maxMonthlyRevenue = max((float) $monthlyRevenue->max('amount'), 1);

        return view('admin.dashboard', compact(
            'pagePath',
            'pageName',
            'totalProducts',
            'availableProducts',
            'totalOrders',
            'waitingConfirmation',
            'pendingPayment',
            'diprosesOrders',
            'totalCustomers',
            'verifiedRevenue',
            'pendingPaymentAmount',
            'recentOrders',
            'recentPayments',
            'paymentStatusCounts',
            'monthlyRevenue',
            'maxMonthlyRevenue'
        ));
    }
}
