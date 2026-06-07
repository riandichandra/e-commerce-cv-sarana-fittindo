<x-admin-layout>
    <div class="flex flex-col gap-6">
        <div class="flex items-center gap-1 text-xs">
            <p class="tracking-wider">ADMIN</p>
            <p>></p>
            <p class="font-bold tracking-wider text-primary">PESANAN</p>
        </div>

        <div class="flex w-full flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <p class="text-sm font-semibold text-primary">Manajemen pesanan</p>
                <h1 class="mt-1 text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
            </div>
        </div>

        <div class="w-full overflow-hidden border border-[#f2c8d0] bg-white shadow-sm">
            <div class="border-b border-[#f2c8d0] bg-[#fff7f8] p-5">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-lg font-black tracking-wide text-texthighlight">DAFTAR PESANAN</h2>
                        <p class="mt-1 text-sm text-gray-600">Pantau pembayaran, pengiriman, dan status pesanan
                            pelanggan.</p>
                    </div>
                    <p class="text-sm font-semibold text-gray-600">
                        Menampilkan {{ $orders->count() }} dari {{ number_format($orders->total(), 0, ',', '.') }}
                        pesanan
                    </p>
                </div>

                <form method="GET" class="mt-4 flex flex-col gap-3 xl:flex-row xl:items-center">
                    <input type="text" name="q" placeholder="Cari nomor pesanan atau pelanggan"
                        value="{{ request('q') }}"
                        class="w-full border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-primary focus:outline-none xl:w-1/3">

                    <select name="status"
                        class="w-full border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-primary focus:outline-none xl:w-auto">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                                {{ \App\Models\Order::make(['status' => $s])->status_label }}</option>
                        @endforeach
                    </select>

                    <div class="flex gap-2">
                        <button type="submit"
                            class="inline-flex h-10 items-center justify-center bg-primary px-4 text-xs font-bold text-white transition hover:bg-red-700">Cari</button>
                        <a href="{{ route('admin.orders.index') }}"
                            class="inline-flex h-10 items-center justify-center border border-gray-300 bg-white px-4 text-xs font-bold text-gray-700 transition hover:bg-gray-50">Reset</a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1080px]">
                    <thead>
                        <tr
                            class="border-b border-gray-200 bg-white text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                            <th class="w-16 px-5 py-4">No.</th>
                            <th class="px-5 py-4">Pesanan</th>
                            <th class="px-5 py-4">Pelanggan</th>
                            <th class="px-5 py-4">Item</th>
                            <th class="px-5 py-4">Total</th>
                            <th class="px-5 py-4">Pembayaran</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4">Tanggal</th>
                            <th class="px-5 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($orders as $order)
                            @php
                                $orderStatusClass = match ($order->status) {
                                    'menunggu_konfirmasi_ongkir' => 'bg-orange-100 text-orange-800',
                                    'belum_dibayar' => 'bg-yellow-100 text-yellow-800',
                                    'menunggu_verifikasi_pembayaran' => 'bg-blue-100 text-blue-800',
                                    'pembayaran_dikonfirmasi' => 'bg-emerald-100 text-emerald-800',
                                    'diproses' => 'bg-indigo-100 text-indigo-800',
                                    'dikirim' => 'bg-purple-100 text-purple-800',
                                    'selesai' => 'bg-green-100 text-green-800',
                                    'dibatalkan' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-700',
                                };

                                $paymentStatusClass = match ($order->payment?->status) {
                                    'terverifikasi' => 'bg-green-100 text-green-800',
                                    'ditolak' => 'bg-red-100 text-red-800',
                                    'menunggu' => 'bg-yellow-100 text-yellow-800',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp

                            <tr class="text-sm transition hover:bg-[#fff7f8]">
                                <td class="px-5 py-4 align-top font-semibold text-gray-500">
                                    {{ $orders->firstItem() + $loop->index }}</td>
                                <td class="px-5 py-4">
                                    <p class="font-bold text-texthighlight">{{ $order->order_number }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ $order->created_at->format('d M Y H:i') }}
                                    </p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-bold text-texthighlight">
                                        {{ $order->user?->name ?? $order->shipping_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $order->shipping_phone }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-700">{{ $order->items_count }}
                                        item</span>
                                </td>
                                <td class="px-5 py-4 font-black text-texthighlight">Rp
                                    {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="font-semibold text-gray-800">{{ $order->paymentMethod?->name ?? '-' }}</span>
                                        <span class="w-fit px-2.5 py-1 text-xs font-bold {{ $paymentStatusClass }}">
                                            {{ $order->payment?->status_label ?? 'Belum Ada' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="px-2.5 py-1 text-xs font-bold {{ $orderStatusClass }}">
                                        {{ $order->status_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-gray-800">{{ $order->created_at->format('d M Y') }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">{{ $order->created_at->format('H:i') }}</p>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @php
                                        $statusOptions = match ($order->status) {
                                            'pembayaran_dikonfirmasi' => [
                                                'diproses' => 'Diproses',
                                                'dikirim' => 'Dikirim',
                                            ],
                                            'diproses' => ['dikirim' => 'Dikirim'],
                                            default => [],
                                        };
                                    @endphp

                                    @if (count($statusOptions))
                                        <form action="{{ route('admin.orders.update', $order) }}" method="POST"
                                            class="mb-2 flex flex-col gap-2">
                                            @csrf
                                            @method('PATCH')

                                            <div class="flex gap-2">
                                                <select name="status"
                                                    class="w-full border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-primary focus:outline-none"
                                                    required>
                                                    <option value="">Pilih status</option>
                                                    @foreach ($statusOptions as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit"
                                                    class="inline-flex items-center justify-center bg-primary px-3 py-2 text-xs font-bold text-white transition hover:bg-red-700">OK</button>
                                            </div>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-500">-</span>
                                    @endif

                                    <a class="inline-flex h-9 items-center justify-center gap-1.5 bg-gray-700 px-3 text-xs font-bold text-white transition hover:bg-gray-800"
                                        href="{{ route('admin.orders.show', $order) }}">
                                        <iconify-icon icon="mdi:eye" class="fs-6"></iconify-icon>
                                        DETAIL
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-14 text-center">
                                    <div class="mx-auto flex max-w-md flex-col items-center">
                                        <div
                                            class="flex h-14 w-14 items-center justify-center bg-[#fff1f3] text-primary">
                                            <iconify-icon icon="mdi:receipt-text-outline" class="fs-3"></iconify-icon>
                                        </div>
                                        <p class="mt-4 text-base font-bold text-texthighlight">Belum ada pesanan.</p>
                                        <p class="mt-2 text-sm text-gray-500">Pesanan pelanggan akan muncul di halaman
                                            ini.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($orders->hasPages())
                <div class="border-t border-gray-100 px-5 py-4">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
