<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Promotion;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $pagePath = explode('/', 'MARKETING/DASHBOARD');
        $pageName = 'Dasbor';

        $totalCustomers = User::whereHas('roles', fn ($query) => $query->where('name', 'pelanggan'))->count();
        $activeCustomers = User::whereHas('roles', fn ($query) => $query->where('name', 'pelanggan'))->where('is_active', true)->count();
        $totalPromotions = Promotion::count();
        $activePromotions = Promotion::where('is_active', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->count();
        $upcomingPromotions = Promotion::whereDate('start_date', '>', now())->count();
        $selesaiOrders = Order::where('status', 'selesai')->count();
        $totalDiscountGiven = Order::sum('discount_amount');

        $recentPromotions = Promotion::with('createdBy')
            ->latest()
            ->limit(5)
            ->get();

        $recentCustomers = User::whereHas('roles', fn ($query) => $query->where('name', 'pelanggan'))
            ->latest()
            ->limit(5)
            ->get();

        $monthlyCustomers = collect(range(5, 0))->map(function (int $monthsAgo) {
            $date = now()->subMonths($monthsAgo);

            return [
                'label' => $date->format('M'),
                'count' => User::whereHas('roles', fn ($query) => $query->where('name', 'pelanggan'))
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
            ];
        });

        $maxMonthlyCustomers = max((int) $monthlyCustomers->max('count'), 1);

        return view('marketing.dashboard', compact(
            'pagePath',
            'pageName',
            'totalCustomers',
            'activeCustomers',
            'totalPromotions',
            'activePromotions',
            'upcomingPromotions',
            'selesaiOrders',
            'totalDiscountGiven',
            'recentPromotions',
            'recentCustomers',
            'monthlyCustomers',
            'maxMonthlyCustomers'
        ));
    }
}
