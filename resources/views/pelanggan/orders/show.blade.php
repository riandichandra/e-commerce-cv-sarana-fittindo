<x-pelanggan-layout>
    <section class="bg-[#071d33] px-8 py-14 text-white">
        <div class="mx-auto max-w-[1290px]">
            <p class="text-xs font-black uppercase tracking-[.24em] text-[#c8d8ee]">Pesanan Detail</p>
            <h1 class="mt-4 text-4xl font-black uppercase tracking-[-.03em]">Detail Pesanan</h1>
        </div>
    </section>

    <section class="bg-[#f7faff] px-8 py-14">
        <div class="mx-auto grid max-w-[1290px] grid-cols-1 gap-8 lg:grid-cols-[1fr_420px]">
            @if (session('success') || session('error'))
                <div class="border-l-4 {{ session('success') ? 'border-green-500 bg-green-50 text-green-700' : 'border-red-500 bg-red-50 text-red-700' }} px-4 py-3 text-sm font-semibold lg:col-span-2">
                    {{ session('success') ?? session('error') }}
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
                        <div>
                            <p class="text-[#657891]">Penerima</p>
                            <p class="mt-1 font-black text-[#10233d]">{{ $order->shipping_name }}</p>
                            <p class="mt-1 text-[#657891]">{{ $order->shipping_phone }}</p>
                        </div>
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
                    <div class="flex items-start justify-between gap-4">
                        <span class="text-[#657891]">Ongkos kirim</span>
                        @if ($order->isWaitingForShippingCost())
                            <span class="text-right font-black text-orange-700">Menunggu konfirmasi admin</span>
                        @else
                            <span class="font-black text-[#10233d]">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                        @endif
                    </div>
                    <div class="flex justify-between border-t border-[#e8eef7] pt-3">
                        <span class="text-[#657891]">{{ $order->isWaitingForShippingCost() ? 'Total sementara' : 'Total pembayaran' }}</span>
                        <span class="font-black text-[#c8102e]">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                    </div>
                </div>

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
                @elseif ($order->payment?->status !== 'terverifikasi')
                    <a href="{{ route('pelanggan.orders.payment-proof', $order) }}"
                        class="mt-7 flex h-11 w-full items-center justify-center gap-2 bg-[#c8102e] text-xs font-black uppercase tracking-[.16em] text-white hover:bg-[#9f0d24]">
                        <iconify-icon icon="mdi:upload-outline"></iconify-icon>
                        Unggah Bukti
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
