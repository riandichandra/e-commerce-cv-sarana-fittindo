<x-gm-layout>
    @php
        $orderStatuses = [
            'pending_payment' => 'Pending Payment',
            'waiting_payment_confirmation' => 'Waiting Payment Confirmation',
            'payment_confirmed' => 'Payment Confirmed',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        $paymentStatuses = [
            'pending' => 'Pending',
            'verified' => 'Verified',
            'rejected' => 'Rejected',
        ];

        $statusClass = fn (?string $status) => match ($status) {
            'pending_payment' => 'bg-yellow-100 text-yellow-800',
            'waiting_payment_confirmation' => 'bg-blue-100 text-blue-800',
            'payment_confirmed' => 'bg-emerald-100 text-emerald-800',
            'processing' => 'bg-indigo-100 text-indigo-800',
            'shipped' => 'bg-purple-100 text-purple-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled', 'rejected' => 'bg-red-100 text-red-800',
            'verified' => 'bg-green-100 text-green-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-700',
        };
    @endphp

    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-1 text-xs">
            <p class="tracking-wider">{{ $pagePath[0] }}</p>
            <p>></p>
            <p class="font-bold tracking-wider text-primary">{{ $pagePath[1] }}</p>
        </div>

        <div class="mb-7 flex w-full flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
                <p class="mt-2 text-sm font-medium text-gray-600">Laporan order dan pembayaran. Download Excel hanya tersedia untuk role GM.</p>
            </div>
            <a href="{{ route('gm.reports.download', request()->query()) }}"
                class="inline-flex items-center justify-center gap-2 bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary-dark">
                <iconify-icon icon="mdi:microsoft-excel" class="fs-5"></iconify-icon>
                DOWNLOAD EXCEL
            </a>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[.14em] text-gray-500">Total Orders</p>
                <p class="mt-2 text-3xl font-black text-texthighlight">{{ $summary['total_orders'] }}</p>
            </div>
            <div class="bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[.14em] text-gray-500">Total Amount</p>
                <p class="mt-2 text-2xl font-black text-texthighlight">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[.14em] text-gray-500">Verified Revenue</p>
                <p class="mt-2 text-2xl font-black text-primary">Rp {{ number_format($summary['verified_revenue'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[.14em] text-gray-500">Discount</p>
                <p class="mt-2 text-2xl font-black text-texthighlight">Rp {{ number_format($summary['total_discount'], 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="mt-5 w-full bg-[#FFF1F3] p-5">
            <form method="GET" action="{{ route('gm.reports.index') }}" class="mb-5 grid grid-cols-1 gap-3 lg:grid-cols-[1fr_1fr_220px_180px_auto]">
                <input type="date" name="start_date" value="{{ $filters['start_date'] }}"
                    class="border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                <input type="date" name="end_date" value="{{ $filters['end_date'] }}"
                    class="border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                <select name="status" class="border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                    <option value="">All Order Status</option>
                    @foreach ($orderStatuses as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="payment_status" class="border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                    <option value="">All Payment</option>
                    @foreach ($paymentStatuses as $value => $label)
                        <option value="{{ $value }}" @selected($filters['payment_status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex items-center justify-center gap-2 bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary-dark">
                    <iconify-icon icon="mdi:filter" class="fs-6"></iconify-icon>
                    FILTER
                </button>
            </form>

            <h2 class="font-semibold tracking-wider text-texthighlight">LAPORAN ORDER</h2>
            <div class="overflow-x-auto">
                <table class="mt-3 w-full">
                    <thead>
                        <tr class="border-b border-gray-300 text-left text-sm font-medium text-gray-600">
                            <th class="px-3 py-3">#</th>
                            <th class="px-3 py-3">Order</th>
                            <th class="px-3 py-3">Customer</th>
                            <th class="px-3 py-3">Items</th>
                            <th class="px-3 py-3">Subtotal</th>
                            <th class="px-3 py-3">Discount</th>
                            <th class="px-3 py-3">Total</th>
                            <th class="px-3 py-3">Payment</th>
                            <th class="px-3 py-3">Order Status</th>
                            <th class="px-3 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr class="border-b border-gray-200 text-sm">
                                <td class="px-3 py-3">{{ $orders->firstItem() + $loop->index }}</td>
                                <td class="px-3 py-3 font-medium text-texthighlight">{{ $order->order_number }}</td>
                                <td class="px-3 py-3">
                                    <p class="font-medium text-texthighlight">{{ $order->user?->name ?? $order->shipping_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $order->shipping_phone }}</p>
                                </td>
                                <td class="px-3 py-3">{{ $order->items->sum('quantity') }}</td>
                                <td class="px-3 py-3">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</td>
                                <td class="px-3 py-3">Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</td>
                                <td class="px-3 py-3 font-bold">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                <td class="px-3 py-3">
                                    <span class="px-2 py-1 text-xs {{ $statusClass($order->payment?->status) }}">
                                        {{ $order->payment ? ucfirst($order->payment->status) : 'Belum Ada' }}
                                    </span>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="px-2 py-1 text-xs {{ $statusClass($order->status) }}">
                                        {{ ucwords(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3">{{ $order->created_at->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-3 py-6 text-center text-sm text-gray-500">Tidak ada data laporan.</td>
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
</x-gm-layout>
