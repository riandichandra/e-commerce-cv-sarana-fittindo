<x-admin-layout>
    <div class="flex flex-col gap-6">
        <div class="flex items-center gap-1 text-xs">
            <p class="tracking-wider">ADMIN</p>
            <p>></p>
            <p class="font-bold tracking-wider text-primary">PRODUK</p>
        </div>

        <div class="flex w-full flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <p class="text-sm font-semibold text-primary">Manajemen katalog</p>
                <h1 class="mt-1 text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
            </div>
            <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                <x-button bgColor="primary" textColor="white" icon="mdi:plus" size="auto"
                    href="{{ route('admin.products.create') }}">
                    TAMBAH PRODUK
                </x-button>

                <x-button bgColor="primary" textColor="white" icon="mdi:tag" size="auto"
                    href="{{ route('admin.categories.index') }}">
                    KATEGORI
                </x-button>

                <x-button bgColor="primary" textColor="white" icon="mdi:factory" size="auto"
                    href="{{ route('admin.brands.index') }}">
                    MEREK
                </x-button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="border border-[#f2c8d0] bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total Produk</p>
                <p class="mt-2 text-2xl font-black text-texthighlight">{{ number_format($totalProducts, 0, ',', '.') }}
                </p>
            </div>
            <div class="border border-[#d9eadf] bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Aktif di Katalog</p>
                <p class="mt-2 text-2xl font-black text-emerald-700">{{ number_format($activeProducts, 0, ',', '.') }}
                </p>
            </div>
            <div class="border border-[#dbeafe] bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tersedia</p>
                <p class="mt-2 text-2xl font-black text-blue-700">{{ number_format($availableProducts, 0, ',', '.') }}
                </p>
            </div>
            <div class="border border-[#fee2e2] bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Stok Kosong</p>
                <p class="mt-2 text-2xl font-black text-red-700">{{ number_format($outOfStockProducts, 0, ',', '.') }}
                </p>
            </div>
        </div>

        <div class="w-full overflow-hidden border border-[#f2c8d0] bg-white shadow-sm">
            <div
                class="flex flex-col gap-3 border-b border-[#f2c8d0] bg-[#fff7f8] p-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-black tracking-wide text-texthighlight">Daftar Produk</h2>
                    <p class="mt-1 text-sm text-gray-600">Kelola produk, stok, harga, dan status tampil di katalog.</p>
                </div>
                <p class="text-sm font-semibold text-gray-600">
                    Menampilkan {{ $products->count() }} dari {{ number_format($products->total(), 0, ',', '.') }}
                    produk
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px]">
                    <thead>
                        <tr
                            class="border-b border-gray-200 bg-white text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                            <th class="w-16 px-5 py-4">No.</th>
                            <th class="px-5 py-4">Produk</th>
                            <th class="px-5 py-4">Kategori / Merek</th>
                            <th class="px-5 py-4">Harga</th>
                            <th class="px-5 py-4">Stok</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($products as $product)
                            @php
                                $isAvailable = $product->status === \App\Models\Product::STATUS_AVAILABLE;
                            @endphp
                            <tr class="text-sm transition hover:bg-[#fff7f8]">
                                <td class="px-5 py-4 align-top font-semibold text-gray-500">
                                    {{ $products->firstItem() + $loop->index }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-bold leading-5 text-texthighlight">{{ $product->name }}</p>
                                            @if ($product->is_featured)
                                                <span
                                                    class="bg-[#fff1f3] px-2 py-0.5 text-[11px] font-bold text-primary">
                                                    Unggulan
                                                </span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">
                                            {{ $product->slug }}
                                        </p>
                                        @if ($product->thickness || $product->dimensions)
                                            <p class="mt-1 text-xs text-gray-500">
                                                {{ collect([$product->thickness, $product->dimensions])->filter()->join(' | ') }}
                                            </p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-gray-800">{{ $product->category?->name ?? '-' }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ $product->brand?->name ?? 'Tanpa merek' }}
                                    </p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-black text-texthighlight">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ number_format($product->weight, 0, ',', '.') }} gram</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-700">
                                        <iconify-icon icon="mdi:archive-outline"></iconify-icon>
                                        {{ number_format($product->stock, 0, ',', '.') }} stok
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            class="px-2.5 py-1 text-xs font-bold {{ $isAvailable ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                            {{ $isAvailable ? 'Tersedia' : 'Tidak tersedia' }}
                                        </span>
                                        <span
                                            class="px-2.5 py-1 text-xs font-bold {{ $product->is_active ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a class="inline-flex h-9 items-center justify-center gap-1.5 bg-primary px-3 text-xs font-bold text-white transition hover:bg-red-700"
                                        href="{{ route('admin.products.edit', $product) }}">
                                        <iconify-icon icon="mdi:pencil" class="fs-6"></iconify-icon>
                                        EDIT
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-14 text-center">
                                    <div class="mx-auto flex max-w-md flex-col items-center">
                                        <div
                                            class="flex h-14 w-14 items-center justify-center bg-[#fff1f3] text-primary">
                                            <iconify-icon icon="mdi:package-variant-closed"
                                                class="fs-3"></iconify-icon>
                                        </div>
                                        <p class="mt-4 text-base font-bold text-texthighlight">Belum ada produk.</p>
                                        <p class="mt-2 text-sm text-gray-500">Tambahkan produk pertama untuk mulai
                                            mengisi katalog.</p>
                                        <a href="{{ route('admin.products.create') }}"
                                            class="mt-5 inline-flex h-10 items-center justify-center gap-2 bg-primary px-4 text-sm font-bold text-white transition hover:bg-red-700">
                                            <iconify-icon icon="mdi:plus" class="fs-6"></iconify-icon>
                                            Tambah Produk
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($products->hasPages())
                <div class="border-t border-gray-100 px-5 py-4">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
