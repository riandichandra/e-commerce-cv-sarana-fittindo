@php
    $formatPrice = fn($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');

    $productImage = function ($product) {
        if (! $product) {
            return null;
        }

        $image = $product->images->firstWhere('is_primary', true) ?? $product->images->first();

        return $image?->image_path ? asset('storage/' . $image->image_path) : null;
    };

    $heroFallbackProduct = $latestProducts->first();
    $heroFallbackImage = $productImage($heroFallbackProduct);

    $heroSlides = $heroPromotions
        ->map(
            fn($promotion) => [
                'eyebrow' => $promotion->type === 'percent' ? 'Promo diskon' : 'Potongan harga',
                'title' => $promotion->name,
                'description' =>
                    $promotion->description ?:
                    'Nikmati penawaran khusus untuk kebutuhan material interior dan proyek Anda.',
                'image' => asset('storage/' . $promotion->banner_image),
                'url' => $promotion->banner_url ?: route('pelanggan.products.index'),
                'period' => $promotion->start_date->format('d M Y') . ' - ' . $promotion->end_date->format('d M Y'),
            ],
        )
        ->values();

    if ($heroSlides->isEmpty()) {
        $heroSlides = collect([
            [
                'eyebrow' => Auth::check() ? 'Halo, ' . Auth::user()->name : 'Selamat datang',
                'title' => 'Temukan Material Interior untuk Kebutuhan Proyek Anda',
                'description' =>
                    'Pilih HPL, plywood, MDF, dan material pendukung dari katalog CV Sarana Fittindo Palembang.',
                'image' => $heroFallbackImage,
                'url' => route('pelanggan.products.index'),
                'period' => $heroFallbackProduct ? 'Produk terbaru tersedia' : 'Katalog material pilihan',
            ],
        ]);
    }

    $displayFeaturedProducts = $featuredProducts->isNotEmpty()
        ? $featuredProducts
        : $latestProducts->take(4);

    $quickLinks = [
        [
            'label' => 'Semua Produk',
            'description' => 'Lihat katalog',
            'icon' => 'mdi:view-grid-outline',
            'url' => route('pelanggan.products.index'),
        ],
        [
            'label' => 'Keranjang',
            'description' => Auth::check() ? $cartItemsCount . ' item' : 'Masuk untuk cek',
            'icon' => 'mdi:cart-outline',
            'url' => Auth::check() ? route('pelanggan.cart.index') : route('login'),
        ],
        [
            'label' => 'Pesanan Saya',
            'description' => 'Pantau status',
            'icon' => 'mdi:receipt-text-outline',
            'url' => Auth::check() ? route('pelanggan.orders.index') : route('login'),
        ],
        [
            'label' => 'Riwayat Pesanan',
            'description' => 'Belanja ulang',
            'icon' => 'mdi:history',
            'url' => Auth::check() ? route('pelanggan.orders.history') : route('login'),
        ],
    ];
@endphp

<x-pelanggan-layout>
    <div class="bg-[#f6f8fb]">
        <section x-data="{
            active: 0,
            slides: @js($heroSlides),
            timer: null,
            next() {
                this.active = (this.active + 1) % this.slides.length;
            },
            prev() {
                this.active = (this.active - 1 + this.slides.length) % this.slides.length;
            },
            go(index) {
                this.active = index;
            },
            start() {
                if (this.slides.length > 1) {
                    this.timer = setInterval(() => this.next(), 6000);
                }
            },
            reset() {
                if (this.timer) {
                    clearInterval(this.timer);
                }
                this.start();
            },
        }" x-init="start()" class="relative min-h-[480px] overflow-hidden bg-[#0c1d38] sm:min-h-[560px] lg:min-h-[620px]">
            <div class="absolute inset-0">
                <template x-for="(slide, index) in slides" :key="slide.image + index">
                    <div x-show="active === index" x-transition.opacity.duration.700ms
                        class="absolute inset-0 h-full w-full bg-cover bg-center"
                        :style="slide.image
                            ? `background-image: linear-gradient(90deg, rgba(4,17,45,.94) 0%, rgba(7,25,60,.76) 38%, rgba(9,22,45,.18) 72%), url('${slide.image}');`
                            : `background-image: linear-gradient(135deg, #0c1d38 0%, #163f8f 58%, #c8102e 100%);`">
                    </div>
                </template>
                <div class="absolute inset-0"
                    style="background-image: linear-gradient(180deg, rgba(12,29,56,.08) 0%, rgba(12,29,56,.88) 100%);">
                </div>
            </div>

            <div class="relative mx-auto flex min-h-[480px] max-w-7xl items-center px-4 py-16 sm:min-h-[560px] sm:px-6 lg:min-h-[620px] lg:px-8">
                <div class="max-w-2xl pb-10 pt-4 text-white">
                    <p class="text-sm font-extrabold uppercase tracking-[.18em] text-[#ffd5dc]"
                        x-text="slides[active].eyebrow"></p>
                    <h1 class="mt-5 text-4xl font-black leading-tight sm:text-5xl lg:text-6xl">
                        <span x-text="slides[active].title"></span>
                    </h1>
                    <p class="mt-5 max-w-xl text-base leading-8 text-slate-200 sm:text-lg">
                        <span x-text="slides[active].description"></span>
                    </p>
                    <p class="mt-5 text-sm font-semibold text-[#ffd5dc]" x-text="slides[active].period"></p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a :href="slides[active].url"
                            class="inline-flex h-12 items-center justify-center rounded-md bg-primary px-6 text-sm font-bold text-white shadow-sm transition hover:bg-primary-dark">
                            Belanja Sekarang
                            <iconify-icon icon="mdi:arrow-right" class="ml-2 fs-6"></iconify-icon>
                        </a>
                        <a href="{{ Auth::check() ? route('pelanggan.orders.index') : route('login') }}"
                            class="inline-flex h-12 items-center justify-center rounded-md border border-white/35 bg-white/10 px-6 text-sm font-bold text-white backdrop-blur transition hover:bg-white hover:text-[#10233d]">
                            Lihat Pesanan
                        </a>
                    </div>
                </div>
            </div>

            <div class="absolute bottom-8 right-4 hidden gap-2 sm:right-6 md:flex lg:right-8" x-show="slides.length > 1">
                <button type="button" x-on:click="prev(); reset()"
                    class="flex h-11 w-11 items-center justify-center rounded-md bg-white/15 text-white backdrop-blur transition hover:bg-white/25"
                    aria-label="Promo sebelumnya">
                    <iconify-icon icon="mdi:chevron-left" class="fs-5"></iconify-icon>
                </button>
                <button type="button" x-on:click="next(); reset()"
                    class="flex h-11 w-11 items-center justify-center rounded-md bg-white/15 text-white backdrop-blur transition hover:bg-white/25"
                    aria-label="Promo berikutnya">
                    <iconify-icon icon="mdi:chevron-right" class="fs-5"></iconify-icon>
                </button>
            </div>

            <div class="absolute bottom-8 left-4 flex gap-2 sm:left-6 lg:left-[calc((100vw-80rem)/2+2rem)]" x-show="slides.length > 1">
                <template x-for="(slide, index) in slides" :key="index">
                    <button type="button" x-on:click="go(index); reset()" class="h-2.5 rounded-full transition-all"
                        :class="active === index ? 'w-9 bg-white' : 'w-2.5 bg-white/45 hover:bg-white/75'"
                        aria-label="Pilih slide promo"></button>
                </template>
            </div>
        </section>

        <section class="border-y border-slate-200 bg-white">
            <div class="mx-auto grid max-w-7xl grid-cols-2 gap-px bg-slate-200 px-4 sm:px-6 lg:grid-cols-4 lg:px-8">
                @foreach ($quickLinks as $link)
                    <a href="{{ $link['url'] }}"
                        class="group flex items-center gap-3 bg-white px-3 py-4 transition hover:bg-[#fff7f8] sm:px-5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-[#fff1f3] text-primary group-hover:bg-primary group-hover:text-white">
                            <iconify-icon icon="{{ $link['icon'] }}" class="fs-5"></iconify-icon>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-bold text-[#10233d]">{{ $link['label'] }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $link['description'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="px-4 py-10 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-primary">Kategori</p>
                        <h2 class="mt-1 text-2xl font-extrabold text-[#10233d]">Belanja Berdasarkan Kategori</h2>
                    </div>
                    <a href="{{ route('pelanggan.products.index') }}"
                        class="text-sm font-bold text-primary hover:text-primary-dark">Lihat semua produk</a>
                </div>

                @if ($categories->isNotEmpty())
                    <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
                        @foreach ($categories as $category)
                            <a href="{{ route('pelanggan.products.index', ['category' => $category->slug]) }}"
                                class="group rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md">
                                <div class="flex h-11 w-11 items-center justify-center rounded-md bg-[#f1f5f9] text-[#10233d] group-hover:bg-[#fff1f3] group-hover:text-primary">
                                    <iconify-icon icon="mdi:shape-outline" class="fs-5"></iconify-icon>
                                </div>
                                <h3 class="mt-4 line-clamp-2 min-h-[40px] text-sm font-bold leading-5 text-[#10233d]">
                                    {{ $category->name }}
                                </h3>
                                <p class="mt-2 text-xs text-slate-500">{{ $category->products_count }} produk aktif</p>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="mt-6 rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center">
                        <p class="font-semibold text-[#10233d]">Kategori belum tersedia.</p>
                        <a href="{{ route('pelanggan.products.index') }}"
                            class="mt-4 inline-flex h-10 items-center justify-center rounded-md bg-primary px-4 text-sm font-bold text-white hover:bg-primary-dark">
                            Buka Katalog
                        </a>
                    </div>
                @endif
            </div>
        </section>

        <section class="px-4 pb-10 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-primary">Pilihan toko</p>
                            <h2 class="mt-1 text-2xl font-extrabold text-[#10233d]">Produk Unggulan</h2>
                        </div>
                        <a href="{{ route('pelanggan.products.index') }}"
                            class="text-sm font-bold text-primary hover:text-primary-dark">Lihat katalog</a>
                    </div>

                    @if ($displayFeaturedProducts->isNotEmpty())
                        <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($displayFeaturedProducts as $product)
                                @php
                                    $image = $productImage($product);
                                    $isAvailable = $product->stock > 0;
                                @endphp
                                <article class="group flex h-full flex-col overflow-hidden rounded-lg border border-slate-200 bg-white transition hover:-translate-y-0.5 hover:shadow-md">
                                    <a href="{{ route('pelanggan.products.show', $product) }}"
                                        class="relative block aspect-[4/3] overflow-hidden bg-slate-100">
                                        @if ($image)
                                            <img src="{{ $image }}" alt="{{ $product->name }}"
                                                class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                        @else
                                            <div class="flex h-full w-full items-center justify-center text-slate-400">
                                                <iconify-icon icon="mdi:image-off-outline" class="fs-3"></iconify-icon>
                                            </div>
                                        @endif
                                        <span class="absolute left-3 top-3 rounded bg-white/95 px-2.5 py-1 text-xs font-bold text-[#10233d] shadow-sm">
                                            {{ $product->category?->name ?? 'Produk' }}
                                        </span>
                                    </a>
                                    <div class="flex flex-1 flex-col p-4">
                                        <h3 class="line-clamp-2 min-h-[44px] text-sm font-bold leading-5 text-[#10233d]">
                                            <a href="{{ route('pelanggan.products.show', $product) }}" class="hover:text-primary">
                                                {{ $product->name }}
                                            </a>
                                        </h3>
                                        <p class="mt-2 text-xs text-slate-500">{{ $product->brand?->name ?? 'CV Sarana Fittindo' }}</p>
                                        <div class="mt-4 flex items-center justify-between gap-3">
                                            <p class="font-extrabold text-primary">{{ $formatPrice($product->price) }}</p>
                                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $isAvailable ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                                {{ $isAvailable ? 'Tersedia' : 'Habis' }}
                                            </span>
                                        </div>
                                        <a href="{{ route('pelanggan.products.show', $product) }}"
                                            class="mt-4 inline-flex h-10 items-center justify-center rounded-md border border-slate-200 text-sm font-bold text-[#10233d] transition hover:border-primary hover:text-primary">
                                            Detail Produk
                                        </a>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-6 rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <p class="font-semibold text-[#10233d]">Produk belum tersedia.</p>
                            <p class="mt-2 text-sm text-slate-500">Silakan cek kembali katalog produk nanti.</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="px-4 pb-10 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-primary">Baru masuk</p>
                        <h2 class="mt-1 text-2xl font-extrabold text-[#10233d]">Produk Terbaru</h2>
                    </div>
                    <a href="{{ route('pelanggan.products.index') }}"
                        class="text-sm font-bold text-primary hover:text-primary-dark">Lihat semua</a>
                </div>

                @if ($latestProducts->isNotEmpty())
                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($latestProducts as $product)
                            @php
                                $image = $productImage($product);
                                $isAvailable = $product->stock > 0;
                            @endphp
                            <article class="group flex h-full flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                <a href="{{ route('pelanggan.products.show', $product) }}"
                                    class="relative block aspect-[4/3] overflow-hidden bg-slate-100">
                                    @if ($image)
                                        <img src="{{ $image }}" alt="{{ $product->name }}"
                                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center text-slate-400">
                                            <iconify-icon icon="mdi:image-off-outline" class="fs-3"></iconify-icon>
                                        </div>
                                    @endif
                                    <span class="absolute bottom-3 left-3 rounded bg-white/95 px-2.5 py-1 text-xs font-bold text-[#10233d] shadow-sm">
                                        {{ $product->category?->name ?? 'Produk' }}
                                    </span>
                                </a>
                                <div class="flex flex-1 flex-col p-4">
                                    <h3 class="line-clamp-2 min-h-[44px] text-sm font-bold leading-5 text-[#10233d]">
                                        <a href="{{ route('pelanggan.products.show', $product) }}" class="hover:text-primary">
                                            {{ $product->name }}
                                        </a>
                                    </h3>
                                    <div class="mt-4 flex items-center justify-between gap-3">
                                        <p class="font-extrabold text-primary">{{ $formatPrice($product->price) }}</p>
                                        <span class="text-xs font-semibold {{ $isAvailable ? 'text-emerald-700' : 'text-slate-500' }}">
                                            {{ $isAvailable ? 'Tersedia' : 'Habis' }}
                                        </span>
                                    </div>
                                    <a href="{{ route('pelanggan.products.show', $product) }}"
                                        class="mt-4 inline-flex h-10 items-center justify-center rounded-md bg-[#fff1f3] text-sm font-bold text-primary transition hover:bg-primary hover:text-white">
                                        Lihat Detail
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center">
                        <p class="font-semibold text-[#10233d]">Produk belum tersedia.</p>
                        <a href="{{ route('pelanggan.products.index') }}"
                            class="mt-4 inline-flex h-10 items-center justify-center rounded-md bg-primary px-4 text-sm font-bold text-white hover:bg-primary-dark">
                            Buka Katalog
                        </a>
                    </div>
                @endif
            </div>
        </section>

        <section class="px-4 pb-12 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl overflow-hidden rounded-lg bg-[#10233d] p-6 text-white shadow-sm sm:p-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-sm font-semibold text-[#ffd5dc]">Butuh bantuan?</p>
                        <h2 class="mt-2 text-2xl font-extrabold">Pilih material dengan lebih percaya diri.</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-200">
                            Jelajahi katalog berdasarkan kategori, bandingkan detail produk, lalu lanjutkan belanja dari halaman detail.
                        </p>
                    </div>
                    <a href="{{ route('pelanggan.products.index') }}"
                        class="inline-flex h-11 shrink-0 items-center justify-center rounded-md bg-white px-5 text-sm font-bold text-[#10233d] transition hover:bg-[#fff1f3] hover:text-primary">
                        Lihat Katalog
                    </a>
                </div>
            </div>
        </section>
    </div>
</x-pelanggan-layout>
