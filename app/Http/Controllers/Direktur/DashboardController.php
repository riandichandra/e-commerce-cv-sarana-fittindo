<?php

namespace App\Http\Controllers\Direktur;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $pagePath = explode('/', 'DIREKTUR/DASHBOARD');
        $pageName = 'Monitoring Dashboard';

        $totalRevenue = Payment::where('status', 'verified')->sum('amount');
        $monthlyRevenue = Payment::where('status', 'verified')
            ->whereYear('verified_at', now()->year)
            ->whereMonth('verified_at', now()->month)
            ->sum('amount');
        $totalOrders = Order::count();
        $completedOrders = Order::where('status', 'completed')->count();
        $activeOrders = Order::whereIn('status', ['waiting_payment_confirmation', 'payment_confirmed', 'processing', 'shipped'])->count();
        $totalCustomers = User::role('pelanggan')->count();
        $activeProducts = Product::where('is_active', true)->count();
        $runningPromotions = Promotion::where('is_active', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->count();

        $monthlyRevenueTrend = collect(range(5, 0))->map(function (int $monthsAgo) {
            $date = now()->subMonths($monthsAgo);

            return [
                'label' => $date->format('M'),
                'amount' => Payment::where('status', 'verified')
                    ->whereYear('verified_at', $date->year)
                    ->whereMonth('verified_at', $date->month)
                    ->sum('amount'),
            ];
        });

        $maxMonthlyRevenue = max((float) $monthlyRevenueTrend->max('amount'), 1);

        $orderStatusCounts = Order::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $topProducts = OrderItem::select('product_name')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->selectRaw('SUM(subtotal) as total_sales')
            ->groupBy('product_name')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get();

        $recentOrders = Order::with(['user', 'payment'])
            ->withCount('items')
            ->latest()
            ->limit(6)
            ->get();

        $activePromotions = Promotion::with('createdBy')
            ->where('is_active', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->latest()
            ->limit(5)
            ->get();

        return view('direktur.dashboard', compact(
            'pagePath',
            'pageName',
            'totalRevenue',
            'monthlyRevenue',
            'totalOrders',
            'completedOrders',
            'activeOrders',
            'totalCustomers',
            'activeProducts',
            'runningPromotions',
            'monthlyRevenueTrend',
            'maxMonthlyRevenue',
            'orderStatusCounts',
            'topProducts',
            'recentOrders',
            'activePromotions'
        ));
    }
}
