<x-admin-layout>
    <div class="flex flex-col gap-6">
        <div class="flex items-center gap-1 text-xs">
            <p class="tracking-wider">ADMIN</p>
            <p>></p>
            <p class="tracking-wider">PRODUK</p>
            <p>></p>
            <p class="font-bold tracking-wider text-primary">KATEGORI</p>
        </div>

        <div class="flex w-full flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <p class="text-sm font-semibold text-primary">Manajemen katalog</p>
                <h1 class="mt-1 text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-button bgColor="primary" textColor="white" icon="mdi:arrow-left" size="auto"
                    href="{{ route('admin.products.index') }}">
                    KEMBALI
                </x-button>
                <x-button bgColor="primary" textColor="white" icon="mdi:plus" size="auto"
                    href="{{ route('admin.categories.create') }}">
                    TAMBAH KATEGORI
                </x-button>
            </div>
        </div>

        <div class="w-full overflow-hidden border border-[#f2c8d0] bg-white shadow-sm">
            <div
                class="flex flex-col gap-3 border-b border-[#f2c8d0] bg-[#fff7f8] p-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-black tracking-wide text-texthighlight">Daftar Kategori</h2>
                    <p class="mt-1 text-sm text-gray-600">Kelola pengelompokan produk yang tampil di katalog.</p>
                </div>
                <p class="text-sm font-semibold text-gray-600">
                    Menampilkan {{ $categories->count() }} dari {{ number_format($categories->total(), 0, ',', '.') }}
                    kategori
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px]">
                    <thead>
                        <tr
                            class="border-b border-gray-200 bg-white text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                            <th class="w-16 px-5 py-4">No.</th>
                            <th class="px-5 py-4">Kategori</th>
                            <th class="px-5 py-4">Jumlah Produk</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($categories as $category)
                            <tr class="text-sm transition hover:bg-[#fff7f8]">
                                <td class="px-5 py-4 align-top font-semibold text-gray-500">
                                    {{ $categories->firstItem() + $loop->index }}</td>
                                <td class="px-5 py-4">
                                    <p class="font-bold text-texthighlight">{{ $category->name }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ $category->slug }}</p>
                                    @if ($category->description)
                                        <p class="mt-1 max-w-xl text-xs text-gray-500">
                                            {{ \Illuminate\Support\Str::limit($category->description, 90) }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-700">
                                        <iconify-icon icon="mdi:package-variant-closed"></iconify-icon>
                                        {{ number_format($category->products_count, 0, ',', '.') }} produk
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="px-2.5 py-1 text-xs font-bold {{ $category->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a class="inline-flex h-9 items-center justify-center gap-1.5 bg-primary px-3 text-xs font-bold text-white transition hover:bg-red-700"
                                        href="{{ route('admin.categories.edit', $category) }}">
                                        <iconify-icon icon="mdi:pencil" class="fs-6"></iconify-icon>
                                        EDIT
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-14 text-center">
                                    <div class="mx-auto flex max-w-md flex-col items-center">
                                        <div
                                            class="flex h-14 w-14 items-center justify-center bg-[#fff1f3] text-primary">
                                            <iconify-icon icon="mdi:tag-outline" class="fs-3"></iconify-icon>
                                        </div>
                                        <p class="mt-4 text-base font-bold text-texthighlight">Belum ada kategori.</p>
                                        <p class="mt-2 text-sm text-gray-500">Tambahkan kategori agar produk lebih mudah
                                            dikelompokkan.</p>
                                        <a href="{{ route('admin.categories.create') }}"
                                            class="mt-5 inline-flex h-10 items-center justify-center gap-2 bg-primary px-4 text-sm font-bold text-white transition hover:bg-red-700">
                                            <iconify-icon icon="mdi:plus" class="fs-6"></iconify-icon>
                                            Tambah Kategori
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($categories->hasPages())
                <div class="border-t border-gray-100 px-5 py-4">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
