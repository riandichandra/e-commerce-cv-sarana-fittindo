<x-admin-layout>
    <div class="flex flex-col gap-2">
        <div class="text-xs flex items-center gap-1">
            <p class="tracking-wider">ADMIN</p>
            <p>></p>
            <p class="tracking-wider">PAYMENTS</p>
            <p>></p>
            <p class="font-bold text-primary tracking-wider">PAYMENT METHODS</p>
        </div>

        <div class="w-full flex items-center justify-between mb-7">
            <h1 class="text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
            <x-button bgColor="primary" textColor="white" icon="mdi:plus" size="auto"
                href="{{ route('admin.payment-methods.create') }}">
                ADD BANK ACCOUNT
            </x-button>
        </div>

        <div class="bg-[#FFF1F3] p-5 w-full">
            <h2 class="font-semibold tracking-wider text-texthighlight">BANK ACCOUNT LISTS</h2>
            <div class="overflow-x-auto">
                <table class="mt-3 w-full">
                    <thead>
                        <tr class="text-left text-sm text-gray-600 font-medium border-b border-gray-300">
                            <th class="py-3 px-3">#</th>
                            <th class="py-3 px-3">Metode</th>
                            <th class="py-3 px-3">Bank</th>
                            <th class="py-3 px-3">Nomor Rekening</th>
                            <th class="py-3 px-3">Nama Rekening</th>
                            <th class="py-3 px-3">Pesanan</th>
                            <th class="py-3 px-3">Status</th>
                            <th class="py-3 px-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($paymentMethods as $paymentMethod)
                            <tr class="border-b border-gray-200 text-sm">
                                <td class="py-3 px-3">{{ $paymentMethods->firstItem() + $loop->index }}</td>
                                <td class="py-3 px-3">
                                    <p class="font-medium text-texthighlight">{{ $paymentMethod->name }}</p>
                                </td>
                                <td class="py-3 px-3">{{ $paymentMethod->bank_name }}</td>
                                <td class="py-3 px-3">{{ $paymentMethod->account_number }}</td>
                                <td class="py-3 px-3">{{ $paymentMethod->account_name }}</td>
                                <td class="py-3 px-3">{{ $paymentMethod->sort_order }}</td>
                                <td class="py-3 px-3">
                                    <span class="px-2 py-1 text-xs {{ $paymentMethod->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' }}">
                                        {{ $paymentMethod->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    <a class="inline-flex items-center gap-1.5 bg-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700 transition"
                                        href="{{ route('admin.payment-methods.edit', $paymentMethod) }}">
                                        <iconify-icon icon="mdi:pencil" class="fs-6"></iconify-icon>
                                        EDIT
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 px-3 text-center text-sm text-gray-500">Belum ada rekening bank.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $paymentMethods->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>
