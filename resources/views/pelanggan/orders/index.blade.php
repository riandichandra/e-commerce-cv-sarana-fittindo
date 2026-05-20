<x-pelanggan-layout>
    <section class="bg-[#071d33] px-8 py-14 text-white">
        <div class="mx-auto max-w-[1290px]">
            <p class="text-xs font-black uppercase tracking-[.24em] text-[#c8d8ee]">Customer Orders</p>
            <h1 class="mt-4 text-4xl font-black uppercase tracking-[-.03em]">Pesanan Saya</h1>
        </div>
    </section>

    <section class="bg-[#f7faff] px-8 py-14">
        <div class="mx-auto grid max-w-[1290px] grid-cols-1 gap-8 xl:grid-cols-2">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-black uppercase text-[#10233d]">Belum Dibayar</h2>
                    <span class="bg-yellow-100 px-3 py-1 text-xs font-black uppercase tracking-[.12em] text-yellow-800">
                        {{ $unpaidOrders->count() }} Pesanan
                    </span>
                </div>

                @forelse ($unpaidOrders as $order)
                    @php
                        $unpaidStatusLabel = match ($order->status) {
                            'waiting_payment_confirmation' => 'Menunggu Verifikasi Admin',
                            default => $order->payment?->status === 'rejected' ? 'Ditolak' : 'Belum Dibayar',
                        };
                        $unpaidStatusClass = match ($order->status) {
                            'waiting_payment_confirmation' => 'bg-blue-100 text-blue-800',
                            default => $order->payment?->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800',
                        };
                    @endphp
                    <article class="bg-white p-5 shadow-sm">
                        <div class="flex flex-col gap-3 border-b border-[#e8eef7] pb-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-lg font-black text-[#10233d]">{{ $order->order_number }}</p>
                                <p class="mt-1 text-xs font-semibold uppercase tracking-[.12em] text-[#7b8799]">
                                    {{ $order->created_at->format('d M Y H:i') }}
                                </p>
                            </div>
                            <span class="w-fit px-3 py-1 text-xs font-black uppercase tracking-[.12em] {{ $unpaidStatusClass }}">
                                {{ $unpaidStatusLabel }}
                            </span>
                        </div>

                        <div class="mt-4 space-y-3">
                            @foreach ($order->items as $item)
                                <div class="flex items-start justify-between gap-4 text-sm">
                                    <div>
                                        <p class="font-bold text-[#10233d]">{{ $item->product_name }}</p>
                                        <p class="mt-1 text-[#657891]">{{ $item->quantity }} x Rp {{ number_format($item->product_price, 0, ',', '.') }}</p>
                                    </div>
                                    <p class="shrink-0 font-black text-[#c8102e]">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
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
                                <p class="mt-1 text-lg font-black text-[#c8102e]">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</p>
                            </div>
                        </div>

                        <a href="{{ route('pelanggan.orders.payment-proof', $order) }}"
                            class="mt-5 flex h-11 w-full items-center justify-center gap-2 bg-[#c8102e] text-xs font-black uppercase tracking-[.16em] text-white hover:bg-[#9f0d24]">
                            <iconify-icon icon="mdi:upload-outline"></iconify-icon>
                            Upload Bukti Pembayaran
                        </a>
                    </article>
                @empty
                    <div class="bg-white px-6 py-12 text-center shadow-sm">
                        <iconify-icon icon="mdi:receipt-text-check-outline" class="text-5xl text-[#9db2cf]"></iconify-icon>
                        <p class="mt-4 text-sm font-semibold text-[#657891]">Tidak ada pesanan yang menunggu pembayaran.</p>
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
                        <div class="flex flex-col gap-3 border-b border-[#e8eef7] pb-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-lg font-black text-[#10233d]">{{ $order->order_number }}</p>
                                <p class="mt-1 text-xs font-semibold uppercase tracking-[.12em] text-[#7b8799]">
                                    {{ $order->created_at->format('d M Y H:i') }}
                                </p>
                            </div>
                            <span class="w-fit bg-green-100 px-3 py-1 text-xs font-black uppercase tracking-[.12em] text-green-800">
                                Sudah Dibayar
                            </span>
                        </div>

                        <div class="mt-4 space-y-3">
                            @foreach ($order->items as $item)
                                <div class="flex items-start justify-between gap-4 text-sm">
                                    <div>
                                        <p class="font-bold text-[#10233d]">{{ $item->product_name }}</p>
                                        <p class="mt-1 text-[#657891]">{{ $item->quantity }} x Rp {{ number_format($item->product_price, 0, ',', '.') }}</p>
                                    </div>
                                    <p class="shrink-0 font-black text-[#c8102e]">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
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
                                <p class="mt-1 text-lg font-black text-[#c8102e]">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</p>
                            </div>
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
