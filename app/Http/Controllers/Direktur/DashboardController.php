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

        $availableYears = Payment::where('status', 'verified')
            ->whereNotNull('verified_at')
            ->selectRaw('YEAR(verified_at) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($year) => (int) $year);

        if ($availableYears->isEmpty()) {
            $availableYears = collect([now()->year]);
        }

        $selectedYear = (int) request('year', now()->year);
        if (! $availableYears->contains($selectedYear)) {
            $selectedYear = $availableYears->first();
        }

        $availableMonths = Payment::where('status', 'verified')
            ->whereYear('verified_at', $selectedYear)
            ->whereNotNull('verified_at')
            ->selectRaw('MONTH(verified_at) as month')
            ->distinct()
            ->orderBy('month')
            ->pluck('month')
            ->map(fn ($month) => (int) $month);

        if ($availableMonths->isEmpty()) {
            $availableMonths = collect(range(1, 12));
        }

        $selectedMonth = (int) request('month', $selectedYear === now()->year ? now()->month : $availableMonths->last());
        if (! $availableMonths->contains($selectedMonth)) {
            if ($selectedYear === now()->year) {
                $selectedMonth = now()->month;
            } elseif ($availableMonths->isNotEmpty()) {
                $selectedMonth = $availableMonths->last();
            } else {
                $selectedMonth = 1;
            }
        }

        $totalRevenue = Payment::where('status', 'verified')
            ->whereYear('verified_at', $selectedYear)
            ->sum('amount');

        $monthlyRevenue = Payment::where('status', 'verified')
            ->whereYear('verified_at', $selectedYear)
            ->whereMonth('verified_at', $selectedMonth)
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

        $topProducts = OrderItem::query()
            ->whereHas('order', fn($q) => $q->whereYear('created_at', $selectedYear))
            ->select('product_name')
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
            'availableYears',
            'selectedYear',
            'availableMonths',
            'selectedMonth',
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
