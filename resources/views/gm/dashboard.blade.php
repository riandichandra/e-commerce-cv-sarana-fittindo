<x-gm-layout>
    @php
        $summaryCards = [
            [
                'label' => 'Total Pendapatan',
                'value' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
                'icon' => 'mdi:cash-multiple',
                'tone' => 'bg-emerald-50 text-emerald-700',
                'meta' => $verifiedPayments . ' pembayaran terverifikasi',
            ],
            [
                'label' => 'Pendapatan Bulanan',
                'value' => 'Rp ' . number_format($monthlyRevenue, 0, ',', '.'),
                'icon' => 'mdi:chart-line',
                'tone' => 'bg-blue-50 text-blue-700',
                'meta' => now()->format('F Y'),
            ],
            [
                'label' => 'Pesanan',
                'value' => $totalOrders,
                'icon' => 'mdi:shopping',
                'tone' => 'bg-rose-50 text-primary',
                'meta' => $totalOrders . ' pesanan ' . $cardOrderStatusLabel,
            ],
            [
                'label' => 'Pelanggan',
                'value' => $totalCustomers,
                'icon' => 'mdi:account-group',
                'tone' => 'bg-yellow-50 text-yellow-700',
                'meta' => $activeProducts . ' produk aktif',
            ],
        ];

        $statusClass = fn(?string $status) => match ($status) {
            'menunggu_konfirmasi_ongkir' => 'bg-orange-100 text-orange-800',
            'belum_dibayar' => 'bg-yellow-100 text-yellow-800',
            'menunggu_verifikasi_pembayaran' => 'bg-blue-100 text-blue-800',
            'pembayaran_dikonfirmasi' => 'bg-emerald-100 text-emerald-800',
            'diproses' => 'bg-indigo-100 text-indigo-800',
            'dikirim' => 'bg-purple-100 text-purple-800',
            'selesai' => 'bg-green-100 text-green-800',
            'dibatalkan', 'ditolak' => 'bg-red-100 text-red-800',
            'terverifikasi' => 'bg-green-100 text-green-800',
            'menunggu' => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-700',
        };

        $monthNamas = [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mei',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Agu',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Des',
        ];

        $topResetQuery = [
            'year' => $selectedYear,
            'month' => $selectedMonth,
            'card_order_start_date' => $cardOrderFilters['card_order_start_date'],
            'card_order_end_date' => $cardOrderFilters['card_order_end_date'],
        ];

        if ($cardOrderFilters['card_order_status']) {
            $topResetQuery['card_order_status'] = $cardOrderFilters['card_order_status'];
        }
    @endphp

    <div class="space-y-7">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="flex items-center gap-1 text-xs uppercase tracking-[.18em] text-gray-500">
                    <span>{{ $pagePath[0] }}</span>
                    <span>/</span>
                    <span class="font-black text-primary">{{ $pagePath[1] }}</span>
                </div>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-texthighlight">{{ $pageName }}</h1>
                <p class="mt-2 max-w-2xl text-sm font-medium text-gray-600">
                    Ringkasan performa penjualan, pembayaran, order, pelanggan, dan produk untuk Manajer Umum.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:min-w-[360px]">
                <a href="{{ route('gm.reports.index') }}"
                    class="flex items-center justify-between bg-white px-4 py-3 font-bold text-texthighlight shadow-sm hover:text-primary">
                    <span>Lihat Laporan</span>
                    <iconify-icon icon="mdi:file-chart"></iconify-icon>
                </a>
                <a href="{{ route('gm.reports.download') }}"
                    class="flex items-center justify-between bg-white px-4 py-3 font-bold text-texthighlight shadow-sm hover:text-primary">
                    <span>Download Laporan</span>
                    <iconify-icon icon="mdi:download"></iconify-icon>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="bg-white p-5 shadow-sm">
                <form method="GET" action="{{ route('gm.dashboard') }}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.16em] text-gray-500">Total Pendapatan</p>
                            <p class="mt-3 text-3xl font-black text-texthighlight">Rp
                                {{ number_format($totalRevenue, 0, ',', '.') }}</p>
                            <div class="mt-4 flex flex-col gap-2 text-sm text-gray-500">
                                <label class="flex items-center gap-2">
                                    <span class="font-semibold text-gray-700">Tahun:</span>
                                    <select name="year"
                                        class="rounded border border-gray-200 bg-white px-3 py-2 text-sm"
                                        onchange="this.form.submit()">
                                        @foreach ($availableYears as $year)
                                            <option value="{{ $year }}" @selected($year === $selectedYear)>
                                                {{ $year }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <p class="text-xs">Pendapatan terverifikasi untuk tahun terpilih.</p>
                            </div>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center bg-emerald-50 text-emerald-700">
                            <iconify-icon icon="mdi:cash-multiple" class="fs-4"></iconify-icon>
                        </div>
                    </div>
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <input type="hidden" name="top_start_date" value="{{ $topFilters['top_start_date'] }}">
                    <input type="hidden" name="top_end_date" value="{{ $topFilters['top_end_date'] }}">
                    <input type="hidden" name="card_order_start_date" value="{{ $cardOrderFilters['card_order_start_date'] }}">
                    <input type="hidden" name="card_order_end_date" value="{{ $cardOrderFilters['card_order_end_date'] }}">
                    <input type="hidden" name="card_order_status" value="{{ $cardOrderFilters['card_order_status'] }}">
                </form>
            </div>

            <div class="bg-white p-5 shadow-sm">
                <form method="GET" action="{{ route('gm.dashboard') }}">
                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                    <input type="hidden" name="top_start_date" value="{{ $topFilters['top_start_date'] }}">
                    <input type="hidden" name="top_end_date" value="{{ $topFilters['top_end_date'] }}">
                    <input type="hidden" name="card_order_start_date" value="{{ $cardOrderFilters['card_order_start_date'] }}">
                    <input type="hidden" name="card_order_end_date" value="{{ $cardOrderFilters['card_order_end_date'] }}">
                    <input type="hidden" name="card_order_status" value="{{ $cardOrderFilters['card_order_status'] }}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.16em] text-gray-500">Pendapatan Bulanan
                            </p>
                            <p class="mt-3 text-3xl font-black text-texthighlight">Rp
                                {{ number_format($monthlyRevenue, 0, ',', '.') }}</p>
                            <div class="mt-4 flex flex-col gap-2 text-sm text-gray-500">
                                <label class="flex items-center gap-2">
                                    <span class="font-semibold text-gray-700">Bulan:</span>
                                    <select name="month"
                                        class="rounded border border-gray-200 bg-white px-3 py-2 text-sm"
                                        onchange="this.form.submit()">
                                        @foreach ($availableMonths as $month)
                                            <option value="{{ $month }}" @selected($month === $selectedMonth)>
                                                {{ $monthNamas[$month] ?? $month }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <p class="text-xs">Pendapatan untuk bulan
                                    {{ $monthNamas[$selectedMonth] ?? $selectedMonth }} {{ $selectedYear }}.</p>
                            </div>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center bg-blue-50 text-blue-700">
                            <iconify-icon icon="mdi:chart-line" class="fs-4"></iconify-icon>
                        </div>
                    </div>
                </form>
            </div>

            <div class="bg-white p-5 shadow-sm">
                <form method="GET" action="{{ route('gm.dashboard') }}">
                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <input type="hidden" name="top_start_date" value="{{ $topFilters['top_start_date'] }}">
                    <input type="hidden" name="top_end_date" value="{{ $topFilters['top_end_date'] }}">

                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.16em] text-gray-500">Pesanan</p>
                            <p class="mt-3 text-3xl font-black text-texthighlight">{{ $totalOrders }}</p>
                            <p class="mt-2 text-sm font-semibold text-gray-500">{{ $totalOrders }} pesanan {{ $cardOrderStatusLabel }}</p>
                            <p class="mt-1 text-xs font-semibold text-gray-400">
                                Periode: {{ $cardOrderStartDate->format('d M Y') }} - {{ $cardOrderEndDate->format('d M Y') }}
                            </p>
                            <p class="mt-1 text-xs font-semibold text-gray-400">
                                Status:
                                {{ $cardOrderFilters['card_order_status']
                                    ? \App\Models\Order::make(['status' => $cardOrderFilters['card_order_status']])->status_label
                                    : 'Semua Status' }}
                            </p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center bg-rose-50 text-primary">
                            <iconify-icon icon="mdi:shopping" class="fs-4"></iconify-icon>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-2">
                        <div class="grid grid-cols-2 gap-2">
                            <input type="date" name="card_order_start_date"
                                value="{{ $cardOrderFilters['card_order_start_date'] }}"
                                class="w-full rounded border border-gray-200 bg-white px-2 py-2 text-xs font-semibold text-gray-700">
                            <input type="date" name="card_order_end_date"
                                value="{{ $cardOrderFilters['card_order_end_date'] }}"
                                class="w-full rounded border border-gray-200 bg-white px-2 py-2 text-xs font-semibold text-gray-700">
                        </div>
                        <select name="card_order_status"
                            class="w-full rounded border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700">
                            <option value="">Semua Status</option>
                            @foreach ($orderStatuses as $status)
                                <option value="{{ $status }}" @selected($cardOrderFilters['card_order_status'] === $status)>
                                    {{ \App\Models\Order::make(['status' => $status])->status_label }}
                                </option>
                            @endforeach
                        </select>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="submit"
                                class="inline-flex h-9 items-center justify-center gap-1 rounded bg-primary px-3 text-xs font-black uppercase tracking-[.12em] text-white hover:bg-primary-dark">
                                <iconify-icon icon="mdi:filter" class="fs-6"></iconify-icon>
                                Filter
                            </button>
                            <a href="{{ route('gm.dashboard', ['year' => $selectedYear, 'month' => $selectedMonth]) }}"
                                class="inline-flex h-9 items-center justify-center rounded border border-gray-200 bg-white px-3 text-xs font-black uppercase tracking-[.12em] text-texthighlight hover:border-primary hover:text-primary">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[.16em] text-gray-500">Pelanggan</p>
                        <p class="mt-3 text-3xl font-black text-texthighlight">{{ $totalCustomers }}</p>
                        <p class="mt-2 text-sm font-semibold text-gray-500">{{ $activeProducts }} produk aktif</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center bg-yellow-50 text-yellow-700">
                        <iconify-icon icon="mdi:account-group" class="fs-4"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[1fr_380px]">
            <section class="bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-black uppercase text-texthighlight">Tren Pendapatan</h2>
                        <p class="mt-1 text-sm font-medium text-gray-500">Pembayaran terverifikasi dalam 6 bulan
                            terakhir.
                        </p>
                    </div>
                    <p class="text-sm font-black text-primary">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
                </div>

                <div class="mt-8 flex h-64 items-end gap-4 border-b border-gray-200">
                    @foreach ($monthlySales as $month)
                        @php
                            $height = max(8, ((float) $month['amount'] / $maxMonthlySales) * 100);
                        @endphp
                        <div class="flex h-full flex-1 flex-col justify-end gap-3">
                            <div class="relative flex flex-1 items-end">
                                <div class="w-full bg-primary" style="height: {{ $height }}%"></div>
                            </div>
                            <div class="pb-3 text-center">
                                <p class="text-xs font-black uppercase text-gray-500">{{ $month['label'] }}</p>
                                <p class="mt-1 text-xs font-semibold text-texthighlight">Rp
                                    {{ number_format($month['amount'], 0, ',', '.') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="bg-[#10233d] p-6 text-white shadow-sm">
                <h2 class="text-xl font-black uppercase">Status Pesanan</h2>
                <p class="mt-1 text-sm font-medium text-blue-100">Distribusi status order saat ini.</p>

                <div class="mt-6 space-y-4">
                    @forelse ($orderStatusCounts as $status => $count)
                        @php
                            $maxStatusCount = max((int) $orderStatusCounts->max(), 1);
                            $width = ($count / $maxStatusCount) * 100;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span
                                    class="font-bold uppercase tracking-[.12em]">{{ \App\Models\Order::make(['status' => $status])->status_label }}</span>
                                <span>{{ $count }}</span>
                            </div>
                            <div class="mt-2 h-2 bg-white/15">
                                <div class="h-2 bg-[#c8102e]" style="width: {{ $width }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="py-10 text-center text-sm font-semibold text-blue-100">Belum ada pesanan.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm">
            <div class="grid gap-4 border-b border-gray-200 bg-[#FFF7F8] px-5 py-4 lg:grid-cols-[minmax(0,1fr)_180px] lg:items-end">
                <div class="lg:self-end">
                    <h2 class="text-xl font-black uppercase text-texthighlight">Top Pelanggan</h2>
                    <p class="mt-1 text-sm font-medium text-gray-500">
                        Pelanggan dengan jumlah pesanan terbanyak dari {{ $topStartDate->format('d M Y') }} sampai {{ $topEndDate->format('d M Y') }}.
                    </p>
                </div>

                <form method="GET" action="{{ route('gm.dashboard') }}" class="grid gap-2">
                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <input type="hidden" name="card_order_start_date" value="{{ $cardOrderFilters['card_order_start_date'] }}">
                    <input type="hidden" name="card_order_end_date" value="{{ $cardOrderFilters['card_order_end_date'] }}">
                    <input type="hidden" name="card_order_status" value="{{ $cardOrderFilters['card_order_status'] }}">
                    <label class="block text-xs font-semibold text-gray-500">
                        <span>Dari</span>
                        <input type="date" name="top_start_date" value="{{ $topFilters['top_start_date'] }}"
                            class="mt-1 h-9 w-full rounded border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-700">
                    </label>
                    <label class="block text-xs font-semibold text-gray-500">
                        <span>Sampai</span>
                        <input type="date" name="top_end_date" value="{{ $topFilters['top_end_date'] }}"
                            class="mt-1 h-9 w-full rounded border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-700">
                    </label>
                    <button type="submit"
                        class="inline-flex h-9 w-full items-center justify-center gap-1 rounded bg-primary px-4 text-xs font-black uppercase tracking-[.12em] text-white hover:bg-primary-dark">
                        <iconify-icon icon="mdi:filter" class="fs-6"></iconify-icon>
                        Filter
                    </button>
                    <a href="{{ route('gm.dashboard', $topResetQuery) }}"
                        class="inline-flex h-9 w-full items-center justify-center rounded border border-gray-200 bg-white px-4 text-xs font-black uppercase tracking-[.12em] text-texthighlight hover:border-primary hover:text-primary">
                        Reset
                    </a>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left">
                    <thead>
                        <tr
                            class="border-b border-gray-200 bg-gray-50 text-xs font-bold uppercase tracking-[.08em] text-gray-500">
                            <th class="w-16 px-5 py-3">No.</th>
                            <th class="px-5 py-3">Pelanggan</th>
                            <th class="px-5 py-3">Pesanan</th>
                            <th class="px-5 py-3">Total Belanja</th>
                            <th class="px-5 py-3">Rata-rata Pesanan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse ($topCustomers as $customer)
                            <tr class="transition hover:bg-gray-50">
                                <td class="px-5 py-4 font-semibold text-gray-500">
                                    {{ $topCustomers->firstItem() + $loop->index }}</td>
                                <td class="px-5 py-4 font-bold text-texthighlight">
                                    {{ $customer->user?->name ?? 'Tidak diketahui' }}</td>
                                <td class="px-5 py-4 text-gray-600">{{ $customer->order_count }}</td>
                                <td class="px-5 py-4 font-bold text-texthighlight">Rp
                                    {{ number_format($customer->total_spent, 0, ',', '.') }}</td>
                                <td class="px-5 py-4 text-gray-600">Rp
                                    {{ number_format((float) $customer->avg_order_value, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500">Belum ada data
                                    pelanggan untuk periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($topCustomers->hasPages())
                <div class="border-t border-gray-200 px-5 py-4">
                    {{ $topCustomers->links() }}
                </div>
            @endif
        </section>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[1fr_420px]">
            <section class="overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 bg-[#FFF7F8] px-5 py-4">
                    <div>
                        <h2 class="text-xl font-black uppercase text-texthighlight">Detail Pesanan</h2>
                        <p class="mt-1 text-sm font-medium text-gray-500">Daftar pesanan terbaru sebagai detail operasional dashboard.
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left">
                        <thead>
                            <tr
                                class="border-b border-gray-200 bg-gray-50 text-xs font-bold uppercase tracking-[.08em] text-gray-500">
                                <th class="w-16 px-5 py-3">No.</th>
                                <th class="px-5 py-3">Pesanan</th>
                                <th class="px-5 py-3">Pelanggan</th>
                                <th class="px-5 py-3">Item</th>
                                <th class="px-5 py-3">Total</th>
                                <th class="px-5 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            @forelse ($detailOrders as $order)
                                <tr class="align-top transition hover:bg-gray-50">
                                    <td class="px-5 py-4 font-semibold text-gray-500">
                                        {{ $detailOrders->firstItem() + $loop->index }}</td>
                                    <td class="px-5 py-4">
                                        <div class="font-black text-texthighlight">{{ $order->order_number }}</div>
                                        <div class="mt-1 text-xs text-gray-500">
                                            {{ $order->created_at->format('d M Y') }}</div>
                                    </td>
                                    <td class="px-5 py-4 font-bold text-gray-800">
                                        {{ $order->user?->name ?? $order->shipping_name }}</td>
                                    <td class="px-5 py-4 text-gray-600">{{ $order->items_count }}</td>
                                    <td class="px-5 py-4 font-bold text-texthighlight">Rp
                                        {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                    <td class="px-5 py-4">
                                        <span
                                            class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $statusClass($order->status) }}">
                                            {{ $order->status_label }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"
                                        class="px-5 py-12 text-center text-sm font-semibold text-gray-500">Tidak ada pesanan pada filter ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($detailOrders->hasPages())
                    <div class="border-t border-gray-200 px-5 py-4">
                        {{ $detailOrders->links() }}
                    </div>
                @endif
            </section>

            <section class="overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-gray-200 bg-[#FFF7F8] px-5 py-4">
                    <div>
                        <h2 class="text-xl font-black uppercase text-texthighlight">Produk Teratas</h2>
                        <p class="mt-1 text-sm font-medium text-gray-500">
                            Produk terlaris dari {{ $topStartDate->format('d M Y') }} sampai {{ $topEndDate->format('d M Y') }}.
                        </p>
                    </div>

                    <form method="GET" action="{{ route('gm.dashboard') }}" class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                        <input type="hidden" name="card_order_start_date" value="{{ $cardOrderFilters['card_order_start_date'] }}">
                        <input type="hidden" name="card_order_end_date" value="{{ $cardOrderFilters['card_order_end_date'] }}">
                        <input type="hidden" name="card_order_status" value="{{ $cardOrderFilters['card_order_status'] }}">
                        <label class="text-xs font-semibold text-gray-500">
                            <span>Dari</span>
                            <input type="date" name="top_start_date" value="{{ $topFilters['top_start_date'] }}"
                                class="mt-1 w-full rounded border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700">
                        </label>
                        <label class="text-xs font-semibold text-gray-500">
                            <span>Sampai</span>
                            <input type="date" name="top_end_date" value="{{ $topFilters['top_end_date'] }}"
                                class="mt-1 w-full rounded border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700">
                        </label>
                        <button type="submit"
                            class="inline-flex h-10 items-center justify-center gap-1 rounded bg-primary px-4 text-xs font-black uppercase tracking-[.12em] text-white hover:bg-primary-dark">
                            <iconify-icon icon="mdi:filter" class="fs-6"></iconify-icon>
                            Filter
                        </button>
                        <a href="{{ route('gm.dashboard', $topResetQuery) }}"
                            class="inline-flex h-10 items-center justify-center rounded border border-gray-200 bg-white px-4 text-xs font-black uppercase tracking-[.12em] text-texthighlight hover:border-primary hover:text-primary">
                            Reset
                        </a>
                    </form>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse ($topProducts as $product)
                        <div class="px-5 py-4 transition hover:bg-gray-50">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-black text-texthighlight">{{ $product->product_name }}</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-600">{{ $product->total_quantity }}
                                        item terjual</p>
                                </div>
                                <p class="text-sm font-black text-primary">Rp
                                    {{ number_format($product->total_sales, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-12 text-center text-sm font-semibold text-gray-500">Belum ada produk
                            terjual pada periode ini.</div>
                    @endforelse
                </div>

                @if ($topProducts->hasPages())
                    <div class="border-t border-gray-200 px-5 py-4">
                        {{ $topProducts->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-gm-layout>
