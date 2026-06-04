<x-pelanggan-layout>
    <section class="bg-[#071d33] px-8 py-14 text-white">
        <div class="mx-auto max-w-[1290px]">
            <p class="text-xs font-black uppercase tracking-[.24em] text-[#c8d8ee]">Riwayat Pesanan Pelanggan</p>
            <h1 class="mt-4 text-4xl font-black uppercase tracking-[-.03em]">Riwayat Pesanan</h1>
        </div>
    </section>

    <section class="bg-[#f7faff] px-8 py-14">
        <div class="mx-auto max-w-[1290px]">
            <!-- Cari and Filter Form -->
            <div class="mb-8 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-black uppercase text-[#10233d]">Cari & Filter</h2>
                <form method="GET" action="{{ route('pelanggan.orders.history') }}" class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <!-- Cari -->
                        <div>
                            <label for="search" class="text-sm font-semibold text-[#10233d]">Nomor Pesanan</label>
                            <input id="search" type="text" name="search" value="{{ request('search') }}"
                                placeholder="Cari nomor pesanan..."
                                class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label for="status" class="text-sm font-semibold text-[#10233d]">Status</label>
                            <select id="status" name="status"
                                class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                                <option value="">-- Semua Status --</option>
                                <option value="menunggu_konfirmasi_ongkir"
                                    {{ request('status') === 'menunggu_konfirmasi_ongkir' ? 'selected' : '' }}>
                                    Menunggu Konfirmasi Ongkir</option>
                                <option value="belum_dibayar"
                                    {{ request('status') === 'belum_dibayar' ? 'selected' : '' }}>Belum Dibayar
                                </option>
                                <option value="menunggu_verifikasi_pembayaran"
                                    {{ request('status') === 'menunggu_verifikasi_pembayaran' ? 'selected' : '' }}>
                                    Menunggu Verifikasi</option>
                                <option value="pembayaran_dikonfirmasi"
                                    {{ request('status') === 'pembayaran_dikonfirmasi' ? 'selected' : '' }}>Pembayaran
                                    Dikonfirmasi</option>
                                <option value="diproses" {{ request('status') === 'diproses' ? 'selected' : '' }}>
                                    Diproses</option>
                                <option value="dikirim" {{ request('status') === 'dikirim' ? 'selected' : '' }}>Dikirim
                                </option>
                                <option value="selesai" {{ request('status') === 'selesai' ? 'selected' : '' }}>
                                    Selesai</option>
                                <option value="dibatalkan" {{ request('status') === 'dibatalkan' ? 'selected' : '' }}>
                                    Dibatalkan</option>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div>
                            <label for="date_from" class="text-sm font-semibold text-[#10233d]">Dari Tanggal</label>
                            <input id="date_from" type="date" name="date_from" value="{{ request('date_from') }}"
                                class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                        </div>

                        <!-- Date To -->
                        <div>
                            <label for="date_to" class="text-sm font-semibold text-[#10233d]">Sampai Tanggal</label>
                            <input id="date_to" type="date" name="date_to" value="{{ request('date_to') }}"
                                class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit"
                            class="flex items-center justify-center gap-2 rounded-lg bg-[#c8102e] px-4 py-2 text-sm font-semibold text-white hover:bg-[#9f0d24]">
                            <iconify-icon icon="mdi:magnify"></iconify-icon>
                            Cari
                        </button>
                        <a href="{{ route('pelanggan.orders.history') }}"
                            class="flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            <iconify-icon icon="mdi:refresh"></iconify-icon>
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Results Summary -->
            <div class="mb-4 text-sm text-[#657891]">
                @if (request()->hasAny(['search', 'status', 'date_from', 'date_to']))
                    <p>Menampilkan <strong>{{ $orders->count() }}</strong> dari
                        <strong>{{ $orders->total() }}</strong> pesanan</p>
                @else
                    <p>Total pesanan: <strong>{{ $orders->total() }}</strong></p>
                @endif
            </div>

            <!-- Pesanan Table -->
            <div class="overflow-x-auto bg-white shadow-sm">
                <table class="w-full">
                    <thead>
                        <tr
                            class="border-b border-gray-300 bg-[#f9fafb] text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <th class="px-4 py-3">Nomor Pesanan</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Item</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($orders as $order)
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
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-semibold text-[#10233d]">{{ $order->order_number }}</td>
                                <td class="px-4 py-3 text-sm text-[#657891]">
                                    {{ $order->created_at->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $orderStatusClass }}">
                                        {{ $order->status_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-[#657891]">{{ $order->items->count() }} item(s)</td>
                                <td class="px-4 py-3 font-semibold text-[#c8102e]">Rp
                                    {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('pelanggan.orders.show', $order) }}"
                                        class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#c8102e] hover:text-[#9f0d24]">
                                        <iconify-icon icon="mdi:eye-outline"></iconify-icon>
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <iconify-icon icon="mdi:inbox-outline"
                                            class="text-4xl text-[#9db2cf]"></iconify-icon>
                                        <p class="mt-3 text-sm font-semibold text-[#657891]">Tidak ada pesanan yang
                                            ditemukan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($orders->hasPages())
                <div class="mt-6">
                    {{ $orders->links() }}
                </div>
            @endif

            <!-- Tombol Kembali -->
            <div class="mt-6">
                <a href="{{ route('pelanggan.orders.index') }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-[#d8e2f0] px-4 py-2 text-sm font-semibold text-[#10233d] hover:border-[#c8102e] hover:text-[#c8102e]">
                    <iconify-icon icon="mdi:arrow-left"></iconify-icon>
                    Kembali
                </a>
            </div>
        </div>
    </section>
</x-pelanggan-layout>
