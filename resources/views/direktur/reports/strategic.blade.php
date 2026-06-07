<x-direktur-layout>
    @php
        $orderStatuses = [
            'menunggu_konfirmasi_ongkir' => 'Menunggu Konfirmasi Ongkir',
            'belum_dibayar' => 'Belum Dibayar',
            'menunggu_verifikasi_pembayaran' => 'Menunggu Verifikasi Pembayaran',
            'pembayaran_dikonfirmasi' => 'Pembayaran Dikonfirmasi',
            'diproses' => 'Diproses',
            'dikirim' => 'Dikirim',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
        ];

        $statusClass = fn (?string $status) => match ($status) {
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
    @endphp

    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-1 text-xs">
            <p class="tracking-wider">{{ $pagePath[0] }}</p>
            <p>></p>
            <p class="font-bold tracking-wider text-primary">{{ $pagePath[1] }}</p>
        </div>

        <div class="mb-7">
            <p class="text-sm font-semibold uppercase tracking-[.16em] text-primary">Monitoring Perusahaan</p>
            <h1 class="text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
            <p class="mt-2 max-w-3xl text-sm font-medium text-gray-600">
                Laporan monitoring membantu Direktur memantau informasi penjualan, pesanan, produk terlaris, serta status promosi sebagai bahan evaluasi kinerja perusahaan.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="rounded-md border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[.14em] text-gray-500">Total Pesanan</p>
                <p class="mt-2 text-3xl font-black text-texthighlight">{{ $summary['total_orders'] }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[.14em] text-gray-500">Total Penjualan</p>
                <p class="mt-2 text-2xl font-black text-texthighlight">Rp {{ number_format($summary['total_sales'], 0, ',', '.') }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[.14em] text-gray-500">Pendapatan Terverifikasi</p>
                <p class="mt-2 text-2xl font-black text-primary">Rp {{ number_format($summary['verified_revenue'], 0, ',', '.') }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[.14em] text-gray-500">Total Diskon</p>
                <p class="mt-2 text-2xl font-black text-texthighlight">Rp {{ number_format($summary['total_discount'], 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-[1fr_360px]">
            <div class="overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 bg-[#FFF7F8] px-5 py-4">
                    <div>
                        <h2 class="font-bold tracking-wider text-texthighlight">DATA PESANAN</h2>
                        <p class="mt-1 text-sm text-gray-500">Pantau order berdasarkan periode dan status pesanan.</p>
                    </div>

                    <form method="GET" action="{{ route('direktur.reports.strategic') }}" class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-[1fr_1fr_240px_auto]">
                        <input type="date" name="start_date" value="{{ $filters['start_date'] }}"
                            class="rounded-md border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <input type="date" name="end_date" value="{{ $filters['end_date'] }}"
                            class="rounded-md border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <select name="status" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                            <option value="">Semua Status Pesanan</option>
                            @foreach ($orderStatuses as $value => $label)
                                <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary-dark">
                            <iconify-icon icon="mdi:filter" class="fs-6"></iconify-icon>
                            FILTER
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[920px] text-left">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 text-xs font-bold uppercase tracking-[.08em] text-gray-500">
                                <th class="w-16 px-5 py-3">No.</th>
                                <th class="px-5 py-3">Pesanan</th>
                                <th class="px-5 py-3">Pelanggan</th>
                                <th class="px-5 py-3">Item</th>
                                <th class="px-5 py-3">Total</th>
                                <th class="px-5 py-3">Pembayaran</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            @forelse ($orders as $order)
                                <tr class="align-top transition hover:bg-gray-50">
                                    <td class="px-5 py-4 font-semibold text-gray-500">{{ $orders->firstItem() + $loop->index }}</td>
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-texthighlight">{{ $order->order_number }}</div>
                                        <div class="mt-1 text-xs text-gray-500">{{ $order->created_at->format('d M Y') }}</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-texthighlight">{{ $order->user?->name ?? $order->shipping_name }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $order->shipping_phone }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">{{ $order->items->sum('quantity') }}</td>
                                    <td class="px-5 py-4 font-bold text-texthighlight">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $statusClass($order->payment?->status) }}">
                                            {{ $order->payment?->status_label ?? 'Belum Ada' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $statusClass($order->status) }}">
                                            {{ $order->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">{{ $order->created_at->format('d M Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-12 text-center">
                                        <div class="mx-auto flex max-w-sm flex-col items-center gap-2 text-gray-500">
                                            <iconify-icon icon="mdi:file-search-outline" class="text-4xl text-gray-300"></iconify-icon>
                                            <p class="font-semibold text-texthighlight">Tidak ada data pesanan</p>
                                            <p class="text-sm">Coba ubah filter tanggal atau status pesanan.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($orders->hasPages())
                    <div class="border-t border-gray-200 px-5 py-4">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>

            <aside class="space-y-5">
                <section class="rounded-md bg-[#10233d] p-6 text-white shadow-sm">
                    <h2 class="text-xl font-black uppercase">Promosi</h2>
                    <p class="mt-1 text-sm font-medium text-blue-100">Status promosi untuk monitoring strategi.</p>
                    <div class="mt-6 space-y-4">
                        <div class="flex items-center justify-between border-b border-white/15 pb-3">
                            <span>Berjalan</span>
                            <span class="font-black">{{ $promotionStatus['running'] }}</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-white/15 pb-3">
                            <span>Terjadwal</span>
                            <span class="font-black">{{ $promotionStatus['upcoming'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Nonaktif</span>
                            <span class="font-black">{{ $promotionStatus['inactive'] }}</span>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 bg-gray-50 px-5 py-4">
                        <h2 class="text-xl font-black uppercase text-texthighlight">Produk Terlaris</h2>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($topProducts as $product)
                            <div class="px-5 py-4">
                                <p class="font-black text-texthighlight">{{ $product->product_name }}</p>
                                <div class="mt-1 flex items-center justify-between gap-3 text-sm font-semibold text-gray-600">
                                    <span>{{ $product->total_quantity }} item</span>
                                    <span class="text-primary">Rp {{ number_format($product->total_sales, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="px-5 py-10 text-center text-sm font-semibold text-gray-500">Belum ada produk terjual.</div>
                        @endforelse
                    </div>
                </section>

                <section class="overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 bg-gray-50 px-5 py-4">
                        <h2 class="text-xl font-black uppercase text-texthighlight">Status Pesanan</h2>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($orderStatusCounts as $status => $count)
                            <div class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                                <span class="font-bold text-gray-600">{{ \App\Models\Order::make(['status' => $status])->status_label }}</span>
                                <span class="rounded-full bg-primary/10 px-3 py-1 font-black text-primary">{{ $count }}</span>
                            </div>
                        @empty
                            <div class="px-5 py-10 text-center text-sm font-semibold text-gray-500">Belum ada pesanan.</div>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-direktur-layout>
