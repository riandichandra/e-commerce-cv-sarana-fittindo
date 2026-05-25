<x-marketing-layout>
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-1 text-xs">
            <p class="tracking-wider">{{ $pagePath[0] }}</p>
            <p>></p>
            <p class="font-bold tracking-wider text-primary">{{ $pagePath[1] }}</p>
        </div>

        <div class="mb-7 flex w-full items-center justify-between">
            <div>
                <h1 class="text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
                <p class="mt-2 text-sm font-medium text-gray-600">Marketing hanya dapat melihat data user dengan role pelanggan.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[.14em] text-gray-500">Total</p>
                    <p class="mt-1 text-2xl font-black text-texthighlight">{{ $totalCustomers }}</p>
                </div>
                <div class="bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[.14em] text-gray-500">Active</p>
                    <p class="mt-1 text-2xl font-black text-primary">{{ $activeCustomers }}</p>
                </div>
            </div>
        </div>

        <div class="w-full bg-[#FFF1F3] p-5">
            <form method="GET" action="{{ route('marketing.users.index') }}" class="mb-5 grid grid-cols-1 gap-3 md:grid-cols-[1fr_180px_auto]">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search name, email, or phone"
                    class="border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                <select name="status" class="border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                    <option value="">All Status</option>
                    <option value="active" @selected($status === 'active')>Active</option>
                    <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                </select>
                <button type="submit" class="inline-flex items-center justify-center gap-2 bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary-dark">
                    <iconify-icon icon="mdi:magnify" class="fs-6"></iconify-icon>
                    FILTER
                </button>
            </form>

            <h2 class="font-semibold tracking-wider text-texthighlight">CUSTOMER LISTS</h2>
            <div class="overflow-x-auto">
                <table class="mt-3 w-full">
                    <thead>
                        <tr class="border-b border-gray-300 text-left text-sm font-medium text-gray-600">
                            <th class="px-3 py-3">#</th>
                            <th class="px-3 py-3">Name</th>
                            <th class="px-3 py-3">Email</th>
                            <th class="px-3 py-3">Phone</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3">Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customers as $customer)
                            <tr class="border-b border-gray-200 text-sm">
                                <td class="px-3 py-3">{{ $customers->firstItem() + $loop->index }}</td>
                                <td class="px-3 py-3 font-medium text-texthighlight">{{ $customer->name }}</td>
                                <td class="px-3 py-3">{{ $customer->email }}</td>
                                <td class="px-3 py-3">{{ $customer->phone ?? '-' }}</td>
                                <td class="px-3 py-3">
                                    <span class="px-2 py-1 text-xs {{ $customer->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' }}">
                                        {{ $customer->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-3 py-3">{{ $customer->created_at->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-6 text-center text-sm text-gray-500">Tidak ada pelanggan yang cocok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $customers->links() }}
            </div>
        </div>
    </div>
</x-marketing-layout>
