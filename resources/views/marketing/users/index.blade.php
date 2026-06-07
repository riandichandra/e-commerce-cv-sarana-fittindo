<x-marketing-layout>
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-1 text-xs">
            <p class="tracking-wider">{{ $pagePath[0] }}</p>
            <p>></p>
            <p class="font-bold tracking-wider text-primary">{{ $pagePath[1] }}</p>
        </div>

        <div class="mb-7 flex w-full flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.16em] text-primary">Database Pelanggan</p>
                <h1 class="text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
                <p class="mt-2 text-sm font-medium text-gray-600">Marketing hanya dapat melihat data user dengan role pelanggan.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-md border border-gray-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[.14em] text-gray-500">Total</p>
                    <p class="mt-1 text-2xl font-black text-texthighlight">{{ $totalCustomers }}</p>
                </div>
                <div class="rounded-md border border-gray-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[.14em] text-gray-500">Aktif</p>
                    <p class="mt-1 text-2xl font-black text-primary">{{ $activeCustomers }}</p>
                </div>
            </div>
        </div>

        <div class="w-full overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-[#FFF7F8] px-5 py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-bold tracking-wider text-texthighlight">LIST PELANGGAN</h2>
                        <p class="mt-1 text-sm text-gray-500">Cari dan pantau status pelanggan yang terdaftar.</p>
                    </div>
                </div>

                <form method="GET" action="{{ route('marketing.users.index') }}" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-[1fr_180px_auto]">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, email, atau telepon"
                        class="rounded-md border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                    <select name="status" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Semua Status</option>
                        <option value="active" @selected($status === 'active')>Aktif</option>
                        <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
                    </select>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary-dark">
                        <iconify-icon icon="mdi:magnify" class="fs-6"></iconify-icon>
                        FILTER
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-left">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-xs font-bold uppercase tracking-[.08em] text-gray-500">
                            <th class="w-16 px-5 py-3">No.</th>
                            <th class="px-5 py-3">Pelanggan</th>
                            <th class="px-5 py-3">Telepon</th>
                            <th class="px-5 py-3">Status Akun</th>
                            <th class="px-5 py-3">Verifikasi Email</th>
                            <th class="px-5 py-3">Terdaftar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse ($customers as $customer)
                            <tr class="align-top transition hover:bg-gray-50">
                                <td class="px-5 py-4 font-semibold text-gray-500">{{ $customers->firstItem() + $loop->index }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-bold text-texthighlight">{{ $customer->name }}</div>
                                    <div class="mt-1 text-sm text-gray-500">{{ $customer->email }}</div>
                                </td>
                                <td class="px-5 py-4 text-gray-600">{{ $customer->phone ?? '-' }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $customer->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' }}">
                                        {{ $customer->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $customer->email_verified_at ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700' }}">
                                        {{ $customer->email_verified_at ? 'Terverifikasi' : 'Belum' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-gray-600">{{ $customer->created_at->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center gap-2 text-gray-500">
                                        <iconify-icon icon="mdi:account-search-outline" class="text-4xl text-gray-300"></iconify-icon>
                                        <p class="font-semibold text-texthighlight">Pelanggan tidak ditemukan</p>
                                        <p class="text-sm">Coba ubah kata kunci atau filter status pelanggan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($customers->hasPages())
                <div class="border-t border-gray-200 px-5 py-4">
                    {{ $customers->links() }}
                </div>
            @endif
        </div>
    </div>
</x-marketing-layout>
