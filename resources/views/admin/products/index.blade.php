<x-admin-layout>
    <div class="flex flex-col gap-2">
        <div class="text-xs flex items-center gap-1">
            <p class="tracking-wider">ADMIN</p>
            <p>></p>
            <p class="font-bold text-primary tracking-wider">PRODUCTS</p>
        </div>

        <div class="w-full flex items-center justify-between mb-7">
            <h1 class="text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
            <div class="flex items-center gap-3 justify-end">
                <x-button bgColor="primary" textColor="white" icon="mdi:plus" size="auto"
                    href="{{ route('admin.products.create') }}">
                    ADD PRODUCT
                </x-button>

                <x-button bgColor="primary" textColor="white" icon="mdi:tag" size="auto"
                    href="{{ route('admin.categories.index') }}">
                    CATEGORIES
                </x-button>

                <x-button bgColor="primary" textColor="white" icon="mdi:factory" size="auto"
                    href="{{ route('admin.brands.index') }}">
                    BRANDS
                </x-button>
            </div>
        </div>

        <div class="bg-[#FFF1F3] p-5 w-full">
            <h2 class="font-semibold tracking-wider text-texthighlight">PRODUCT LISTS</h2>
            <div class="overflow-x-auto">
                <table class="mt-3 w-full">
                    <thead>
                        <tr class="text-left text-sm text-gray-600 font-medium border-b border-gray-300">
                            <th class="py-3 px-3">#</th>
                            <th class="py-3 px-3">Nama</th>
                            <th class="py-3 px-3">Kategori</th>
                            <th class="py-3 px-3">Merek</th>
                            <th class="py-3 px-3">Harga</th>
                            <th class="py-3 px-3">Stok</th>
                            <th class="py-3 px-3">Status</th>
                            <th class="py-3 px-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr class="border-b border-gray-200 text-sm">
                                <td class="py-3 px-3">{{ $products->firstItem() + $loop->index }}</td>
                                <td class="py-3 px-3 font-medium text-texthighlight">{{ $product->name }}</td>
                                <td class="py-3 px-3">{{ $product->category?->name ?? '-' }}</td>
                                <td class="py-3 px-3">{{ $product->brand?->name ?? '-' }}</td>
                                <td class="py-3 px-3">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                                <td class="py-3 px-3">{{ $product->stock }}</td>
                                <td class="py-3 px-3">
                                    <span
                                        class="px-2 py-1 text-xs {{ $product->status === 'tersedia' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ ucfirst($product->status) }}
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-2">
                                        <a class="inline-flex items-center gap-1.5 bg-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700 transition"
                                            href="{{ route('admin.products.edit', $product) }}">
                                            <iconify-icon icon="mdi:pencil" class="fs-6"></iconify-icon>
                                            EDIT
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 px-3 text-center text-sm text-gray-500">Belum ada produk.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $products->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>
