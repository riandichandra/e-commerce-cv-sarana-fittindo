<x-admin-layout>
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

    <div class="flex flex-col gap-2">
        <div class="text-xs flex items-center gap-1">
            <p class="tracking-wider">ADMIN</p>
            <p>></p>
            <p class="tracking-wider">PESANAN</p>
            <p>></p>
            <p class="font-bold text-primary tracking-wider">DETAIL</p>
        </div>

        <div class="w-full flex items-center justify-between mb-7">
            <h1 class="text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
            <x-button bgColor="primary" textColor="white" icon="mdi:arrow-left" size="auto"
                href="{{ route('admin.orders.index') }}">
                KEMBALI
            </x-button>
        </div>

        @if (session('success') || session('error'))
            <div
                class="mb-4 border-l-4 {{ session('success') ? 'border-green-500 bg-green-50 text-green-700' : 'border-red-500 bg-red-50 text-red-700' }} px-4 py-3 text-sm font-semibold">
                {{ session('success') ?? session('error') }}
            </div>
        @endif

        <div class="bg-[#FFF1F3] p-5 w-full">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-bold text-texthighlight">{{ $order->order_number }}</h2>
                <p class="text-sm text-gray-600">{{ $order->created_at->format('d M Y H:i') }}</p>
            </div>

            <div class="mt-5 grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="bg-white p-4">
                    <h3 class="font-semibold tracking-wider text-texthighlight">PELANGGAN</h3>
                    <div class="mt-3 text-sm text-gray-700 space-y-1">
                        <p class="font-medium text-texthighlight">{{ $order->user?->name ?? $order->shipping_name }}</p>
                        <p>{{ $order->user?->email ?? '-' }}</p>
                        <p>{{ $order->shipping_phone }}</p>
                    </div>
                </div>

                <div class="bg-white p-4">
                    <h3 class="font-semibold tracking-wider text-texthighlight">STATUS PESANAN</h3>
                    <div class="mt-3 flex flex-col gap-2 text-sm">
                        <span class="w-fit px-2 py-1 text-xs {{ $orderStatusClass }}">
                            {{ $order->status_label }}
                        </span>
                        <p>Total: <span class="font-semibold text-texthighlight">Rp
                                {{ number_format($order->total_amount, 0, ',', '.') }}</span></p>
                        @if ($order->isWaitingForShippingCost())
                            <p class="font-semibold text-orange-700">Ongkos kirim belum dikonfirmasi.</p>
                        @endif
                        <p>Item: {{ $order->items()->sum('quantity') }}</p>
                        @if ($order->shipped_at)
                            <p>Dikirim pada: <span class="font-semibold text-texthighlight">{{ $order->shipped_at->format('d M Y H:i') }}</span></p>
                            @if ($order->status === 'dikirim')
                                <p class="font-semibold text-purple-700">Otomatis selesai pada {{ $order->shipped_at->copy()->addDays(3)->format('d M Y H:i') }}.</p>
                            @endif
                        @endif
                        @if ($order->completed_at)
                            <p>Selesai pada: <span class="font-semibold text-texthighlight">{{ $order->completed_at->format('d M Y H:i') }}</span></p>
                            <p class="font-semibold text-green-700">
                                {{ $order->completion_source === 'system' ? 'Selesai otomatis oleh sistem.' : 'Diselesaikan oleh pelanggan.' }}
                            </p>
                        @endif
                        @if ($order->completion_notes)
                            <p class="text-xs text-gray-500">{{ $order->completion_notes }}</p>
                        @endif
                    </div>
                </div>

                <div class="bg-white p-4">
                    <h3 class="font-semibold tracking-wider text-texthighlight">PEMBAYARAN</h3>
                    <div class="mt-3 flex flex-col gap-2 text-sm">
                        <p>{{ $order->paymentMethod?->name ?? '-' }}</p>
                        <span class="w-fit px-2 py-1 text-xs {{ $paymentStatusClass }}">
                            {{ $order->payment?->status_label ?? 'Belum Ada' }}
                        </span>
                        <p>Amount: Rp {{ number_format($order->payment?->amount ?? 0, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            @if ($order->notes)
                <div class="mt-4 border-l-4 border-blue-400 bg-white px-4 py-3 text-sm text-gray-700">
                    <p class="font-semibold tracking-wider text-texthighlight">CATATAN PELANGGAN</p>
                    <p class="mt-2 whitespace-pre-line">{{ $order->notes }}</p>
                </div>
            @endif

            <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bg-white p-4">
                    <h3 class="font-semibold tracking-wider text-texthighlight">ALAMAT PENGIRIMAN</h3>
                    <div class="mt-3 text-sm text-gray-700 space-y-1">
                        <p class="font-medium text-texthighlight">{{ $order->shipping_name }}</p>
                        <p>{{ $order->shipping_address }}</p>
                        <p>{{ $order->shipping_village ? $order->shipping_village . ', ' : '' }}{{ $order->shipping_district }}
                        </p>
                        <p>{{ $order->shipping_city }}, {{ $order->shipping_province }}</p>
                        <p>{{ $order->shipping_postal_code ?? '-' }}</p>
                    </div>
                </div>

                <div class="bg-white p-4">
                    <h3 class="font-semibold tracking-wider text-texthighlight">ONGKOS KIRIM</h3>
                    <div class="mt-3 space-y-2 text-sm text-gray-700">
                        <div class="flex justify-between gap-4">
                            <span>Subtotal</span>
                            <span class="font-semibold text-texthighlight">Rp
                                {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if ((float) $order->discount_amount > 0)
                            <div class="flex items-start justify-between gap-4">
                                <span>
                                    Diskon
                                    @if ($order->promotion_name)
                                        <span
                                            class="block text-xs font-semibold text-gray-500">{{ $order->promotion_name }}</span>
                                    @endif
                                </span>
                                <span class="text-right font-semibold text-green-700">-Rp
                                    {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4">
                            <span>Ongkos kirim</span>
                            @if ($order->isWaitingForShippingCost())
                                <span class="font-semibold text-orange-700">Menunggu input admin</span>
                            @else
                                <span class="font-semibold text-texthighlight">Rp
                                    {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                            @endif
                        </div>
                        @if ($order->shipping_cost_source || $order->shipping_service_label || $order->shipping_weight_gram)
                            <div class="border-t border-gray-200 pt-2 text-xs text-gray-600">
                                <p>Sumber: <span class="font-semibold text-texthighlight">{{ $order->shipping_cost_source ? strtoupper(str_replace('_', ' ', $order->shipping_cost_source)) : '-' }}</span></p>
                                @if ($order->shipping_service_label)
                                    <p class="mt-1">Layanan: <span class="font-semibold text-texthighlight">{{ $order->shipping_service_label }}</span></p>
                                @endif
                                @if ($order->shipping_etd)
                                    <p class="mt-1">Estimasi: <span class="font-semibold text-texthighlight">{{ $order->shipping_etd }}</span></p>
                                @endif
                                @if ($order->shipping_weight_gram)
                                    <p class="mt-1">Berat: <span class="font-semibold text-texthighlight">{{ number_format($order->shipping_weight_gram, 0, ',', '.') }} gram</span></p>
                                @endif
                                @if ($order->shipping_origin_district_id || $order->shipping_destination_district_id)
                                    <p class="mt-1">Origin/Destination: <span class="font-semibold text-texthighlight">{{ $order->shipping_origin_district_id ?? '-' }} / {{ $order->shipping_destination_district_id ?? '-' }}</span></p>
                                @endif
                            </div>
                        @endif
                        <div class="flex justify-between gap-4 border-t border-gray-200 pt-2">
                            <span>Total pembayaran</span>
                            <span class="font-semibold text-primary">Rp
                                {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    @if ($order->isWaitingForShippingCost())
                        <form action="{{ route('admin.orders.shipping-cost.update', $order) }}" method="POST"
                            class="mt-4 space-y-3">
                            @csrf
                            @method('PATCH')

                            <div>
                                <label for="shipping_cost" class="text-sm font-semibold text-texthighlight">Input Ongkos
                                    Kirim</label>
                                <input id="shipping_cost" name="shipping_cost" type="number" min="0"
                                    step="1000" value="{{ old('shipping_cost') }}"
                                    class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-primary focus:outline-none"
                                    required>
                                <x-input-error :messages="$errors->get('shipping_cost')" class="mt-2" />
                            </div>

                            <button type="submit"
                                class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-white hover:bg-red-700 transition">
                                KONFIRMASI ONGKIR
                            </button>
                        </form>
                    @elseif ($order->shipping_cost_confirmed_at)
                        <p class="mt-3 text-xs font-semibold text-gray-500">
                            Dikonfirmasi pada {{ $order->shipping_cost_confirmed_at->format('d M Y H:i') }}.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-4 overflow-hidden border border-[#f2c8d0] bg-white shadow-sm">
            <div class="border-b border-[#f2c8d0] bg-[#fff7f8] p-5">
                <h3 class="text-lg font-black tracking-wide text-texthighlight">Bukti Pesanan</h3>
                <p class="mt-1 text-sm text-gray-600">Bukti pembayaran dan bukti barang diterima pelanggan.</p>
            </div>

            <div class="grid grid-cols-1 gap-5 p-5 xl:grid-cols-2">
                <div class="border border-gray-200 bg-white p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h4 class="font-black tracking-wide text-texthighlight">Bukti Pembayaran</h4>
                            <p class="mt-1 text-sm text-gray-500">Bukti transfer yang diunggah pelanggan.</p>
                        </div>
                        <span class="w-fit px-2.5 py-1 text-xs font-bold {{ $paymentStatusClass }}">
                            {{ $order->payment?->status_label ?? 'Belum Ada' }}
                        </span>
                    </div>

                    @if ($order->payment?->proof_image)
                        <img src="{{ asset('storage/' . $order->payment->proof_image) }}" alt="Bukti pembayaran"
                            class="mt-4 aspect-video w-full border border-gray-200 object-cover">

                        <div class="mt-4 grid gap-3 text-sm text-gray-700 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Pengirim</p>
                                <p class="mt-1 font-semibold text-texthighlight">
                                    {{ $order->payment->sender_name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Tanggal Transfer</p>
                                <p class="mt-1 font-semibold text-texthighlight">
                                    {{ $order->payment->transfer_date ? $order->payment->transfer_date->format('d M Y') : '-' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Nominal</p>
                                <p class="mt-1 font-semibold text-texthighlight">Rp
                                    {{ number_format($order->payment->amount, 0, ',', '.') }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Diverifikasi</p>
                                <p class="mt-1 font-semibold text-texthighlight">
                                    {{ $order->payment->verifiedBy?->name ?? '-' }}</p>
                                @if ($order->payment->verified_at)
                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ $order->payment->verified_at->format('d M Y H:i') }}</p>
                                @endif
                            </div>
                        </div>

                        @if ($order->payment->rejection_reason || $order->payment->notes)
                            <div
                                class="mt-4 border-l-4 border-yellow-400 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                                {{ $order->payment->rejection_reason ?: $order->payment->notes }}
                            </div>
                        @endif

                        <a href="{{ asset('storage/' . $order->payment->proof_image) }}" target="_blank"
                            class="mt-4 inline-flex h-10 items-center justify-center gap-2 bg-primary px-4 text-xs font-bold text-white transition hover:bg-red-700">
                            <iconify-icon icon="mdi:open-in-new" class="fs-6"></iconify-icon>
                            Lihat Bukti
                        </a>
                    @else
                        <div
                            class="mt-5 flex min-h-[260px] flex-col items-center justify-center border border-dashed border-gray-300 bg-gray-50 p-6 text-center">
                            <div class="flex h-14 w-14 items-center justify-center bg-[#fff1f3] text-primary">
                                <iconify-icon icon="mdi:receipt-text-outline" class="fs-3"></iconify-icon>
                            </div>
                            <p class="mt-4 font-bold text-texthighlight">Bukti pembayaran belum diunggah.</p>
                            <p class="mt-2 text-sm text-gray-500">Pelanggan belum mengirimkan bukti transfer untuk
                                pesanan ini.</p>
                        </div>
                    @endif
                </div>

                <div class="border border-gray-200 bg-white p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h4 class="font-black tracking-wide text-texthighlight">Bukti Barang Diterima</h4>
                            <p class="mt-1 text-sm text-gray-500">Foto bukti dari pelanggan saat pesanan diselesaikan.
                            </p>
                        </div>
                        <span class="w-fit px-2.5 py-1 text-xs font-bold {{ $orderStatusClass }}">
                            {{ $order->status_label }}
                        </span>
                    </div>

                    @if ($order->received_image)
                        <img src="{{ asset('storage/' . $order->received_image) }}" alt="Bukti barang diterima"
                            class="mt-4 aspect-video w-full border border-gray-200 object-cover">

                        <div class="mt-4 grid gap-3 text-sm text-gray-700 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Pelanggan</p>
                                <p class="mt-1 font-semibold text-texthighlight">
                                    {{ $order->user?->name ?? $order->shipping_name }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Diupload/Diupdate
                                </p>
                                <p class="mt-1 font-semibold text-texthighlight">
                                    {{ $order->updated_at->format('d M Y H:i') }}</p>
                            </div>
                        </div>

                        <a href="{{ asset('storage/' . $order->received_image) }}" target="_blank"
                            class="mt-4 inline-flex h-10 items-center justify-center gap-2 bg-primary px-4 text-xs font-bold text-white transition hover:bg-red-700">
                            <iconify-icon icon="mdi:open-in-new" class="fs-6"></iconify-icon>
                            Lihat Bukti
                        </a>
                    @else
                        <div
                            class="mt-5 flex min-h-[260px] flex-col items-center justify-center border border-dashed border-gray-300 bg-gray-50 p-6 text-center">
                            <div class="flex h-14 w-14 items-center justify-center bg-[#fff1f3] text-primary">
                                <iconify-icon icon="mdi:package-variant-closed-check" class="fs-3"></iconify-icon>
                            </div>
                            <p class="mt-4 font-bold text-texthighlight">Bukti barang diterima belum tersedia.</p>
                            <p class="mt-2 text-sm text-gray-500">
                                {{ $order->completion_source === 'system'
                                    ? 'Pesanan selesai otomatis setelah 3 hari sejak dikirim, tanpa foto bukti dari pelanggan.'
                                    : ($order->status === 'selesai'
                                    ? 'Pesanan selesai, tetapi foto bukti belum tersimpan.'
                                    : 'Bukti akan tersedia setelah pelanggan menandai pesanan selesai.') }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-4 overflow-hidden border border-[#f2c8d0] bg-white shadow-sm">
            <div class="border-b border-[#f2c8d0] bg-[#fff7f8] p-5">
                <h3 class="text-lg font-black tracking-wide text-texthighlight">Item Pesanan</h3>
                <p class="mt-1 text-sm text-gray-600">Daftar produk yang dibeli pada pesanan ini.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px]">
                    <thead>
                        <tr
                            class="border-b border-gray-200 bg-white text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                            <th class="w-16 px-5 py-4">No.</th>
                            <th class="px-5 py-4">Produk</th>
                            <th class="px-5 py-4">Harga</th>
                            <th class="px-5 py-4">Qty</th>
                            <th class="px-5 py-4">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($items as $item)
                            <tr class="text-sm transition hover:bg-[#fff7f8]">
                                <td class="px-5 py-4 font-semibold text-gray-500">
                                    {{ $items->firstItem() + $loop->index }}</td>
                                <td class="px-5 py-4 font-bold text-texthighlight">{{ $item->product_name }}</td>
                                <td class="px-5 py-4">Rp {{ number_format($item->product_price, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-4">{{ $item->quantity }}</td>
                                <td class="px-5 py-4 font-black text-texthighlight">Rp
                                    {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-14 text-center">
                                    <div class="mx-auto flex max-w-md flex-col items-center">
                                        <div
                                            class="flex h-14 w-14 items-center justify-center bg-[#fff1f3] text-primary">
                                            <iconify-icon icon="mdi:package-variant-closed"
                                                class="fs-3"></iconify-icon>
                                        </div>
                                        <p class="mt-4 text-base font-bold text-texthighlight">Belum ada item pesanan.
                                        </p>
                                        <p class="mt-2 text-sm text-gray-500">Produk yang dipesan akan tampil di tabel
                                            ini.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($items->hasPages())
                <div class="border-t border-gray-100 px-5 py-4">
                    {{ $items->links() }}
                </div>
            @endif
        </div>
    </div>
    </div>
</x-admin-layout>
