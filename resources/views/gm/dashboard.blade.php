<x-gm-layout>
    @php
        $summaryCards = [
            [
                'label' => 'Total Revenue',
                'value' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
                'icon' => 'mdi:cash-multiple',
                'tone' => 'bg-emerald-50 text-emerald-700',
                'meta' => $verifiedPayments . ' pembayaran verified',
            ],
            [
                'label' => 'Monthly Revenue',
                'value' => 'Rp ' . number_format($monthlyRevenue, 0, ',', '.'),
                'icon' => 'mdi:chart-line',
                'tone' => 'bg-blue-50 text-blue-700',
                'meta' => now()->format('F Y'),
            ],
            [
                'label' => 'Orders',
                'value' => $completedOrders . ' / ' . $totalOrders,
                'icon' => 'mdi:shopping',
                'tone' => 'bg-rose-50 text-primary',
                'meta' => $processingOrders . ' order sedang berjalan',
            ],
            [
                'label' => 'Customers',
                'value' => $totalCustomers,
                'icon' => 'mdi:account-group',
                'tone' => 'bg-yellow-50 text-yellow-700',
                'meta' => $activeProducts . ' produk aktif',
            ],
        ];

        $statusClass = fn (?string $status) => match ($status) {
            'pending_payment' => 'bg-yellow-100 text-yellow-800',
            'waiting_payment_confirmation' => 'bg-blue-100 text-blue-800',
            'payment_confirmed' => 'bg-emerald-100 text-emerald-800',
            'processing' => 'bg-indigo-100 text-indigo-800',
            'shipped' => 'bg-purple-100 text-purple-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled', 'rejected' => 'bg-red-100 text-red-800',
            'verified' => 'bg-green-100 text-green-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-700',
        };
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
                    Ringkasan performa penjualan, pembayaran, order, pelanggan, dan produk untuk General Manager.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:min-w-[360px]">
                <a href="{{ route('gm.reports.index') }}" class="flex items-center justify-between bg-white px-4 py-3 font-bold text-texthighlight shadow-sm hover:text-primary">
                    <span>Lihat Laporan</span>
                    <iconify-icon icon="mdi:file-chart"></iconify-icon>
                </a>
                <a href="{{ route('gm.reports.download') }}" class="flex items-center justify-between bg-white px-4 py-3 font-bold text-texthighlight shadow-sm hover:text-primary">
                    <span>Download Excel</span>
                    <iconify-icon icon="mdi:download"></iconify-icon>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($summaryCards as $card)
                <div class="bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.16em] text-gray-500">{{ $card['label'] }}</p>
                            <p class="mt-3 text-3xl font-black text-texthighlight">{{ $card['value'] }}</p>
                            <p class="mt-2 text-sm font-semibold text-gray-500">{{ $card['meta'] }}</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center {{ $card['tone'] }}">
                            <iconify-icon icon="{{ $card['icon'] }}" class="fs-4"></iconify-icon>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[1fr_380px]">
            <section class="bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-black uppercase text-texthighlight">Revenue Trend</h2>
                        <p class="mt-1 text-sm font-medium text-gray-500">Pembayaran verified dalam 6 bulan terakhir.</p>
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
                                <p class="mt-1 text-xs font-semibold text-texthighlight">Rp {{ number_format($month['amount'], 0, ',', '.') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="bg-[#10233d] p-6 text-white shadow-sm">
                <h2 class="text-xl font-black uppercase">Order Status</h2>
                <p class="mt-1 text-sm font-medium text-blue-100">Distribusi status order saat ini.</p>

                <div class="mt-6 space-y-4">
                    @forelse ($orderStatusCounts as $status => $count)
                        @php
                            $maxStatusCount = max((int) $orderStatusCounts->max(), 1);
                            $width = ($count / $maxStatusCount) * 100;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-bold uppercase tracking-[.12em]">{{ str_replace('_', ' ', $status) }}</span>
                                <span>{{ $count }}</span>
                            </div>
                            <div class="mt-2 h-2 bg-white/15">
                                <div class="h-2 bg-[#c8102e]" style="width: {{ $width }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="py-10 text-center text-sm font-semibold text-blue-100">Belum ada order.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[1fr_420px]">
            <section class="bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-black uppercase text-texthighlight">Recent Orders</h2>
                        <p class="mt-1 text-sm font-medium text-gray-500">{{ $totalOrders }} total order tercatat.</p>
                    </div>
                    <a href="{{ route('gm.reports.index') }}" class="text-xs font-black uppercase tracking-[.14em] text-primary hover:text-primary-dark">View Reports</a>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs uppercase tracking-[.12em] text-gray-500">
                                <th class="py-3 pr-4">Order</th>
                                <th class="py-3 pr-4">Customer</th>
                                <th class="py-3 pr-4">Items</th>
                                <th class="py-3 pr-4">Total</th>
                                <th class="py-3 pr-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentOrders as $order)
                                <tr class="border-b border-gray-100 text-sm">
                                    <td class="py-4 pr-4 font-black text-texthighlight">{{ $order->order_number }}</td>
                                    <td class="py-4 pr-4">
                                        <p class="font-bold text-gray-800">{{ $order->user?->name ?? $order->shipping_name }}</p>
                                        <p class="text-xs text-gray-500">{{ $order->created_at->format('d M Y') }}</p>
                                    </td>
                                    <td class="py-4 pr-4">{{ $order->items_count }}</td>
                                    <td class="py-4 pr-4 font-bold">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                    <td class="py-4 pr-4">
                                        <span class="px-2 py-1 text-xs font-bold {{ $statusClass($order->status) }}">
                                            {{ ucwords(str_replace('_', ' ', $order->status)) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-sm font-semibold text-gray-500">Belum ada order.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black uppercase text-texthighlight">Top Products</h2>
                <p class="mt-1 text-sm font-medium text-gray-500">Produk terlaris berdasarkan quantity order.</p>

                <div class="mt-5 space-y-4">
                    @forelse ($topProducts as $product)
                        <div class="border-b border-gray-100 pb-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-black text-texthighlight">{{ $product->product_name }}</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-600">{{ $product->total_quantity }} item terjual</p>
                                </div>
                                <p class="text-sm font-black text-primary">Rp {{ number_format($product->total_sales, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="py-10 text-center text-sm font-semibold text-gray-500">Belum ada produk terjual.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-gm-layout>
