<x-admin-layout>
    <div class="flex flex-col gap-2">
        <div class="text-xs flex items-center gap-1">
            <p class="tracking-wider">ADMIN</p>
            <p>></p>
            <p class="font-bold text-primary tracking-wider">PENDING SHIPPING</p>
        </div>

        <div class="w-full flex items-center justify-between mb-7">
            <h1 class="text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
        </div>

        @if (session('success') || session('error'))
            <div class="mb-4 border-l-4 {{ session('success') ? 'border-green-500 bg-green-50 text-green-700' : 'border-red-500 bg-red-50 text-red-700' }} px-4 py-3 text-sm font-semibold">
                {{ session('success') ?? session('error') }}
            </div>
        @endif

        <div class="bg-[#FFF1F3] p-5 w-full">
            <h2 class="font-semibold tracking-wider text-texthighlight">DAFTAR PESANAN MENUNGGU ONGKIR</h2>
            <p class="mt-1 text-sm text-gray-500">Pesanan dari luar Kota Palembang yang memerlukan input ongkos kirim manual oleh admin.</p>

            <div class="overflow-x-auto mt-4">
                <table class="mt-3 w-full">
                    <thead>
                        <tr class="text-left text-sm text-gray-600 font-medium border-b border-gray-300">
                            <th class="py-3 px-3">#</th>
                            <th class="py-3 px-3">Nomor Pesanan</th>
                            <th class="py-3 px-3">Pelanggan</th>
                            <th class="py-3 px-3">Alamat</th>
                            <th class="py-3 px-3">Item</th>
                            <th class="py-3 px-3">Subtotal</th>
                            <th class="py-3 px-3">Input Ongkir</th>
                            <th class="py-3 px-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr class="border-b border-gray-200 text-sm">
                                <td class="py-3 px-3">{{ $orders->firstItem() + $loop->index }}</td>
                                <td class="py-3 px-3 font-medium text-texthighlight">{{ $order->order_number }}</td>
                                <td class="py-3 px-3">
                                    <p class="font-medium text-texthighlight">
                                        {{ $order->user?->name ?? $order->shipping_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $order->shipping_phone }}</p>
                                </td>
                                <td class="py-3 px-3 max-w-[200px]">
                                    <p class="text-xs text-gray-700">{{ $order->shipping_address }}</p>
                                    <p class="text-xs text-gray-500">{{ $order->shipping_district }},
                                        {{ $order->shipping_city }}</p>
                                    <p class="text-xs text-gray-500">{{ $order->shipping_province }}</p>
                                </td>
                                <td class="py-3 px-3">{{ $order->items_count }}</td>
                                <td class="py-3 px-3 font-medium">Rp
                                    {{ number_format($order->subtotal, 0, ',', '.') }}</td>
                                <td class="py-3 px-3">
                                    <form action="{{ route('admin.orders.shipping-cost.update', $order) }}"
                                        method="POST" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="number" name="shipping_cost" min="0" step="1000"
                                            placeholder="Rp"
                                            class="w-28 rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-sm text-gray-700 shadow-sm focus:border-primary focus:outline-none"
                                            required>
                                        <button type="submit"
                                            class="inline-flex items-center justify-center rounded-lg bg-primary px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-red-700 transition">
                                            SIMPAN
                                        </button>
                                    </form>
                                </td>
                                <td class="py-3 px-3">
                                    <a class="inline-flex items-center gap-1.5 bg-gray-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-800 transition"
                                        href="{{ route('admin.orders.show', $order) }}">
                                        <iconify-icon icon="mdi:eye" class="fs-6"></iconify-icon>
                                        DETAIL
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 px-3 text-center text-sm text-gray-500">Tidak ada
                                    pesanan yang menunggu konfirmasi ongkos kirim.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        </div>

        <div class="mt-2">
            <a href="{{ route('admin.orders.index') }}"
                class="inline-flex items-center gap-1 text-sm font-semibold text-gray-600 hover:text-primary transition">
                <iconify-icon icon="mdi:arrow-left" class="fs-5"></iconify-icon>
                Kembali ke semua pesanan
            </a>
        </div>
    </div>
</x-admin-layout>
