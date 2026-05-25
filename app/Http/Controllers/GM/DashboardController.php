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
        $pageName = 'Dashboard';

        $totalRevenue = Payment::where('status', 'verified')->sum('amount');
        $monthlyRevenue = Payment::where('status', 'verified')
            ->whereMonth('verified_at', now()->month)
            ->whereYear('verified_at', now()->year)
            ->sum('amount');
        $totalOrders = Order::count();
        $completedOrders = Order::where('status', 'completed')->count();
        $processingOrders = Order::whereIn('status', ['payment_confirmed', 'processing', 'shipped'])->count();
        $totalCustomers = User::role('pelanggan')->count();
        $activeProducts = Product::where('is_active', true)->count();
        $verifiedPayments = Payment::where('status', 'verified')->count();

        $monthlySales = collect(range(5, 0))->map(function (int $monthsAgo) {
            $date = now()->subMonths($monthsAgo);

            return [
                'label' => $date->format('M'),
                'amount' => Payment::where('status', 'verified')
                    ->whereYear('verified_at', $date->year)
                    ->whereMonth('verified_at', $date->month)
                    ->sum('amount'),
            ];
        });

        $maxMonthlySales = max((float) $monthlySales->max('amount'), 1);

        $orderStatusCounts = Order::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $topProducts = OrderItem::select('product_name')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->selectRaw('SUM(subtotal) as total_sales')
            ->groupBy('product_name')
            ->orderByDesc('total_quantity')
            ->limit(5)
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
            'totalOrders',
            'completedOrders',
            'processingOrders',
            'totalCustomers',
            'activeProducts',
            'verifiedPayments',
            'monthlySales',
            'maxMonthlySales',
            'orderStatusCounts',
            'topProducts',
            'recentOrders'
        ));
    }
}
