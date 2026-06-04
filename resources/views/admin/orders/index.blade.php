<x-admin-layout>
    <div class="flex flex-col gap-2">
        <div class="text-xs flex items-center gap-1">
            <p class="tracking-wider">ADMIN</p>
            <p>></p>
            <p class="font-bold text-primary tracking-wider">ORDERS</p>
        </div>

        <div class="w-full flex items-center justify-between mb-7">
            <h1 class="text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
        </div>

        <div class="bg-[#FFF1F3] p-5 w-full">
            <h2 class="font-semibold tracking-wider text-texthighlight">DAFTAR PESANAN</h2>
            <form method="GET" class="mt-4 mb-4 flex items-center gap-3">
                <input type="text" name="q" placeholder="Cari nomor pesanan atau pelanggan"
                    value="{{ request('q') }}"
                    class="w-1/3 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-primary focus:outline-none">

                <select name="status"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-primary focus:outline-none">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                            {{ \App\Models\Order::make(['status' => $s])->status_label }}</option>
                    @endforeach
                </select>

                <div class="flex gap-2">
                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white hover:bg-red-700 transition">Cari</button>
                    <a href="{{ route('admin.orders.index') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">Reset</a>
                </div>
            </form>
            <div class="overflow-x-auto">
                <table class="mt-3 w-full">
                    <thead>
                        <tr class="text-left text-sm text-gray-600 font-medium border-b border-gray-300">
                            <th class="py-3 px-3">#</th>
                            <th class="py-3 px-3">Nomor Pesanan</th>
                            <th class="py-3 px-3">Pelanggan</th>
                            <th class="py-3 px-3">Item</th>
                            <th class="py-3 px-3">Total</th>
                            <th class="py-3 px-3">Pembayaran</th>
                            <th class="py-3 px-3">Status Pesanan</th>
                            <th class="py-3 px-3">Pengiriman</th>
                            <th class="py-3 px-3">Tanggal</th>
                            <th class="py-3 px-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
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

                            <tr class="border-b border-gray-200 text-sm">
                                <td class="py-3 px-3">{{ $orders->firstItem() + $loop->index }}</td>
                                <td class="py-3 px-3 font-medium text-texthighlight">{{ $order->order_number }}</td>
                                <td class="py-3 px-3">
                                    <p class="font-medium text-texthighlight">
                                        {{ $order->user?->name ?? $order->shipping_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $order->shipping_phone }}</p>
                                </td>
                                <td class="py-3 px-3">{{ $order->items_count }}</td>
                                <td class="py-3 px-3">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                <td class="py-3 px-3">
                                    <div class="flex flex-col gap-1">
                                        <span>{{ $order->paymentMethod?->name ?? '-' }}</span>
                                        <span class="w-fit px-2 py-1 text-xs {{ $paymentStatusClass }}">
                                            {{ $order->payment?->status_label ?? 'Belum Ada' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 px-3">
                                    <span class="px-2 py-1 text-xs {{ $orderStatusClass }}">
                                        {{ $order->status_label }}
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    <span class="px-2 py-1 text-xs bg-gray-100 text-gray-700">
                                        {{ $order->delivery?->status_label ?? 'Belum Ada' }}
                                    </span>
                                </td>
                                <td class="py-3 px-3">{{ $order->created_at->format('d M Y') }}</td>
                                <td class="py-3 px-3">
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
                                            class="flex flex-col gap-2">
                                            @csrf
                                            @method('PATCH')

                                            <div class="flex gap-2">
                                                <select name="status"
                                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-primary focus:outline-none"
                                                    required>
                                                    <option value="">Pilih status</option>
                                                    @foreach ($statusOptions as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit"
                                                    class="inline-flex items-center justify-center rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white hover:bg-red-700 transition">OK</button>
                                            </div>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-500">-</span>
                                    @endif

                                    <a class="inline-flex items-center gap-1.5 bg-gray-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-800 transition"
                                        href="{{ route('admin.orders.show', $order) }}">
                                        <iconify-icon icon="mdi:eye" class="fs-6"></iconify-icon>
                                        DETAIL
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-6 px-3 text-center text-sm text-gray-500">Belum ada
                                    pesanan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>
