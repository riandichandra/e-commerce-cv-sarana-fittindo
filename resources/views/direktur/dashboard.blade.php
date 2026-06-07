<x-direktur-layout>
    @php
        $summaryCards = [
            [
                'label' => 'Total Penjualan',
                'value' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
                'icon' => 'mdi:cash-check',
                'tone' => 'bg-emerald-50 text-emerald-700',
                'meta' => 'Pembayaran terverifikasi',
            ],
            [
                'label' => 'Penjualan Bulan Ini',
                'value' => 'Rp ' . number_format($monthlyRevenue, 0, ',', '.'),
                'icon' => 'mdi:chart-line',
                'tone' => 'bg-blue-50 text-blue-700',
                'meta' => now()->format('F Y'),
            ],
            [
                'label' => 'Pesanan',
                'value' => $selesaiOrders . ' / ' . $totalOrders,
                'icon' => 'mdi:shopping-outline',
                'tone' => 'bg-rose-50 text-primary',
                'meta' => $activeOrders . ' pesanan aktif',
            ],
            [
                'label' => 'Promosi Berjalan',
                'value' => $runningPromotions,
                'icon' => 'mdi:loudspeaker',
                'tone' => 'bg-yellow-50 text-yellow-700',
                'meta' => $totalCustomers . ' pelanggan, ' . $activeProducts . ' produk aktif',
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
                <p class="mt-2 max-w-3xl text-sm font-medium text-gray-600">
                    Monitoring Dasbor menampilkan ringkasan penjualan, data pesanan, produk terlaris, dan promosi
                    berjalan sebagai bahan pemantauan kinerja perusahaan.
                </p>
            </div>

            <a href="{{ route('direktur.reports.strategic') }}"
                class="flex items-center justify-between bg-white px-4 py-3 text-sm font-bold text-texthighlight shadow-sm hover:text-primary lg:min-w-[220px]">
                <span>Lihat Laporan</span>
                <iconify-icon icon="mdi:arrow-right"></iconify-icon>
            </a>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="bg-white p-5 shadow-sm">
                <form method="GET" action="{{ route('direktur.dashboard') }}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.16em] text-gray-500">Total Penjualan</p>
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
                                <p class="text-xs">Total penjualan terverifikasi untuk tahun terpilih.</p>
                            </div>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center bg-emerald-50 text-emerald-700">
                            <iconify-icon icon="mdi:cash-check" class="fs-4"></iconify-icon>
                        </div>
                    </div>
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                </form>
            </div>

            <div class="bg-white p-5 shadow-sm">
                <form method="GET" action="{{ route('direktur.dashboard') }}">
                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.16em] text-gray-500">Penjualan Bulan Ini
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
                                <p class="text-xs">Penjualan untuk bulan
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
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[.16em] text-gray-500">Pesanan</p>
                        <p class="mt-3 text-3xl font-black text-texthighlight">{{ $selesaiOrders }} /
                            {{ $totalOrders }}</p>
                        <p class="mt-2 text-sm font-semibold text-gray-500">{{ $activeOrders }} pesanan aktif</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center bg-rose-50 text-primary">
                        <iconify-icon icon="mdi:shopping-outline" class="fs-4"></iconify-icon>
                    </div>
                </div>
            </div>

            <div class="bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[.16em] text-gray-500">Promosi Berjalan</p>
                        <p class="mt-3 text-3xl font-black text-texthighlight">{{ $runningPromotions }}</p>
                        <p class="mt-2 text-sm font-semibold text-gray-500">{{ $totalCustomers }} pelanggan,
                            {{ $activeProducts }} produk aktif</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center bg-yellow-50 text-yellow-700">
                        <iconify-icon icon="mdi:loudspeaker" class="fs-4"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[1fr_380px]">
            <section class="bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-black uppercase text-texthighlight">Tren Penjualan</h2>
                        <p class="mt-1 text-sm font-medium text-gray-500">Pendapatan terverifikasi dalam 6 bulan
                            terakhir.</p>
                    </div>
                    <p class="text-sm font-black text-primary">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
                </div>

                <div class="mt-8 flex h-64 items-end gap-4 border-b border-gray-200">
                    @foreach ($monthlyRevenueTrend as $month)
                        @php
                            $height = max(8, ((float) $month['amount'] / $maxMonthlyRevenue) * 100);
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
                <p class="mt-1 text-sm font-medium text-blue-100">Distribusi status pesanan saat ini.</p>

                <div class="mt-6 space-y-4">
                    @forelse ($orderStatusCounts as $status => $count)
                        @php
                            $maxStatusCount = max((int) $orderStatusCounts->max(), 1);
                            $width = ($count / $maxStatusCount) * 100;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span
                                    class="font-bold uppercase tracking-[.12em]">{{ \App\Models\Payment::make(['status' => $status])->status_label }}</span>
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
            <div class="border-b border-gray-200 bg-[#FFF7F8] px-5 py-4">
                <h2 class="text-xl font-black uppercase text-texthighlight">Top Pelanggan</h2>
                <p class="mt-1 text-sm font-medium text-gray-500">Pelanggan dengan jumlah pesanan terbanyak pada
                    {{ $monthNamas[$selectedMonth] ?? $selectedMonth }} {{ $selectedYear }}.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-xs font-bold uppercase tracking-[.08em] text-gray-500">
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
                                <td class="px-5 py-4 font-semibold text-gray-500">{{ $topCustomers->firstItem() + $loop->index }}</td>
                                <td class="px-5 py-4 font-bold text-texthighlight">{{ $customer->user?->name ?? 'Tidak diketahui' }}</td>
                                <td class="px-5 py-4 text-gray-600">{{ $customer->order_count }}</td>
                                <td class="px-5 py-4 font-bold text-texthighlight">Rp {{ number_format($customer->total_spent, 0, ',', '.') }}</td>
                                <td class="px-5 py-4 text-gray-600">Rp {{ number_format((float) $customer->avg_order_value, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500">Belum ada data pelanggan untuk periode ini.</td>
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
                <div class="flex items-center justify-between border-b border-gray-200 bg-[#FFF7F8] px-5 py-4">
                    <div>
                        <h2 class="text-xl font-black uppercase text-texthighlight">Data Pesanan Terbaru</h2>
                        <p class="mt-1 text-sm font-medium text-gray-500">Pesanan terbaru untuk pemantauan operasional.</p>
                    </div>
                    <a href="{{ route('direktur.reports.strategic') }}"
                        class="text-xs font-black uppercase tracking-[.14em] text-primary hover:text-primary-dark">Lihat Laporan</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 text-xs font-bold uppercase tracking-[.08em] text-gray-500">
                                <th class="w-16 px-5 py-3">No.</th>
                                <th class="px-5 py-3">Pesanan</th>
                                <th class="px-5 py-3">Pelanggan</th>
                                <th class="px-5 py-3">Item</th>
                                <th class="px-5 py-3">Total</th>
                                <th class="px-5 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            @forelse ($recentOrders as $order)
                                <tr class="align-top transition hover:bg-gray-50">
                                    <td class="px-5 py-4 font-semibold text-gray-500">{{ $recentOrders->firstItem() + $loop->index }}</td>
                                    <td class="px-5 py-4">
                                        <div class="font-black text-texthighlight">{{ $order->order_number }}</div>
                                        <div class="mt-1 text-xs text-gray-500">{{ $order->created_at->format('d M Y') }}</div>
                                    </td>
                                    <td class="px-5 py-4 font-bold text-gray-800">{{ $order->user?->name ?? $order->shipping_name }}</td>
                                    <td class="px-5 py-4 text-gray-600">{{ $order->items_count }}</td>
                                    <td class="px-5 py-4 font-bold text-texthighlight">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $statusClass($order->status) }}">
                                            {{ $order->status_label }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center text-sm font-semibold text-gray-500">Belum ada pesanan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($recentOrders->hasPages())
                    <div class="border-t border-gray-200 px-5 py-4">
                        {{ $recentOrders->links() }}
                    </div>
                @endif
            </section>

            <section class="overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-4 border-b border-gray-200 bg-[#FFF7F8] px-5 py-4">
                    <div>
                        <h2 class="text-xl font-black uppercase text-texthighlight">Produk Terlaris</h2>
                        <p class="mt-1 text-sm font-medium text-gray-500">Produk dengan nilai penjualan tertinggi.</p>
                    </div>

                    <form method="GET" action="{{ route('direktur.dashboard') }}" class="flex items-center gap-3">
                        <label class="text-xs font-semibold text-gray-500">Tahun</label>
                        <select name="year" class="rounded-md border border-gray-200 bg-white px-3 py-2 text-sm"
                            onchange="this.form.submit()">
                            @foreach ($availableYears as $year)
                                <option value="{{ $year }}" @selected($year === $selectedYear)>{{ $year }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    </form>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse ($topProducts as $product)
                        <div class="px-5 py-4 transition hover:bg-gray-50">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-black text-texthighlight">{{ $product->product_name }}</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-600">{{ $product->total_quantity }} item terjual</p>
                                </div>
                                <p class="text-sm font-black text-primary">Rp {{ number_format($product->total_sales, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-12 text-center text-sm font-semibold text-gray-500">Belum ada produk terjual.</div>
                    @endforelse
                </div>

                @if ($topProducts->hasPages())
                    <div class="border-t border-gray-200 px-5 py-4">
                        {{ $topProducts->links() }}
                    </div>
                @endif
            </section>
        </div>

        <section class="overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-200 bg-[#FFF7F8] px-5 py-4">
                <div>
                    <h2 class="text-xl font-black uppercase text-texthighlight">Promosi Sedang Berjalan</h2>
                    <p class="mt-1 text-sm font-medium text-gray-500">Promosi aktif untuk pemantauan strategi penjualan.</p>
                </div>
                <span class="rounded-full bg-primary px-3 py-1 text-xs font-black uppercase tracking-[.12em] text-white">{{ $runningPromotions }} Aktif</span>
            </div>

            <div class="grid grid-cols-1 divide-y divide-gray-100 md:grid-cols-2 md:divide-x md:divide-y-0 xl:grid-cols-5">
                @forelse ($activePromotions as $promotion)
                    <div class="p-5 transition hover:bg-gray-50">
                        <p class="font-black text-texthighlight">{{ $promotion->name }}</p>
                        <p class="mt-2 text-sm font-semibold text-gray-600">
                            {{ $promotion->type === 'percent' ? rtrim(rtrim(number_format($promotion->value, 2, ',', '.'), '0'), ',') . '%' : 'Rp ' . number_format($promotion->value, 0, ',', '.') }}
                        </p>
                        <p class="mt-3 text-xs font-semibold text-gray-500">
                            {{ $promotion->code ?: 'Tanpa kode' }} · {{ $promotion->start_date->format('d M Y') }} - {{ $promotion->end_date->format('d M Y') }}
                        </p>
                        <p class="mt-2 text-xs text-gray-500">{{ $promotion->createdBy?->name ?? '-' }}</p>
                    </div>
                @empty
                    <div class="col-span-full px-5 py-12 text-center text-sm font-semibold text-gray-500">Tidak ada promosi yang sedang berjalan.</div>
                @endforelse
            </div>

            @if ($activePromotions->hasPages())
                <div class="border-t border-gray-200 px-5 py-4">
                    {{ $activePromotions->links() }}
                </div>
            @endif
        </section>
    </div>
</x-direktur-layout>
