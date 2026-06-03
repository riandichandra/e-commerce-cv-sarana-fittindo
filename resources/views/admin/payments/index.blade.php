<x-admin-layout>
    <div class="flex flex-col gap-2">
        <div class="text-xs flex items-center gap-1">
            <p class="tracking-wider">ADMIN</p>
            <p>></p>
            <p class="font-bold text-primary tracking-wider">PAYMENTS</p>
        </div>

        <div class="w-full flex items-center justify-between mb-7">
            <h1 class="text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
        </div>

        <div class="bg-[#FFF1F3] p-5 w-full">
            <h2 class="font-semibold tracking-wider text-texthighlight">PAYMENT LISTS</h2>
            <form method="GET" class="mt-4 mb-4 flex items-center gap-3">
                <input type="text" name="q" placeholder="Search order number or customer"
                    value="{{ request('q') }}"
                    class="w-1/3 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-primary focus:outline-none">

                <select name="status"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-primary focus:outline-none">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                            {{ ucwords($s) }}</option>
                    @endforeach
                </select>

                <div class="flex gap-2">
                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white hover:bg-red-700 transition">Search</button>
                    <a href="{{ route('admin.payments.index') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">Reset</a>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="mt-3 w-full">
                    <thead>
                        <tr class="text-left text-sm text-gray-600 font-medium border-b border-gray-300">
                            <th class="py-3 px-3">#</th>
                            <th class="py-3 px-3">Order Number</th>
                            <th class="py-3 px-3">Customer</th>
                            <th class="py-3 px-3">Method</th>
                            <th class="py-3 px-3">Amount</th>
                            <th class="py-3 px-3">Sender</th>
                            <th class="py-3 px-3">Transfer Date</th>
                            <th class="py-3 px-3">Proof</th>
                            <th class="py-3 px-3">Status</th>
                            <th class="py-3 px-3">Verified By</th>
                            <th class="py-3 px-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            @php
                                $statusClass = match ($payment->status) {
                                    'verified' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp

                            <tr class="border-b border-gray-200 text-sm">
                                <td class="py-3 px-3">{{ $payments->firstItem() + $loop->index }}</td>
                                <td class="py-3 px-3 font-medium text-texthighlight">
                                    {{ $payment->order?->order_number ?? '-' }}
                                </td>
                                <td class="py-3 px-3">
                                    <p class="font-medium text-texthighlight">
                                        {{ $payment->order?->user?->name ?? ($payment->order?->shipping_name ?? '-') }}
                                    </p>
                                    <p class="text-xs text-gray-500">{{ $payment->order?->shipping_phone ?? '-' }}</p>
                                </td>
                                <td class="py-3 px-3">{{ $payment->paymentMethod?->name ?? '-' }}</td>
                                <td class="py-3 px-3">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                <td class="py-3 px-3">{{ $payment->sender_name ?? '-' }}</td>
                                <td class="py-3 px-3">
                                    {{ $payment->transfer_date ? $payment->transfer_date->format('d M Y') : '-' }}</td>
                                <td class="py-3 px-3">
                                    @if ($payment->proof_image)
                                        <a class="inline-flex items-center gap-1.5 bg-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700 transition"
                                            href="{{ asset('storage/' . $payment->proof_image) }}" target="_blank">
                                            <iconify-icon icon="mdi:image" class="fs-6"></iconify-icon>
                                            VIEW
                                        </a>
                                    @else
                                        <span class="text-gray-500">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3">
                                    <span class="px-2 py-1 text-xs {{ $statusClass }}">
                                        {{ ucwords(str_replace('_', ' ', $payment->status)) }}
                                    </span>
                                </td>
                                <td class="py-3 px-3">{{ $payment->verifiedBy?->name ?? '-' }}</td>
                                <td class="py-3 px-3">
                                    @if ($payment->status === 'pending')
                                        <div class="flex flex-col gap-2">
                                            <form action="{{ route('admin.payments.verify', $payment) }}"
                                                method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                    class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-green-600 px-3 py-2 text-xs font-semibold text-white hover:bg-green-700 transition">
                                                    Verify
                                                </button>
                                            </form>

                                            <form action="{{ route('admin.payments.reject', $payment) }}"
                                                method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                    class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700 transition">
                                                    Reject
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-500">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-6 px-3 text-center text-sm text-gray-500">Belum ada
                                    pembayaran.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $payments->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>
