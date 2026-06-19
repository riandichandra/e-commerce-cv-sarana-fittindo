<x-admin-layout>
    <div class="flex flex-col gap-6">
        <div class="flex items-center gap-1 text-xs">
            <p class="tracking-wider">ADMIN</p>
            <p>></p>
            <p class="tracking-wider">PEMBAYARAN</p>
            <p>></p>
            <p class="font-bold tracking-wider text-primary">METODE PEMBAYARAN</p>
        </div>

        <div class="flex w-full flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <p class="text-sm font-semibold text-primary">Manajemen pembayaran</p>
                <h1 class="mt-1 text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
            </div>
            <x-button bgColor="primary" textColor="white" icon="mdi:plus" size="auto"
                href="{{ route('admin.payment-methods.create') }}">
                TAMBAH METODE PEMBAYARAN
            </x-button>
        </div>

        <div class="w-full overflow-hidden border border-[#f2c8d0] bg-white shadow-sm">
            <div
                class="flex flex-col gap-3 border-b border-[#f2c8d0] bg-[#fff7f8] p-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-black tracking-wide text-texthighlight">Daftar Rekening Bank</h2>
                    <p class="mt-1 text-sm text-gray-600">Kelola rekening dan metode pembayaran yang bisa dipilih
                        pelanggan.</p>
                </div>
                <p class="text-sm font-semibold text-gray-600">
                    Menampilkan {{ $paymentMethods->count() }} dari
                    {{ number_format($paymentMethods->total(), 0, ',', '.') }} rekening
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[920px]">
                    <thead>
                        <tr
                            class="border-b border-gray-200 bg-white text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                            <th class="w-16 px-5 py-4">No.</th>
                            <th class="px-5 py-4">Metode</th>
                            <th class="px-5 py-4">Bank</th>
                            <th class="px-5 py-4">Nomor Rekening</th>
                            <th class="px-5 py-4">Nama Rekening</th>
                            <th class="px-5 py-4">Urutan</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($paymentMethods as $paymentMethod)
                            <tr class="text-sm transition hover:bg-[#fff7f8]">
                                <td class="px-5 py-4 align-top font-semibold text-gray-500">
                                    {{ $paymentMethods->firstItem() + $loop->index }}</td>
                                <td class="px-5 py-4">
                                    <p class="font-bold text-texthighlight">{{ $paymentMethod->name }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ $paymentMethod->code }}</p>
                                </td>
                                <td class="px-5 py-4 font-semibold text-gray-800">{{ $paymentMethod->bank_name }}</td>
                                <td class="px-5 py-4 font-mono text-sm text-gray-700">
                                    {{ $paymentMethod->account_number }}</td>
                                <td class="px-5 py-4">{{ $paymentMethod->account_name }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-700">
                                        {{ $paymentMethod->sort_order }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="px-2.5 py-1 text-xs font-bold {{ $paymentMethod->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $paymentMethod->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a class="inline-flex h-9 items-center justify-center gap-1.5 bg-primary px-3 text-xs font-bold text-white transition hover:bg-red-700"
                                        href="{{ route('admin.payment-methods.edit', $paymentMethod) }}">
                                        <iconify-icon icon="mdi:pencil" class="fs-6"></iconify-icon>
                                        EDIT
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-14 text-center">
                                    <div class="mx-auto flex max-w-md flex-col items-center">
                                        <div
                                            class="flex h-14 w-14 items-center justify-center bg-[#fff1f3] text-primary">
                                            <iconify-icon icon="mdi:bank-outline" class="fs-3"></iconify-icon>
                                        </div>
                                        <p class="mt-4 text-base font-bold text-texthighlight">Belum ada rekening bank.
                                        </p>
                                        <p class="mt-2 text-sm text-gray-500">Tambahkan rekening agar pelanggan dapat
                                            memilih metode pembayaran.</p>
                                        <a href="{{ route('admin.payment-methods.create') }}"
                                            class="mt-5 inline-flex h-10 items-center justify-center gap-2 bg-primary px-4 text-sm font-bold text-white transition hover:bg-red-700">
                                            <iconify-icon icon="mdi:plus" class="fs-6"></iconify-icon>
                                            Tambah Rekening
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($paymentMethods->hasPages())
                <div class="border-t border-gray-100 px-5 py-4">
                    {{ $paymentMethods->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
