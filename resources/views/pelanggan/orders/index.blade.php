@php
    $orderAlert = session('success') || session('error')
        ? [
            'type' => session('success') ? 'success' : 'error',
            'title' => session('success') ? 'Pesanan berhasil diperbarui' : 'Aksi pesanan gagal',
            'message' => session('success') ?? session('error'),
            'icon' => session('success') ? 'mdi:check-circle-outline' : 'mdi:alert-circle-outline',
        ]
        : null;
@endphp

<x-pelanggan-layout>
    <section class="bg-[#071d33] px-8 py-14 text-white">
        <div class="mx-auto max-w-[1290px]">
            <p class="text-xs font-black uppercase tracking-[.24em] text-[#c8d8ee]">Pesanan Pelanggan</p>
            <h1 class="mt-4 text-4xl font-black uppercase tracking-[-.03em]">Pesanan Saya</h1>
        </div>
    </section>

    <section class="bg-[#f7faff] px-8 py-14">
        <div class="mx-auto grid max-w-[1290px] grid-cols-1 gap-8 xl:grid-cols-2">
            @if ($orderAlert)
                <div
                    class="{{ $orderAlert['type'] === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-red-200 bg-red-50 text-red-900' }} xl:col-span-2 border p-5 shadow-sm"
                    x-data="{ visible: true }"
                    x-show="visible"
                    x-transition.opacity.duration.200ms
                >
                    <div class="flex items-start gap-4">
                        <div class="{{ $orderAlert['type'] === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }} flex h-12 w-12 shrink-0 items-center justify-center">
                            <iconify-icon icon="{{ $orderAlert['icon'] }}" class="text-2xl"></iconify-icon>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-black uppercase tracking-[.14em]">{{ $orderAlert['title'] }}</p>
                            <p class="mt-2 text-sm font-semibold leading-6 opacity-90">{{ $orderAlert['message'] }}</p>
                            @if (session('success'))
                                <p class="mt-3 text-xs font-black uppercase tracking-[.12em] text-emerald-700">
                                    Status pesanan terbaru sudah tercatat di sistem.
                                </p>
                            @endif
                        </div>
                        <button type="button" class="shrink-0 text-current opacity-60 hover:opacity-100" x-on:click="visible = false" aria-label="Tutup notifikasi">
                            <iconify-icon icon="mdi:close" class="text-xl"></iconify-icon>
                        </button>
                    </div>
                </div>
            @endif

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-black uppercase text-[#10233d]">Belum Dibayar</h2>
                    <span class="bg-yellow-100 px-3 py-1 text-xs font-black uppercase tracking-[.12em] text-yellow-800">
                        {{ $unpaidOrders->count() }} Pesanan
                    </span>
                </div>

                @forelse ($unpaidOrders as $order)
                    <article class="bg-white p-5 shadow-sm">
                        <div
                            class="flex flex-col gap-3 border-b border-[#e8eef7] pb-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-lg font-black text-[#10233d]">{{ $order->order_number }}</p>
                                <p class="mt-1 text-xs font-semibold uppercase tracking-[.12em] text-[#7b8799]">
                                    {{ $order->created_at->format('d M Y H:i') }}
                                </p>
                            </div>
                            <div class="flex flex-col items-start gap-2 sm:items-end">
                                <p class="text-xs font-black uppercase tracking-[.14em] text-[#657891]">Status Pesanan</p>
                                <span
                                    class="w-fit px-3 py-1 text-xs font-black uppercase tracking-[.12em] {{ $order->status_badge_class }}">
                                    {{ $order->status_label }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 space-y-3">
                            @foreach ($order->items as $item)
                                <div class="flex items-start justify-between gap-4 text-sm">
                                    <div>
                                        <p class="font-bold text-[#10233d]">{{ $item->product_name }}</p>
                                        <p class="mt-1 text-[#657891]">{{ $item->quantity }} x Rp
                                            {{ number_format($item->product_price, 0, ',', '.') }}</p>
                                    </div>
                                    <p class="shrink-0 font-black text-[#c8102e]">Rp
                                        {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-5 grid gap-3 border-t border-[#e8eef7] pt-4 text-sm sm:grid-cols-2">
                            <div>
                                <p class="text-[#657891]">Metode pembayaran</p>
                                <p class="mt-1 font-black text-[#10233d]">{{ $order->paymentMethod?->name ?? '-' }}</p>
                            </div>
                            <div class="sm:text-right">
                                <p class="text-[#657891]">{{ $order->isWaitingForShippingCost() ? 'Total sementara' : 'Total' }}</p>
                                <p class="mt-1 text-lg font-black text-[#c8102e]">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</p>
                                @if ($order->isWaitingForShippingCost())
                                    <p class="mt-1 text-xs font-semibold text-orange-700">Ongkos kirim menunggu konfirmasi admin.</p>
                                @endif
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <a href="{{ route('pelanggan.orders.show', $order) }}"
                                class="flex h-11 items-center justify-center gap-2 border border-[#d8e2f0] text-xs font-black uppercase tracking-[.16em] text-[#10233d] hover:border-[#c8102e] hover:text-[#c8102e]">
                                <iconify-icon icon="mdi:eye-outline"></iconify-icon>
                                Detail
                            </a>
                            @if ($order->isWaitingForShippingCost())
                                <div class="flex min-h-11 items-center justify-center bg-orange-50 px-3 text-center text-xs font-black uppercase tracking-[.12em] text-orange-800">
                                    Menunggu Ongkir
                                </div>
                            @else
                                <a href="{{ route('pelanggan.orders.payment-proof', $order) }}"
                                    class="flex h-11 items-center justify-center gap-2 bg-[#c8102e] text-xs font-black uppercase tracking-[.16em] text-white hover:bg-[#9f0d24]">
                                    <iconify-icon icon="mdi:upload-outline"></iconify-icon>
                                    Unggah Bukti
                                </a>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="bg-white px-6 py-12 text-center shadow-sm">
                        <iconify-icon icon="mdi:receipt-text-check-outline"
                            class="text-5xl text-[#9db2cf]"></iconify-icon>
                        <p class="mt-4 text-sm font-semibold text-[#657891]">Tidak ada pesanan yang menunggu pembayaran.
                        </p>
                    </div>
                @endforelse
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-black uppercase text-[#10233d]">Sudah Dibayar</h2>
                    <span class="bg-green-100 px-3 py-1 text-xs font-black uppercase tracking-[.12em] text-green-800">
                        {{ $paidOrders->count() }} Pesanan
                    </span>
                </div>

                @forelse ($paidOrders as $order)
                    <article class="bg-white p-5 shadow-sm">
                        <div
                            class="flex flex-col gap-3 border-b border-[#e8eef7] pb-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-lg font-black text-[#10233d]">{{ $order->order_number }}</p>
                                <p class="mt-1 text-xs font-semibold uppercase tracking-[.12em] text-[#7b8799]">
                                    {{ $order->created_at->format('d M Y H:i') }}
                                </p>
                            </div>
                            <div class="flex flex-col items-start gap-2 sm:items-end">
                                <p class="text-xs font-black uppercase tracking-[.14em] text-[#657891]">Status Pesanan</p>
                                <span
                                    class="w-fit px-3 py-1 text-xs font-black uppercase tracking-[.12em] {{ $order->status_badge_class }}">
                                    {{ $order->status_label }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 space-y-3">
                            @foreach ($order->items as $item)
                                <div class="flex items-start justify-between gap-4 text-sm">
                                    <div>
                                        <p class="font-bold text-[#10233d]">{{ $item->product_name }}</p>
                                        <p class="mt-1 text-[#657891]">{{ $item->quantity }} x Rp
                                            {{ number_format($item->product_price, 0, ',', '.') }}</p>
                                    </div>
                                    <p class="shrink-0 font-black text-[#c8102e]">Rp
                                        {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-5 grid gap-3 border-t border-[#e8eef7] pt-4 text-sm sm:grid-cols-2">
                            <div>
                                <p class="text-[#657891]">Metode pembayaran</p>
                                <p class="mt-1 font-black text-[#10233d]">{{ $order->paymentMethod?->name ?? '-' }}</p>
                            </div>
                            <div class="sm:text-right">
                                <p class="text-[#657891]">Total</p>
                                <p class="mt-1 text-lg font-black text-[#c8102e]">Rp
                                    {{ number_format($order->total_amount, 0, ',', '.') }}</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-3">
                            <a href="{{ route('pelanggan.orders.show', $order) }}"
                                class="flex h-11 items-center justify-center gap-2 border border-[#d8e2f0] text-xs font-black uppercase tracking-[.16em] text-[#10233d] hover:border-[#c8102e] hover:text-[#c8102e]">
                                <iconify-icon icon="mdi:eye-outline"></iconify-icon>
                                Detail
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="bg-white px-6 py-12 text-center shadow-sm">
                        <iconify-icon icon="mdi:receipt-text-outline" class="text-5xl text-[#9db2cf]"></iconify-icon>
                        <p class="mt-4 text-sm font-semibold text-[#657891]">Belum ada pesanan yang sudah dibayar.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</x-pelanggan-layout>
