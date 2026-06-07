<x-admin-layout>
    <div class="flex flex-col gap-6">
        <div class="flex items-center gap-1 text-xs">
            <p class="tracking-wider">ADMIN</p>
            <p>></p>
            <p class="font-bold tracking-wider text-primary">PEMBAYARAN</p>
        </div>

        <div class="flex w-full flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <p class="text-sm font-semibold text-primary">Manajemen pembayaran</p>
                <h1 class="mt-1 text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
            </div>
        </div>

        <div class="w-full overflow-hidden border border-[#f2c8d0] bg-white shadow-sm">
            <div class="border-b border-[#f2c8d0] bg-[#fff7f8] p-5">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-lg font-black tracking-wide text-texthighlight">Daftar Pembayaran</h2>
                        <p class="mt-1 text-sm text-gray-600">Verifikasi pembayaran pelanggan dan pantau bukti transfer.
                        </p>
                    </div>
                    <p class="text-sm font-semibold text-gray-600">
                        Menampilkan {{ $payments->count() }} dari {{ number_format($payments->total(), 0, ',', '.') }}
                        pembayaran
                    </p>
                </div>

                <form method="GET" class="mt-4 flex flex-col gap-3 xl:flex-row xl:items-center">
                    <input type="text" name="q" placeholder="Cari nomor pesanan atau pelanggan"
                        value="{{ request('q') }}"
                        class="w-full border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-primary focus:outline-none xl:w-1/3">

                    <select name="status"
                        class="w-full border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-primary focus:outline-none xl:w-auto">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                                {{ ucwords($s) }}</option>
                        @endforeach
                    </select>

                    <div class="flex gap-2">
                        <button type="submit"
                            class="inline-flex h-10 items-center justify-center bg-primary px-4 text-xs font-bold text-white transition hover:bg-red-700">Cari</button>
                        <a href="{{ route('admin.payments.index') }}"
                            class="inline-flex h-10 items-center justify-center border border-gray-300 bg-white px-4 text-xs font-bold text-gray-700 transition hover:bg-gray-50">Reset</a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1260px]">
                    <thead>
                        <tr
                            class="border-b border-gray-200 bg-white text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                            <th class="w-16 px-5 py-4">No.</th>
                            <th class="px-5 py-4">Pesanan</th>
                            <th class="px-5 py-4">Pelanggan</th>
                            <th class="px-5 py-4">Metode</th>
                            <th class="px-5 py-4">Nominal</th>
                            <th class="px-5 py-4">Pengirim</th>
                            <th class="px-5 py-4">Tanggal Transfer</th>
                            <th class="px-5 py-4">Bukti</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4">Diverifikasi</th>
                            <th class="px-5 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($payments as $payment)
                            @php
                                $statusClass = match ($payment->status) {
                                    'terverifikasi' => 'bg-green-100 text-green-800',
                                    'ditolak' => 'bg-red-100 text-red-800',
                                    'menunggu' => 'bg-yellow-100 text-yellow-800',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp

                            <tr class="text-sm transition hover:bg-[#fff7f8]">
                                <td class="px-5 py-4 align-top font-semibold text-gray-500">
                                    {{ $payments->firstItem() + $loop->index }}</td>
                                <td class="px-5 py-4 font-bold text-texthighlight">
                                    {{ $payment->order?->order_number ?? '-' }}
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-bold text-texthighlight">
                                        {{ $payment->order?->user?->name ?? ($payment->order?->shipping_name ?? '-') }}
                                    </p>
                                    <p class="text-xs text-gray-500">{{ $payment->order?->shipping_phone ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-4 font-semibold text-gray-800">
                                    {{ $payment->paymentMethod?->name ?? '-' }}</td>
                                <td class="px-5 py-4 font-black text-texthighlight">Rp
                                    {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                <td class="px-5 py-4">{{ $payment->sender_name ?? '-' }}</td>
                                <td class="px-5 py-4">
                                    {{ $payment->transfer_date ? $payment->transfer_date->format('d M Y') : '-' }}</td>
                                <td class="px-5 py-4">
                                    @if ($payment->proof_image)
                                        <a class="inline-flex h-9 items-center justify-center gap-1.5 bg-primary px-3 text-xs font-bold text-white transition hover:bg-red-700"
                                            href="{{ asset('storage/' . $payment->proof_image) }}" target="_blank">
                                            <iconify-icon icon="mdi:image" class="fs-6"></iconify-icon>
                                            VIEW
                                        </a>
                                    @else
                                        <span class="text-gray-500">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <span class="px-2.5 py-1 text-xs font-bold {{ $statusClass }}">
                                        {{ $payment->status_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-gray-800">{{ $payment->verifiedBy?->name ?? '-' }}</p>
                                    @if ($payment->verified_at)
                                        <p class="mt-1 text-xs text-gray-500">
                                            {{ $payment->verified_at->format('d M Y H:i') }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @if ($payment->status === 'menunggu')
                                        <div class="flex flex-col gap-2">
                                            <form action="{{ route('admin.payments.verify', $payment) }}"
                                                method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                    class="inline-flex w-full items-center justify-center gap-2 bg-green-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-green-700">
                                                    Terima
                                                </button>
                                            </form>

                                            <form action="{{ route('admin.payments.reject', $payment) }}"
                                                method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                    class="inline-flex w-full items-center justify-center gap-2 bg-red-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-red-700">
                                                    Tolak
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
                                <td colspan="11" class="px-5 py-14 text-center">
                                    <div class="mx-auto flex max-w-md flex-col items-center">
                                        <div
                                            class="flex h-14 w-14 items-center justify-center bg-[#fff1f3] text-primary">
                                            <iconify-icon icon="mdi:cash-check" class="fs-3"></iconify-icon>
                                        </div>
                                        <p class="mt-4 text-base font-bold text-texthighlight">Belum ada pembayaran.</p>
                                        <p class="mt-2 text-sm text-gray-500">Data pembayaran pelanggan akan muncul di
                                            halaman ini.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($payments->hasPages())
                <div class="border-t border-gray-100 px-5 py-4">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
