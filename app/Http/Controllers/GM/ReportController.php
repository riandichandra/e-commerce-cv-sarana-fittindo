<?php

namespace App\Http\Controllers\GM;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
            'verified_revenue' => (clone $ordersQuery)
                ->whereHas('payment', fn ($query) => $query->where('status', 'terverifikasi'))
                ->with('payment')
                ->get()
                ->sum(fn (Order $order) => (float) ($order->payment?->amount ?? 0)),
        ];

        return view('gm.reports.index', compact(
            'pagePath',
            'pageName',
            'orders',
            'filters',
            'summary',
        ));
    }

    public function download(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $orders = $this->reportQuery($filters)
            ->latest()
            ->get();
        $header = $this->reportHeader($filters);

        $filename = 'laporan-kuantitas-penjualan-'.now()->format('Ymd-His').'.xls';

        return response()->streamDownload(function () use ($orders, $filters, $header) {
            echo view('gm.reports.excel', [
                'orders' => $orders,
                'filters' => $filters,
                'generatedAt' => now(),
                'header' => $header,
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

    private function reportHeader(array $filters): array
    {
        return [
            'title' => 'Laporan Kuantitas Penjualan',
            'branch' => 'CV. SARANA FITTINDO',
            'period' => $this->periodLabel($filters['start_date'], $filters['end_date']),
            'order_status' => $this->orderStatusLabel($filters['status']),
            'payment_status' => $this->paymentStatusLabel($filters['payment_status']),
        ];
    }

    private function orderStatusLabel(?string $status): string
    {
        if (! $status) {
            return 'Semua';
        }

        return [
            'menunggu_konfirmasi_ongkir' => 'Menunggu Konfirmasi Ongkir',
            'belum_dibayar' => 'Belum Dibayar',
            'menunggu_verifikasi_pembayaran' => 'Menunggu Verifikasi Pembayaran',
            'pembayaran_dikonfirmasi' => 'Pembayaran Dikonfirmasi',
            'diproses' => 'Diproses',
            'dikirim' => 'Dikirim',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
        ][$status] ?? ucwords(str_replace('_', ' ', $status));
    }

    private function paymentStatusLabel(?string $status): string
    {
        if (! $status) {
            return 'Semua';
        }

        return [
            'menunggu' => 'Menunggu',
            'terverifikasi' => 'Terverifikasi',
            'ditolak' => 'Ditolak',
        ][$status] ?? ucwords(str_replace('_', ' ', $status));
    }

    private function periodLabel(?string $startDate, ?string $endDate): string
    {
        $start = $this->formatIndonesianDate($startDate) ?? 'Semua';
        $end = $this->formatIndonesianDate($endDate) ?? 'Semua';

        if ($start === 'Semua' && $end === 'Semua') {
            return 'Semua';
        }

        return $start . ' - ' . $end;
    }

    private function formatIndonesianDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $parsedDate = Carbon::parse($date);

        return $parsedDate->day . ' ' . $months[$parsedDate->month] . ' ' . $parsedDate->year;
    }
}
