<x-pelanggan-layout>
    <div class="bg-[#f5f8fc]">
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-[#436aa6]">
                <a href="{{ route('pelanggan.dashboard') }}" class="hover:text-[#c8102e]">Beranda</a>
                <span class="text-[#9caec6]">/</span>
                <a href="{{ route('pelanggan.products.index') }}" class="hover:text-[#c8102e]">Produk</a>
                <span class="text-[#9caec6]">/</span>
                <span class="text-[#436aa6]">Kategori</span>
                <span class="text-[#9caec6]">/</span>
                <span class="max-w-[260px] truncate text-[#10233d] sm:max-w-none">{{ $category->name }}</span>
            </div>
        </div>
    </div>

    <div class="bg-white">
        <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-12">
            <div class="rounded-md border border-[#d8e2f0] bg-white p-5 shadow-sm sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.18em] text-[#c8102e]">Kategori Produk</p>
                <h1 class="mt-2 text-3xl font-black leading-tight text-[#10233d] sm:text-4xl">{{ $category->name }}</h1>
                <p class="mt-4 max-w-3xl text-sm leading-7 text-[#4d6380]">
                    {{ $category->description ?: 'Deskripsi kategori belum tersedia.' }}
                </p>
                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <p class="text-sm font-bold text-[#6e84a3]">{{ $products->total() }} produk aktif dalam kategori ini</p>
                    <a href="{{ route('pelanggan.products.index', ['category' => $category->id]) }}"
                        class="text-sm font-black uppercase tracking-[.14em] text-[#436aa6] hover:text-[#c8102e]">
                        Lihat di Katalog
                    </a>
                </div>
            </div>
        </section>

        <section class="bg-[#f5f8fc] px-4 py-12 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[.18em] text-[#c8102e]">Katalog Kategori</p>
                        <h2 class="mt-2 text-3xl font-black text-[#10233d]">Produk {{ $category->name }}</h2>
                    </div>
                    <a href="{{ route('pelanggan.products.index') }}"
                        class="text-sm font-black uppercase tracking-[.14em] text-[#436aa6] hover:text-[#c8102e]">
                        Semua Produk
                    </a>
                </div>

                @if ($products->count() > 0)
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($products as $product)
                            @include('pelanggan.products.partials.card', ['product' => $product])
                        @endforeach
                    </div>

                    @if ($products->hasPages())
                        <div class="mt-10 flex justify-center">
                            {{ $products->links() }}
                        </div>
                    @endif
                @else
                    <div class="rounded-md border border-[#d8e2f0] bg-white p-8 text-center shadow-sm">
                        <p class="text-lg font-black text-[#10233d]">Belum ada produk aktif untuk kategori ini.</p>
                        <p class="mt-2 text-sm text-[#6e84a3]">Silakan lihat katalog produk lainnya.</p>
                    </div>
                @endif
            </div>
        </section>
    </div>
</x-pelanggan-layout>
