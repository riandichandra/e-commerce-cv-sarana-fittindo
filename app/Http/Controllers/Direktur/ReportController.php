<?php

namespace App\Http\Controllers\Direktur;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function strategic(Request $request)
    {
        $pagePath = explode('/', 'DIREKTUR/LAPORAN');
        $pageName = 'Laporan Monitoring';
        $filters = $this->filters($request);

        $ordersQuery = Order::with(['user', 'payment', 'items'])
            ->when($filters['start_date'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['end_date'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status));

        $orders = (clone $ordersQuery)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total_orders' => (clone $ordersQuery)->count(),
            'total_sales' => (clone $ordersQuery)->sum('total_amount'),
            'total_discount' => (clone $ordersQuery)->sum('discount_amount'),
            'verified_revenue' => Payment::where('status', 'verified')
                ->when($filters['start_date'], fn ($query, $date) => $query->whereDate('verified_at', '>=', $date))
                ->when($filters['end_date'], fn ($query, $date) => $query->whereDate('verified_at', '<=', $date))
                ->sum('amount'),
        ];

        $topProducts = OrderItem::select('product_name')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->selectRaw('SUM(subtotal) as total_sales')
            ->groupBy('product_name')
            ->orderByDesc('total_sales')
            ->limit(8)
            ->get();

        $promotionStatus = [
            'running' => Promotion::where('is_active', true)
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->count(),
            'upcoming' => Promotion::where('is_active', true)
                ->whereDate('start_date', '>', now())
                ->count(),
            'inactive' => Promotion::where('is_active', false)->count(),
        ];

        $orderStatusCounts = (clone $ordersQuery)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('direktur.reports.strategic', compact(
            'pagePath',
            'pageName',
            'filters',
            'orders',
            'summary',
            'topProducts',
            'promotionStatus',
            'orderStatusCounts'
        ));
    }

    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'string'],
        ]);

        return [
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'status' => $validated['status'] ?? null,
        ];
    }
}
