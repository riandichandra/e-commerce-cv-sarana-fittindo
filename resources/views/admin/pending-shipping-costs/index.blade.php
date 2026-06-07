<x-admin-layout>
    <div class="flex flex-col gap-6">
        <div class="flex items-center gap-1 text-xs">
            <p class="tracking-wider">ADMIN</p>
            <p>></p>
            <p class="font-bold tracking-wider text-primary">PENDING PENGIRIMAN</p>
        </div>

        <div class="flex w-full flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <p class="text-sm font-semibold text-primary">Ongkir manual</p>
                <h1 class="mt-1 text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
            </div>
        </div>

        @if (session('success') || session('error'))
            <div
                class="mb-4 border-l-4 {{ session('success') ? 'border-green-500 bg-green-50 text-green-700' : 'border-red-500 bg-red-50 text-red-700' }} px-4 py-3 text-sm font-semibold">
                {{ session('success') ?? session('error') }}
            </div>
        @endif

        <div class="w-full overflow-hidden border border-[#f2c8d0] bg-white shadow-sm">
            <div
                class="flex flex-col gap-3 border-b border-[#f2c8d0] bg-[#fff7f8] p-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-black tracking-wide text-texthighlight">Daftar Pesanan Menunggu Ongkir</h2>
                    <p class="mt-1 text-sm text-gray-600">Pesanan dari luar Kota Palembang yang memerlukan input ongkos
                        kirim manual.</p>
                </div>
                <p class="text-sm font-semibold text-gray-600">
                    Menampilkan {{ $orders->count() }} dari {{ number_format($orders->total(), 0, ',', '.') }} pesanan
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px]">
                    <thead>
                        <tr
                            class="border-b border-gray-200 bg-white text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                            <th class="w-16 px-5 py-4">No.</th>
                            <th class="px-5 py-4">Pesanan</th>
                            <th class="px-5 py-4">Pelanggan</th>
                            <th class="px-5 py-4">Alamat</th>
                            <th class="px-5 py-4">Item</th>
                            <th class="px-5 py-4">Subtotal</th>
                            <th class="px-5 py-4">Input Ongkir</th>
                            <th class="px-5 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($orders as $order)
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
                                <td class="max-w-[240px] px-5 py-4">
                                    <p class="text-xs text-gray-700">{{ $order->shipping_address }}</p>
                                    <p class="text-xs text-gray-500">{{ $order->shipping_district }},
                                        {{ $order->shipping_city }}</p>
                                    <p class="text-xs text-gray-500">{{ $order->shipping_province }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-700">{{ $order->items_count }}
                                        item</span>
                                </td>
                                <td class="px-5 py-4 font-black text-texthighlight">Rp
                                    {{ number_format($order->subtotal, 0, ',', '.') }}</td>
                                <td class="px-5 py-4">
                                    <form action="{{ route('admin.orders.shipping-cost.update', $order) }}"
                                        method="POST" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="number" name="shipping_cost" min="0" step="1000"
                                            placeholder="Rp"
                                            class="w-28 border border-gray-300 bg-white px-2 py-1.5 text-sm text-gray-700 shadow-sm focus:border-primary focus:outline-none"
                                            required>
                                        <button type="submit"
                                            class="inline-flex items-center justify-center bg-primary px-2.5 py-1.5 text-xs font-bold text-white transition hover:bg-red-700">
                                            SIMPAN
                                        </button>
                                    </form>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a class="inline-flex h-9 items-center justify-center gap-1.5 bg-gray-700 px-3 text-xs font-bold text-white transition hover:bg-gray-800"
                                        href="{{ route('admin.orders.show', $order) }}">
                                        <iconify-icon icon="mdi:eye" class="fs-6"></iconify-icon>
                                        DETAIL
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-14 text-center">
                                    <div class="mx-auto flex max-w-md flex-col items-center">
                                        <div
                                            class="flex h-14 w-14 items-center justify-center bg-[#fff1f3] text-primary">
                                            <iconify-icon icon="mdi:truck-check-outline" class="fs-3"></iconify-icon>
                                        </div>
                                        <p class="mt-4 text-base font-bold text-texthighlight">Tidak ada pesanan
                                            menunggu ongkir.</p>
                                        <p class="mt-2 text-sm text-gray-500">Semua ongkos kirim manual sudah
                                            dikonfirmasi.</p>
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

        <div>
            <a href="{{ route('admin.orders.index') }}"
                class="inline-flex items-center gap-1 text-sm font-semibold text-gray-600 hover:text-primary transition">
                <iconify-icon icon="mdi:arrow-left" class="fs-5"></iconify-icon>
                Kembali ke semua pesanan
            </a>
        </div>
    </div>
</x-admin-layout>
