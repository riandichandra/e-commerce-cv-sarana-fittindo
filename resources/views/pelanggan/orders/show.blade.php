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
            <p class="text-xs font-black uppercase tracking-[.24em] text-[#c8d8ee]">Pesanan Detail</p>
            <h1 class="mt-4 text-4xl font-black uppercase tracking-[-.03em]">Detail Pesanan</h1>
        </div>
    </section>

    <section class="bg-[#f7faff] px-8 py-14">
        <div class="mx-auto grid max-w-[1290px] grid-cols-1 gap-8 lg:grid-cols-[1fr_420px]">
            @if ($orderAlert)
                <div
                    class="{{ $orderAlert['type'] === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-red-200 bg-red-50 text-red-900' }} lg:col-span-2 border p-5 shadow-sm"
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

            <div class="space-y-6">
                <div class="bg-white p-6 shadow-sm">
                    <div
                        class="flex flex-col gap-3 border-b border-[#e8eef7] pb-5 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-xl font-black uppercase text-[#10233d]">{{ $order->order_number }}</h2>
                            <p class="mt-1 text-sm text-[#657891]">{{ $order->created_at->format('d M Y H:i') }}</p>
                        </div>
                        <div class="flex flex-col items-start gap-2 sm:items-end">
                            <p class="text-xs font-black uppercase tracking-[.14em] text-[#657891]">Status Pesanan</p>
                            <span
                                class="w-fit px-3 py-1 text-xs font-black uppercase tracking-[.12em] {{ $order->status_badge_class }}">
                                {{ $order->status_label }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 text-sm md:grid-cols-2">
                        <div>
                            <p class="text-[#657891]">Metode pembayaran</p>
                            <p class="mt-1 font-black text-[#10233d]">{{ $order->paymentMethod?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-[#657891]">Status pembayaran</p>
                            <p class="mt-1 font-black text-[#10233d]">
                                {{ $order->payment?->status_label ?? '-' }}
                            </p>
                        </div>
                        @if ($order->shipping_service_label)
                            <div>
                                <p class="text-[#657891]">Layanan pengiriman</p>
                                <p class="mt-1 font-black text-[#10233d]">{{ $order->shipping_service_label }}</p>
                                <p class="mt-1 text-[#657891]">
                                    {{ $order->shipping_etd ? 'Estimasi ' . $order->shipping_etd : 'Estimasi belum tersedia' }}
                                </p>
                            </div>
                        @endif
                        <div>
                            <p class="text-[#657891]">Penerima</p>
                            <p class="mt-1 font-black text-[#10233d]">{{ $order->shipping_name }}</p>
                            <p class="mt-1 text-[#657891]">{{ $order->shipping_phone }}</p>
                        </div>
                        @if ($order->shipped_at)
                            <div>
                                <p class="text-[#657891]">Dikirim pada</p>
                                <p class="mt-1 font-black text-[#10233d]">{{ $order->shipped_at->format('d M Y H:i') }}</p>
                                @if ($order->status === 'dikirim')
                                    <p class="mt-1 text-[#657891]">Otomatis selesai pada {{ $order->shipped_at->copy()->addDays(3)->format('d M Y H:i') }}</p>
                                @endif
                            </div>
                        @endif
                        @if ($order->completed_at)
                            <div>
                                <p class="text-[#657891]">Selesai pada</p>
                                <p class="mt-1 font-black text-[#10233d]">{{ $order->completed_at->format('d M Y H:i') }}</p>
                                <p class="mt-1 text-[#657891]">
                                    {{ $order->completion_source === 'system' ? 'Selesai otomatis oleh sistem.' : 'Diselesaikan oleh pelanggan.' }}
                                </p>
                            </div>
                        @endif
                        <div>
                            <p class="text-[#657891]">{{ $order->isWaitingForShippingCost() ? 'Total sementara' : 'Total pembayaran' }}</p>
                            <p class="mt-1 text-lg font-black text-[#c8102e]">Rp
                                {{ number_format($order->total_amount, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    @if ($order->isWaitingForShippingCost())
                        <div class="mt-5 border-l-4 border-orange-400 bg-orange-50 px-4 py-3 text-sm font-semibold leading-6 text-orange-800">
                            Ongkos kirim untuk alamat Anda sedang menunggu konfirmasi admin. Silakan tunggu total pembayaran final sebelum mengunggah bukti pembayaran.
                        </div>
                    @endif

                    @if ($order->completion_notes)
                        <div class="mt-5 border-l-4 border-blue-400 bg-blue-50 px-4 py-3 text-sm font-semibold leading-6 text-blue-800">
                            {{ $order->completion_notes }}
                        </div>
                    @endif

                    @if ($order->notes)
                        <div class="mt-5 border-l-4 border-[#436aa6] bg-[#f5f8fc] px-4 py-3 text-sm leading-6 text-[#10233d]">
                            <p class="font-black uppercase tracking-[.12em]">Catatan Pesanan</p>
                            <p class="mt-2 whitespace-pre-line">{{ $order->notes }}</p>
                        </div>
                    @endif

                    @if ($order->payment?->status === 'ditolak')
                        <div class="mt-5 border-l-4 border-red-400 bg-red-50 px-4 py-3 text-sm font-semibold leading-6 text-red-800">
                            <p class="font-black uppercase tracking-[.12em]">Pembayaran Ditolak</p>
                            <p class="mt-2">{{ $order->payment->rejection_reason ?: 'Pembayaran ditolak oleh admin.' }}</p>
                        </div>
                    @endif
                </div>

                <div class="bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-black uppercase text-[#10233d]">Alamat Pengiriman</h2>
                    <div class="mt-4 text-sm leading-6 text-[#657891]">
                        <p class="font-black text-[#10233d]">{{ $order->shipping_name }}</p>
                        <p>{{ $order->shipping_address }}</p>
                        <p>{{ $order->shipping_village ? $order->shipping_village . ', ' : '' }}{{ $order->shipping_district }}
                        </p>
                        <p>{{ $order->shipping_city }}, {{ $order->shipping_province }}</p>
                        <p>{{ $order->shipping_postal_code ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <aside class="h-fit bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black uppercase text-[#10233d]">Produk Dipesan</h2>

                <div class="mt-6 space-y-4">
                    @foreach ($order->items as $item)
                        <div class="border-b border-[#e8eef7] pb-4">
                            <div class="flex items-start justify-between gap-4 text-sm">
                                <div>
                                    <p class="font-bold text-[#10233d]">{{ $item->product_name }}</p>
                                    <p class="mt-1 text-[#657891]">{{ $item->quantity }} x Rp
                                        {{ number_format($item->product_price, 0, ',', '.') }}</p>
                                </div>
                                <p class="shrink-0 font-black text-[#c8102e]">Rp
                                    {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 space-y-3 border-t border-[#f2c8d0] pt-4 text-sm">
                    <div class="flex justify-between">
                        <span class="text-[#657891]">Subtotal</span>
                        <span class="font-black text-[#10233d]">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                    </div>
                    @if ((float) $order->discount_amount > 0)
                        <div class="flex items-start justify-between gap-4">
                            <span class="text-[#657891]">
                                Diskon
                                @if ($order->promotion_name)
                                    <span class="mt-1 block text-xs font-semibold text-[#10233d]">{{ $order->promotion_name }}</span>
                                @endif
                            </span>
                            <span class="text-right font-black text-green-700">-Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                        <div class="flex items-start justify-between gap-4">
                            <span class="text-[#657891]">Ongkos kirim</span>
                            @if ($order->isWaitingForShippingCost())
                                <span class="text-right font-black text-orange-700">Menunggu konfirmasi admin</span>
                            @else
                                <span class="text-right font-black text-[#10233d]">
                                    Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}
                                    @if ($order->shipping_service_label)
                                        <span class="block text-xs font-semibold text-[#657891]">{{ $order->shipping_service_label }}</span>
                                    @endif
                                </span>
                            @endif
                        </div>
                    <div class="flex justify-between border-t border-[#e8eef7] pt-3">
                        <span class="text-[#657891]">{{ $order->isWaitingForShippingCost() ? 'Total sementara' : 'Total pembayaran' }}</span>
                        <span class="font-black text-[#c8102e]">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                    </div>
                </div>

                @if (! $order->isWaitingForShippingCost() && $order->status !== 'dibatalkan' && ! in_array($order->payment?->status, ['terverifikasi', 'ditolak']))
                    <div class="mt-6 border-t border-[#e8eef7] pt-5 text-sm">
                        <h3 class="text-base font-black uppercase text-[#10233d]">Detail Pembayaran</h3>

                        <dl class="mt-4 space-y-3">
                            <div>
                                <dt class="text-[#657891]">Metode pembayaran</dt>
                                <dd class="mt-1 font-black text-[#10233d]">{{ $order->paymentMethod?->name ?? '-' }}</dd>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-1">
                                <div>
                                    <dt class="text-[#657891]">Bank</dt>
                                    <dd class="mt-1 font-black text-[#10233d]">{{ $order->paymentMethod?->bank_name ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[#657891]">Atas nama</dt>
                                    <dd class="mt-1 font-black text-[#10233d]">{{ $order->paymentMethod?->account_name ?? '-' }}</dd>
                                </div>
                            </div>
                            <div>
                                <dt class="text-[#657891]">Nomor rekening</dt>
                                <dd class="mt-1 break-all text-lg font-black text-[#c8102e]">{{ $order->paymentMethod?->account_number ?? '-' }}</dd>
                            </div>
                        </dl>

                        @if ($order->paymentMethod?->instructions)
                            <div class="mt-4 border-l-4 border-[#c8102e] bg-[#fff7f8] px-4 py-3 text-xs font-semibold leading-5 text-[#657891]">
                                {{ $order->paymentMethod->instructions }}
                            </div>
                        @endif
                    </div>
                @endif

                @if ($order->received_image)
                    <div class="mt-6 rounded-xl border border-[#e8eef7] bg-[#f9fafb] p-4">
                        <p class="text-sm font-black uppercase tracking-[.12em] text-[#10233d]">Foto Bukti Produk Sampai
                        </p>
                        <img src="{{ asset('storage/' . $order->received_image) }}" alt="Foto produk sampai"
                            class="mt-4 w-full rounded-lg object-cover shadow-sm sm:h-64" />
                    </div>
                @endif

                @if ($order->status === 'dikirim')
                    <form action="{{ route('pelanggan.orders.complete', $order) }}" method="POST"
                        enctype="multipart/form-data" class="mt-7">
                        @csrf
                        @method('PATCH')

                        <label for="received_image"
                            class="text-sm font-black uppercase tracking-[.12em] text-[#10233d]">Foto Produk Sudah Sampai</label>
                        <input id="received_image" name="received_image" type="file"
                            accept="image/png,image/jpeg,image/webp"
                            class="mt-3 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-primary focus:outline-none"
                            required>
                        <x-input-error :messages="$errors->get('received_image')" class="mt-2" />

                        <button type="submit"
                            class="mt-4 flex h-11 w-full items-center justify-center gap-2 bg-green-600 text-xs font-black uppercase tracking-[.16em] text-white hover:bg-green-700">
                            <iconify-icon icon="mdi:check-circle-outline"></iconify-icon>
                            Selesai
                        </button>
                    </form>

                    <form action="{{ route('pelanggan.orders.cancel', $order) }}" method="POST" class="mt-3"
                        onsubmit="return confirm('Ajukan pengembalian untuk pesanan ini?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                            class="flex h-11 w-full items-center justify-center gap-2 bg-[#c8102e] text-xs font-black uppercase tracking-[.16em] text-white hover:bg-[#9f0d24]">
                            <iconify-icon icon="mdi:restore"></iconify-icon>
                            Dibatalkan
                        </button>
                    </form>
                @elseif ($order->isWaitingForShippingCost())
                    <div class="mt-7 bg-orange-50 px-4 py-3 text-center text-xs font-black uppercase tracking-[.12em] text-orange-800">
                        Menunggu Konfirmasi Ongkir
                    </div>
                @elseif ($order->payment?->status === 'ditolak')
                    <div class="mt-7 bg-red-50 px-4 py-3 text-center text-xs font-black uppercase tracking-[.12em] text-red-800">
                        Pembayaran Ditolak
                    </div>
                @elseif ($order->status !== 'dibatalkan' && $order->payment?->status !== 'terverifikasi')
                    <a href="{{ route('pelanggan.orders.payment-proof', $order) }}"
                        class="mt-7 flex h-11 w-full items-center justify-center gap-2 bg-[#c8102e] text-xs font-black uppercase tracking-[.16em] text-white hover:bg-[#9f0d24]">
                        <iconify-icon icon="mdi:cash-fast"></iconify-icon>
                        Bayar
                    </a>
                @endif

                <a href="{{ route('pelanggan.orders.index') }}"
                    class="mt-3 flex h-11 w-full items-center justify-center border border-[#d8e2f0] text-xs font-black uppercase tracking-[.16em] text-[#10233d] hover:border-[#c8102e] hover:text-[#c8102e]">
                    Kembali
                </a>
            </aside>
        </div>
    </section>
</x-pelanggan-layout>
