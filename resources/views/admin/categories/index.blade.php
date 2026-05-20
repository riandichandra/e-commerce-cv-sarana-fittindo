<x-admin-layout>
    <div class="flex flex-col gap-2">
        <div class="text-xs flex items-center gap-1">
            <p class="tracking-wider">ADMIN</p>
            <p>></p>
            <p class="tracking-wider">PRODUCTS</p>
            <p>></p>
            <p class="font-bold text-primary tracking-wider">CATEGORIES</p>
        </div>

        <div class="w-full flex items-center justify-between mb-7">
            <h1 class="text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
            <div class="flex items-center gap-3">
                <x-button bgColor="primary" textColor="white" icon="mdi:arrow-left" size="auto"
                    href="{{ route('admin.products.index') }}">
                    BACK
                </x-button>
                <x-button bgColor="primary" textColor="white" icon="mdi:plus" size="auto"
                    href="{{ route('admin.categories.create') }}">
                    ADD CATEGORY
                </x-button>
            </div>
        </div>

        <div class="bg-[#FFF1F3] p-5 w-full">
            <h2 class="font-semibold tracking-wider text-texthighlight">CATEGORY LISTS</h2>
            <div class="overflow-x-auto">
                <table class="mt-3 w-full">
                    <thead>
                        <tr class="text-left text-sm text-gray-600 font-medium border-b border-gray-300">
                            <th class="py-3 px-3">#</th>
                            <th class="py-3 px-3">Name</th>
                            <th class="py-3 px-3">Jumlah Produk</th>
                            <th class="py-3 px-3">Status</th>
                            <th class="py-3 px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr class="border-b border-gray-200 text-sm">
                                <td class="py-3 px-3">{{ $categories->firstItem() + $loop->index }}</td>
                                <td class="py-3 px-3 font-medium text-texthighlight">{{ $category->name }}</td>
                                <td class="py-3 px-3">{{ $category->products_count }}</td>
                                <td class="py-3 px-3">
                                    <span class="px-2 py-1 text-xs {{ $category->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' }}">
                                        {{ $category->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-2">
                                        <a class="inline-flex items-center gap-1.5 bg-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700 transition"
                                            href="{{ route('admin.categories.edit', $category) }}">
                                            <iconify-icon icon="mdi:pencil" class="fs-6"></iconify-icon>
                                            EDIT
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 px-3 text-center text-sm text-gray-500">Belum ada kategori.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $categories->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>
