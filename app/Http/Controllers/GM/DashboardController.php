<?php

namespace App\Http\Controllers\GM;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $pagePath = explode('/', 'GM/DASHBOARD');
        $pageName = 'Dasbor';

        $availableYears = Payment::where('status', 'terverifikasi')
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

        $availableMonths = Payment::where('status', 'terverifikasi')
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

        $totalRevenue = Payment::where('status', 'terverifikasi')
            ->whereYear('verified_at', $selectedYear)
            ->sum('amount');

        $monthlyRevenue = Payment::where('status', 'terverifikasi')
            ->whereYear('verified_at', $selectedYear)
            ->whereMonth('verified_at', $selectedMonth)
            ->sum('amount');

        $totalOrders = Order::count();
        $selesaiOrders = Order::where('status', 'selesai')->count();
        $diprosesOrders = Order::whereIn('status', ['pembayaran_dikonfirmasi', 'diproses', 'dikirim'])->count();
        $totalCustomers = User::whereHas('roles', fn ($query) => $query->where('name', 'pelanggan'))->count();
        $activeProducts = Product::where('is_active', true)->count();
        $verifiedPayments = Payment::where('status', 'terverifikasi')->count();

        $monthlySales = collect(range(5, 0))->map(function (int $monthsAgo) {
            $date = now()->subMonths($monthsAgo);

            return [
                'label' => $date->format('M'),
                'amount' => Payment::where('status', 'terverifikasi')
                    ->whereYear('verified_at', $date->year)
                    ->whereMonth('verified_at', $date->month)
                    ->sum('amount'),
            ];
        });

        $maxMonthlySales = max((float) $monthlySales->max('amount'), 1);

        $orderStatusCounts = Order::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $topProducts = OrderItem::query()
            ->whereHas('order', fn($q) => $q->whereYear('created_at', $selectedYear))
            ->select('product_name')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->selectRaw('SUM(subtotal) as total_sales')
            ->groupBy('product_name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();

        $topCustomers = Order::query()
            ->when($selectedYear, fn($q) => $q->whereYear('created_at', $selectedYear))
            ->when($selectedMonth, fn($q) => $q->whereMonth('created_at', $selectedMonth))
            ->select('user_id')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('SUM(total_amount) as total_spent')
            ->selectRaw('AVG(total_amount) as avg_order_value')
            ->groupBy('user_id')
            ->orderByDesc('order_count')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->with('user')
            ->get();

        $recentOrders = Order::with(['user', 'payment'])
            ->withCount('items')
            ->latest()
            ->limit(5)
            ->get();

        return view('gm.dashboard', compact(
            'pagePath',
            'pageName',
            'totalRevenue',
            'monthlyRevenue',
            'availableYears',
            'selectedYear',
            'availableMonths',
            'selectedMonth',
            'totalOrders',
            'selesaiOrders',
            'diprosesOrders',
            'totalCustomers',
            'activeProducts',
            'verifiedPayments',
            'monthlySales',
            'maxMonthlySales',
            'orderStatusCounts',
            'topProducts',
            'topCustomers',
            'recentOrders'
        ));
    }
}
