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
        $pageName = 'Dashboard';

        $totalProducts = Product::count();
        $availableProducts = Product::where('stock', true)->count();
        $totalOrders = Order::count();
        $waitingConfirmation = Order::where('status', 'waiting_payment_confirmation')->count();
        $pendingPayment = Order::where('status', 'pending_payment')->count();
        $processingOrders = Order::whereIn('status', ['payment_confirmed', 'processing', 'shipped'])->count();
        $totalCustomers = User::whereHas('role', fn ($query) => $query->where('name', 'pelanggan'))->count();
        $verifiedRevenue = Payment::where('status', 'verified')->sum('amount');
        $pendingPaymentAmount = Payment::where('status', 'pending')->sum('amount');

        $recentOrders = Order::with(['user', 'payment', 'paymentMethod'])
            ->withCount('items')
            ->latest()
            ->limit(5)
            ->get();

        $recentPayments = Payment::with(['order.user', 'paymentMethod'])
            ->latest()
            ->limit(5)
            ->get();

        $paymentStatusCounts = [
            'pending' => Payment::where('status', 'pending')->count(),
            'verified' => Payment::where('status', 'verified')->count(),
            'rejected' => Payment::where('status', 'rejected')->count(),
        ];

        $monthlyRevenue = collect(range(5, 0))->map(function (int $monthsAgo) {
            $date = now()->subMonths($monthsAgo);

            return [
                'label' => $date->format('M'),
                'amount' => Payment::where('status', 'verified')
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
            'processingOrders',
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
