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
            <p class="tracking-wider">ORDERS</p>
            <p>></p>
            <p class="font-bold text-primary tracking-wider">DETAIL</p>
        </div>

        <div class="w-full flex items-center justify-between mb-7">
            <h1 class="text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
            <x-button bgColor="primary" textColor="white" icon="mdi:arrow-left" size="auto"
                href="{{ route('admin.orders.index') }}">
                BACK
            </x-button>
        </div>

        @if (session('success') || session('error'))
            <div class="mb-4 border-l-4 {{ session('success') ? 'border-green-500 bg-green-50 text-green-700' : 'border-red-500 bg-red-50 text-red-700' }} px-4 py-3 text-sm font-semibold">
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
                    <h3 class="font-semibold tracking-wider text-texthighlight">CUSTOMER</h3>
                    <div class="mt-3 text-sm text-gray-700 space-y-1">
                        <p class="font-medium text-texthighlight">{{ $order->user?->name ?? $order->shipping_name }}</p>
                        <p>{{ $order->user?->email ?? '-' }}</p>
                        <p>{{ $order->shipping_phone }}</p>
                    </div>
                </div>

                <div class="bg-white p-4">
                    <h3 class="font-semibold tracking-wider text-texthighlight">ORDER STATUS</h3>
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
                    </div>
                </div>

                <div class="bg-white p-4">
                    <h3 class="font-semibold tracking-wider text-texthighlight">PAYMENT</h3>
                    <div class="mt-3 flex flex-col gap-2 text-sm">
                        <p>{{ $order->paymentMethod?->name ?? '-' }}</p>
                        <span class="w-fit px-2 py-1 text-xs {{ $paymentStatusClass }}">
                            {{ $order->payment?->status_label ?? 'Belum Ada' }}
                        </span>
                        <p>Amount: Rp {{ number_format($order->payment?->amount ?? 0, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bg-white p-4">
                    <h3 class="font-semibold tracking-wider text-texthighlight">SHIPPING ADDRESS</h3>
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
                    <h3 class="font-semibold tracking-wider text-texthighlight">SHIPPING COST</h3>
                    <div class="mt-3 space-y-2 text-sm text-gray-700">
                        <div class="flex justify-between gap-4">
                            <span>Subtotal</span>
                            <span class="font-semibold text-texthighlight">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if ((float) $order->discount_amount > 0)
                            <div class="flex items-start justify-between gap-4">
                                <span>
                                    Diskon
                                    @if ($order->promotion_name)
                                        <span class="block text-xs font-semibold text-gray-500">{{ $order->promotion_name }}</span>
                                    @endif
                                </span>
                                <span class="text-right font-semibold text-green-700">-Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4">
                            <span>Ongkos kirim</span>
                            @if ($order->isWaitingForShippingCost())
                                <span class="font-semibold text-orange-700">Menunggu input admin</span>
                            @else
                                <span class="font-semibold text-texthighlight">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                            @endif
                        </div>
                        <div class="flex justify-between gap-4 border-t border-gray-200 pt-2">
                            <span>Total pembayaran</span>
                            <span class="font-semibold text-primary">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    @if ($order->isWaitingForShippingCost())
                        <form action="{{ route('admin.orders.shipping-cost.update', $order) }}" method="POST" class="mt-4 space-y-3">
                            @csrf
                            @method('PATCH')

                            <div>
                                <label for="shipping_cost" class="text-sm font-semibold text-texthighlight">Input Ongkos Kirim</label>
                                <input id="shipping_cost" name="shipping_cost" type="number" min="0" step="1000"
                                    value="{{ old('shipping_cost') }}"
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

        @if ($order->received_image)
            <div class="mt-4 bg-white p-4">
                <h3 class="font-semibold tracking-wider text-texthighlight mb-3">BUKTI PENERIMAAN BARANG</h3>
                <div class="flex flex-col gap-3">
                    <img src="{{ asset('storage/' . $order->received_image) }}" alt="Bukti penerimaan barang"
                        class="w-full max-w-md rounded-lg object-cover shadow-sm border border-gray-300">
                    <p class="text-sm text-gray-600">Diupload pada: {{ $order->updated_at->format('d M Y H:i') }}
                    </p>
                </div>
            </div>
        @endif

        <div class="mt-4 bg-white p-4">
            <h3 class="font-semibold tracking-wider text-texthighlight">ORDER ITEMS</h3>
            <div class="overflow-x-auto">
                <table class="mt-3 w-full">
                    <thead>
                        <tr class="text-left text-sm text-gray-600 font-medium border-b border-gray-300">
                            <th class="py-3 px-3">#</th>
                            <th class="py-3 px-3">Produk</th>
                            <th class="py-3 px-3">Harga</th>
                            <th class="py-3 px-3">Qty</th>
                            <th class="py-3 px-3">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr class="border-b border-gray-200 text-sm">
                                <td class="py-3 px-3">{{ $items->firstItem() + $loop->index }}</td>
                                <td class="py-3 px-3 font-medium text-texthighlight">{{ $item->product_name }}</td>
                                <td class="py-3 px-3">Rp {{ number_format($item->product_price, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-3">{{ $item->quantity }}</td>
                                <td class="py-3 px-3">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 px-3 text-center text-sm text-gray-500">Belum ada
                                    item pesanan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $items->links() }}
            </div>
        </div>
    </div>
    </div>
</x-admin-layout>
