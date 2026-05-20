<x-admin-layout>
    @php
        $orderStatusClass = match ($order->status) {
            'pending_payment' => 'bg-yellow-100 text-yellow-800',
            'waiting_payment_confirmation' => 'bg-blue-100 text-blue-800',
            'payment_confirmed' => 'bg-emerald-100 text-emerald-800',
            'processing' => 'bg-indigo-100 text-indigo-800',
            'shipped' => 'bg-purple-100 text-purple-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-700',
        };

        $paymentStatusClass = match ($order->payment?->status) {
            'verified' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
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
                            {{ ucwords(str_replace('_', ' ', $order->status)) }}
                        </span>
                        <p>Total: <span class="font-semibold text-texthighlight">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span></p>
                        <p>Items: {{ $order->items->sum('quantity') }}</p>
                    </div>
                </div>

                <div class="bg-white p-4">
                    <h3 class="font-semibold tracking-wider text-texthighlight">PAYMENT</h3>
                    <div class="mt-3 flex flex-col gap-2 text-sm">
                        <p>{{ $order->paymentMethod?->name ?? '-' }}</p>
                        <span class="w-fit px-2 py-1 text-xs {{ $paymentStatusClass }}">
                            {{ $order->payment?->status ? ucwords(str_replace('_', ' ', $order->payment->status)) : 'Belum Ada' }}
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
                        <p>{{ $order->shipping_village ? $order->shipping_village . ', ' : '' }}{{ $order->shipping_district }}</p>
                        <p>{{ $order->shipping_city }}, {{ $order->shipping_province }}</p>
                        <p>{{ $order->shipping_postal_code ?? '-' }}</p>
                    </div>
                </div>

                <div class="bg-white p-4">
                    <h3 class="font-semibold tracking-wider text-texthighlight">DELIVERY</h3>
                    <div class="mt-3 text-sm text-gray-700 space-y-1">
                        <p>Status: {{ $order->delivery?->status ? ucwords(str_replace('_', ' ', $order->delivery->status)) : 'Belum Ada' }}</p>
                        <p>Courier: {{ $order->delivery?->courier ?? '-' }}</p>
                        <p>Tracking: {{ $order->delivery?->tracking_number ?? '-' }}</p>
                        <p>Estimated Arrival: {{ $order->delivery?->estimated_arrival ? $order->delivery->estimated_arrival->format('d M Y') : '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-4 bg-white p-4">
                <h3 class="font-semibold tracking-wider text-texthighlight">ORDER ITEMS</h3>
                <div class="overflow-x-auto">
                    <table class="mt-3 w-full">
                        <thead>
                            <tr class="text-left text-sm text-gray-600 font-medium border-b border-gray-300">
                                <th class="py-3 px-3">#</th>
                                <th class="py-3 px-3">Product</th>
                                <th class="py-3 px-3">Price</th>
                                <th class="py-3 px-3">Qty</th>
                                <th class="py-3 px-3">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($order->items as $item)
                                <tr class="border-b border-gray-200 text-sm">
                                    <td class="py-3 px-3">{{ $loop->iteration }}</td>
                                    <td class="py-3 px-3 font-medium text-texthighlight">{{ $item->product_name }}</td>
                                    <td class="py-3 px-3">Rp {{ number_format($item->product_price, 0, ',', '.') }}</td>
                                    <td class="py-3 px-3">{{ $item->quantity }}</td>
                                    <td class="py-3 px-3">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 px-3 text-center text-sm text-gray-500">Belum ada item pesanan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
