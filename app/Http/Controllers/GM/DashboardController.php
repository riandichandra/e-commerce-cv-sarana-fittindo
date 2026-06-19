<?php

namespace App\Http\Controllers\GM;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $pagePath = explode('/', 'GM/DASHBOARD');
        $pageName = 'Dasbor';
        $orderStatuses = [
            'menunggu_konfirmasi_ongkir',
            'belum_dibayar',
            'menunggu_verifikasi_pembayaran',
            'pembayaran_dikonfirmasi',
            'diproses',
            'dikirim',
            'selesai',
            'dibatalkan',
        ];
        $cardOrderFilters = $this->cardOrderFilters($request, $orderStatuses);
        $cardOrderStartDate = Carbon::parse($cardOrderFilters['card_order_start_date'])->startOfDay();
        $cardOrderEndDate = Carbon::parse($cardOrderFilters['card_order_end_date'])->endOfDay();
        $cardOrderStatusLabel = $this->orderStatusLabel($cardOrderFilters['card_order_status']);
        $topFilters = $this->topPeriodFilters($request);
        $topStartDate = Carbon::parse($topFilters['top_start_date'])->startOfDay();
        $topEndDate = Carbon::parse($topFilters['top_end_date'])->endOfDay();

        $availableYears = Payment::nonDummyOrder()
            ->where('status', 'terverifikasi')
            ->whereNotNull('verified_at')
            ->orderBy('verified_at')
            ->pluck('verified_at')
            ->map(fn ($date) => (int) $date->year)
            ->unique()
            ->values()
            ->sortDesc()
            ->values();

        if ($availableYears->isEmpty()) {
            $availableYears = collect([now()->year]);
        }

        $selectedYear = (int) request('year', now()->year);
        if (! $availableYears->contains($selectedYear)) {
            $selectedYear = $availableYears->first();
        }

        $availableMonths = Payment::nonDummyOrder()
            ->where('status', 'terverifikasi')
            ->whereYear('verified_at', $selectedYear)
            ->whereNotNull('verified_at')
            ->orderBy('verified_at')
            ->pluck('verified_at')
            ->map(fn ($date) => (int) $date->month)
            ->unique()
            ->values();

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

        $totalRevenue = Payment::nonDummyOrder()
            ->where('status', 'terverifikasi')
            ->whereYear('verified_at', $selectedYear)
            ->sum('amount');

        $monthlyRevenue = Payment::nonDummyOrder()
            ->where('status', 'terverifikasi')
            ->whereYear('verified_at', $selectedYear)
            ->whereMonth('verified_at', $selectedMonth)
            ->sum('amount');

        $cardOrdersQuery = Order::nonDummy()
            ->whereBetween('created_at', [$cardOrderStartDate, $cardOrderEndDate])
            ->when($cardOrderFilters['card_order_status'], fn ($query, $status) => $query->where('status', $status));

        $totalOrders = (clone $cardOrdersQuery)->count();
        $totalCustomers = User::whereHas('roles', fn ($query) => $query->where('name', 'pelanggan'))->count();
        $activeProducts = Product::where('is_active', true)->count();
        $verifiedPayments = Payment::nonDummyOrder()->where('status', 'terverifikasi')->count();

        $monthlySales = collect(range(5, 0))->map(function (int $monthsAgo) {
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

        $maxMonthlySales = max((float) $monthlySales->max('amount'), 1);

        $orderStatusCounts = Order::nonDummy()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $paginationQuery = $request->except([
            'card_order_start_date',
            'card_order_end_date',
            'card_order_status',
            'order_period',
            'order_start_date',
            'order_end_date',
            'order_status',
        ]);

        $topProducts = OrderItem::nonDummyOrder()
            ->whereHas('order', fn ($q) => $q->whereBetween('created_at', [$topStartDate, $topEndDate]))
            ->select('product_name')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->selectRaw('SUM(subtotal) as total_sales')
            ->groupBy('product_name')
            ->orderByDesc('total_quantity')
            ->orderByDesc('total_sales')
            ->paginate(5, ['*'], 'top_products_page')
            ->appends($paginationQuery);

        $topCustomers = Order::nonDummy()
            ->whereBetween('created_at', [$topStartDate, $topEndDate])
            ->select('user_id')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('SUM(total_amount) as total_spent')
            ->selectRaw('AVG(total_amount) as avg_order_value')
            ->groupBy('user_id')
            ->orderByDesc('order_count')
            ->orderByDesc('total_spent')
            ->with('user')
            ->paginate(5, ['*'], 'top_customers_page')
            ->appends($paginationQuery);

        $detailOrders = Order::nonDummy()
            ->with(['user', 'payment'])
            ->withCount('items')
            ->latest()
            ->paginate(5, ['*'], 'detail_orders_page')
            ->appends($paginationQuery);

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
            'totalCustomers',
            'activeProducts',
            'verifiedPayments',
            'monthlySales',
            'maxMonthlySales',
            'orderStatusCounts',
            'orderStatuses',
            'cardOrderFilters',
            'cardOrderStartDate',
            'cardOrderEndDate',
            'cardOrderStatusLabel',
            'topFilters',
            'topStartDate',
            'topEndDate',
            'topProducts',
            'topCustomers',
            'detailOrders'
        ));
    }

    private function cardOrderFilters(Request $request, array $orderStatuses): array
    {
        $validated = $request->validate([
            'card_order_start_date' => ['nullable', 'date'],
            'card_order_end_date' => ['nullable', 'date', 'after_or_equal:card_order_start_date'],
            'card_order_status' => ['nullable', 'string', Rule::in($orderStatuses)],
        ]);

        return [
            'card_order_start_date' => $validated['card_order_start_date'] ?? now()->startOfMonth()->toDateString(),
            'card_order_end_date' => $validated['card_order_end_date'] ?? now()->endOfMonth()->toDateString(),
            'card_order_status' => $validated['card_order_status'] ?? null,
        ];
    }

    private function topPeriodFilters(Request $request): array
    {
        $validated = $request->validate([
            'top_start_date' => ['nullable', 'date'],
            'top_end_date' => ['nullable', 'date', 'after_or_equal:top_start_date'],
        ]);

        return [
            'top_start_date' => $validated['top_start_date'] ?? now()->startOfMonth()->toDateString(),
            'top_end_date' => $validated['top_end_date'] ?? now()->toDateString(),
        ];
    }

    private function orderStatusLabel(?string $status): string
    {
        if (! $status) {
            return 'semua status';
        }

        return strtolower(Order::make(['status' => $status])->status_label);
    }
}
