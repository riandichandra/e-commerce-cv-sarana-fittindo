<?php

namespace App\Http\Controllers\GM;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $pagePath = explode('/', 'GM/LAPORAN');
        $pageName = 'Laporan';
        $filters = $this->filters($request);
        $ordersQuery = $this->reportQuery($filters);

        $orders = (clone $ordersQuery)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total_orders' => (clone $ordersQuery)->count(),
            'total_revenue' => (clone $ordersQuery)->sum('total_amount'),
            'total_discount' => (clone $ordersQuery)->sum('discount_amount'),
            'verified_revenue' => Payment::where('status', 'terverifikasi')
                ->when($filters['start_date'], fn ($query, $date) => $query->whereDate('verified_at', '>=', $date))
                ->when($filters['end_date'], fn ($query, $date) => $query->whereDate('verified_at', '<=', $date))
                ->sum('amount'),
        ];

        return view('gm.reports.index', compact('pagePath', 'pageName', 'orders', 'filters', 'summary'));
    }

    public function download(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $orders = $this->reportQuery($filters)
            ->latest()
            ->get();

        $filename = 'laporan-gm-'.now()->format('Ymd-His').'.xls';

        return response()->streamDownload(function () use ($orders, $filters) {
            echo view('gm.reports.excel', [
                'orders' => $orders,
                'filters' => $filters,
                'generatedAt' => now(),
            ])->render();
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'string'],
            'payment_status' => ['nullable', 'string'],
        ]);

        return [
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'status' => $validated['status'] ?? null,
            'payment_status' => $validated['payment_status'] ?? null,
        ];
    }

    private function reportQuery(array $filters)
    {
        return Order::with(['user', 'payment.paymentMethod', 'items'])
            ->when($filters['start_date'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['end_date'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->when($filters['payment_status'], function ($query, $status) {
                $query->whereHas('payment', fn ($query) => $query->where('status', $status));
            });
    }
}
