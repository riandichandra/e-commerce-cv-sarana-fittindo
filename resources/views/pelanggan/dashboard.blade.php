@php
    $localImages = [
        asset('storage/products/dhpkAn0vG4mLZgmK34IkMPhkLMmIDLk5zAIMcp4M.jpg'),
        asset('storage/products/Oh8Tc0FrTkuw8Z5PotedjCIQa9NdCaxkBGZJ6MSm.jpg'),
        asset('storage/products/pS6IaqoHnjYGwS1CQhRTQPFSPb7bX6KJIrm6zxne.jpg'),
        asset('storage/products/WkUfwN4Sgn9TMmmCbIzHu72wVWyrXAC3QqrU8I2w.jpg'),
    ];

    $categoryCards = [
        [
            'title' => 'HPL Solutions',
            'subtitle' => 'INDUSTRIAL GRADE FINISH',
            'image' => $localImages[0],
            'category' => 'hpl',
        ],
        [
            'title' => 'Premium MDF',
            'subtitle' => 'PRECISION ENGINEERED',
            'image' => $localImages[1],
            'category' => 'mdf',
        ],
        [
            'title' => 'Marine Plywood',
            'subtitle' => 'STRUCTURAL INTEGRITY',
            'image' => $localImages[2],
            'category' => 'plywood',
        ],
    ];

    $arrivalFallbacks = [
        [
            'title' => 'Midnight Slate HPL',
            'category' => 'ARCHITECTURAL CHOICE',
            'price' => 450000,
            'image' => $localImages[3],
            'description' =>
                'Introducing the deep texture of slate fused with the durability of premium HPL. Perfect for high-traffic commercial spaces.',
        ],
        [
            'title' => 'Oak Grain Plywood',
            'category' => 'THICKNESS: 18MM',
            'price' => 320000,
            'image' => $localImages[0],
            'description' => '',
        ],
        [
            'title' => 'Ultra-Bond Adhesive',
            'category' => 'INDUSTRIAL CAPACITY',
            'price' => 85000,
            'image' => $localImages[1],
            'description' => '',
        ],
    ];

    $productFallbacks = [
        ['title' => 'HPL Wood Grain - Walnut', 'category' => 'HPL', 'price' => 285000, 'image' => $localImages[0]],
        ['title' => 'MDF Board - 15mm Grade A', 'category' => 'MDF', 'price' => 195000, 'image' => $localImages[1]],
        ['title' => 'Multiplex Meranti - 18mm', 'category' => 'PLYWOOD', 'price' => 340000, 'image' => $localImages[2]],
        [
            'title' => 'Contact Cement - High Heat',
            'category' => 'ADHESIVES',
            'price' => 72000,
            'image' => $localImages[3],
        ],
    ];

    $heroSlides = $heroPromotions
        ->map(
            fn($promotion) => [
                'title' => $promotion->name,
                'subtitle' => $promotion->type === 'percent' ? 'PROMO DISKON PERSEN' : 'PROMO POTONGAN HARGA',
                'description' =>
                    $promotion->description ?:
                    'Nikmati penawaran khusus CV Sarana Fittindo selama periode promosi berlangsung.',
                'image' => asset('storage/' . $promotion->banner_image),
                'url' => $promotion->banner_url ?: route('pelanggan.products.index'),
                'period' => $promotion->start_date->format('d M Y') . ' - ' . $promotion->end_date->format('d M Y'),
            ],
        )
        ->values();

    if ($heroSlides->isEmpty()) {
        $heroSlides = collect([
            [
                'title' => 'Architectural Precision.',
                'subtitle' => 'NEW SEASON ARRIVALS',
                'description' =>
                    'Discover our curated collection of industrial-grade HPL and Plywood solutions designed for durability and high-end aesthetics.',
                'image' => $localImages[2],
                'url' => route('pelanggan.products.index'),
                'period' => 'Featured Collection',
            ],
        ]);
    }
@endphp

<x-pelanggan-layout>
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
            this.timer = setInterval(() => this.next(), 5500);
        },
        reset() {
            clearInterval(this.timer);
            this.start();
        },
    }" x-init="start()" class="relative h-[650px] overflow-hidden bg-[#0c1d38]">
        <div class="absolute inset-0">
            <template x-for="(slide, index) in slides" :key="slide.image + index">
                <div x-show="active === index" x-transition.opacity.duration.700ms
                    class="absolute inset-0 h-full w-full scale-105 bg-cover bg-center"
                    :style="`background-image: linear-gradient(90deg, rgba(4,17,45,.92) 0%, rgba(7,25,60,.72) 34%, rgba(9,22,45,.18) 68%), url('${slide.image}');`">
                </div>
            </template>
            <div class="absolute inset-0 opacity-80"
                style="background-image: repeating-linear-gradient(0deg, rgba(255,255,255,.08) 0 2px, transparent 2px 16px), linear-gradient(180deg, transparent 0%, rgba(0,38,108,.82) 100%);">
            </div>
        </div>

        <div class="relative mx-auto flex h-full max-w-[1365px] items-center px-12">
            <div class="max-w-[720px] pt-10">
                <p class="mb-6 text-[12px] font-black uppercase tracking-[.35em] text-white"
                    x-text="slides[active].subtitle"></p>
                <h1 class="text-[58px] font-black uppercase leading-[.96] text-white md:text-[76px]">
                    <span x-text="slides[active].title"></span>
                </h1>
                <p class="mt-5 text-[12px] font-black uppercase tracking-[.18em] text-[#ffe2e7]"
                    x-text="slides[active].period"></p>
                <p class="mt-8 max-w-[540px] text-[16px] leading-8 text-[#c6d6ef]">
                    <span x-text="slides[active].description"></span>
                </p>
                <a :href="slides[active].url"
                    class="mt-9 inline-flex h-12 items-center bg-[#c8102e] px-8 text-[12px] font-black uppercase tracking-[.16em] text-white hover:bg-[#9f0d24]">
                    View Promotion
                    <iconify-icon icon="mdi:arrow-right" class="ml-2 fs-6"></iconify-icon>
                </a>
            </div>
        </div>

        <div class="absolute bottom-10 right-12 flex">
            <button type="button" x-on:click="prev(); reset()"
                class="flex h-11 w-11 items-center justify-center bg-white/15 text-white hover:bg-white/25"
                aria-label="Previous slide">
                <iconify-icon icon="mdi:chevron-left" class="fs-5"></iconify-icon>
            </button>
            <button type="button" x-on:click="next(); reset()"
                class="ml-1 flex h-11 w-11 items-center justify-center bg-white/15 text-white hover:bg-white/25"
                aria-label="Next slide">
                <iconify-icon icon="mdi:chevron-right" class="fs-5"></iconify-icon>
            </button>
        </div>

        <div class="absolute bottom-12 left-12 flex gap-2">
            <template x-for="(slide, index) in slides" :key="index">
                <button type="button" x-on:click="go(index); reset()" class="h-2.5 transition-all"
                    :class="active === index ? 'w-9 bg-white' : 'w-2.5 bg-white/40 hover:bg-white/70'"
                    aria-label="Go to promotion slide"></button>
            </template>
        </div>
    </section>

    <section class="bg-[#f7faff] px-8 py-[100px]">
        <div class="mx-auto grid max-w-[1290px] grid-cols-1 gap-8 md:grid-cols-3">
            @foreach ($categoryCards as $card)
                <a href="{{ route('pelanggan.products.index', ['category' => $card['category']]) }}"
                    class="group relative h-[530px] overflow-hidden bg-[#0b1727]">
                    <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}"
                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#07101d] via-[#07101d]/35 to-transparent"></div>
                    <div class="absolute bottom-0 left-0 right-0 p-8 text-white">
                        <h2 class="text-[27px] font-black leading-none">{{ $card['title'] }}</h2>
                        <p class="mt-3 text-[12px] font-bold uppercase tracking-[.16em] text-[#d9e4f8]">
                            {{ $card['subtitle'] }}</p>
                        <p class="mt-7 text-[11px] font-black uppercase tracking-[.16em]">Explore Category</p>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    <section class="bg-[#fff1f3] px-8 py-[100px]">
        <div class="mx-auto max-w-[1290px]">
            <div class="mb-16 flex items-end justify-between">
                <div>
                    <h2 class="text-[36px] font-black uppercase tracking-[-.03em] text-[#10233d]">Our Latest Arrivals
                    </h2>
                    <div class="mt-3 h-1 w-24 bg-[#c8102e]"></div>
                </div>
                <a href="{{ route('pelanggan.products.index') }}"
                    class="text-[11px] font-black uppercase tracking-[.18em] text-[#c8102e] hover:text-[#9f0d24]">
                    View All Arrivals
                </a>
            </div>

            <div class="grid min-h-[630px] grid-cols-1 gap-8 lg:grid-cols-4">
                @php
                    $mainArrival = $latestProducts->first();
                    $mainImage = $mainArrival?->images->first()?->image_path
                        ? asset('storage/' . $mainArrival->images->first()->image_path)
                        : $arrivalFallbacks[0]['image'];
                @endphp
                <article class="relative overflow-hidden bg-white lg:col-span-2">
                    <div class="absolute inset-0 bg-gradient-to-r from-white via-white/90 to-white/20"></div>
                    <img src="{{ $mainImage }}" alt="{{ $mainArrival?->name ?? $arrivalFallbacks[0]['title'] }}"
                        class="absolute right-0 top-0 h-full w-2/3 object-cover opacity-30">
                    <div class="relative flex h-full min-h-[560px] max-w-[460px] flex-col p-8">
                        <p class="text-[11px] font-black uppercase tracking-[.18em] text-[#657891]">
                            {{ $mainArrival?->category?->name ?? $arrivalFallbacks[0]['category'] }}
                        </p>
                        <h3 class="mt-5 text-[34px] font-black leading-tight text-[#10233d]">
                            {{ $mainArrival?->name ?? $arrivalFallbacks[0]['title'] }}
                        </h3>
                        <p class="mt-6 text-[15px] leading-7 text-[#617086]">
                            {{ $mainArrival?->description ?? $arrivalFallbacks[0]['description'] }}
                        </p>
                        <div class="mt-auto flex items-center">
                            <p class="text-[20px] font-black text-[#c8102e]">
                                Rp
                                {{ number_format($mainArrival?->price ?? $arrivalFallbacks[0]['price'], 0, ',', '.') }}
                            </p>
                            <a href="{{ $mainArrival ? route('pelanggan.products.show', $mainArrival) : route('pelanggan.products.index') }}"
                                class="ml-6 flex h-12 w-12 items-center justify-center bg-[#c8102e] text-white hover:bg-[#9f0d24]"
                                aria-label="Open product">
                                <iconify-icon icon="mdi:cart-outline" class="fs-5"></iconify-icon>
                            </a>
                        </div>
                    </div>
                </article>

                @foreach ([1, 2] as $index)
                    @php
                        $item = $latestProducts->get($index);
                        $fallback = $arrivalFallbacks[$index];
                        $itemImage = $item?->images->first()?->image_path
                            ? asset('storage/' . $item->images->first()->image_path)
                            : $fallback['image'];
                    @endphp
                    <article class="flex flex-col bg-[#ffe2e7] p-7">
                        <img src="{{ $itemImage }}" alt="{{ $item?->name ?? $fallback['title'] }}"
                            class="mb-auto h-[270px] w-full object-cover">
                        <div class="pt-10">
                            <h3 class="text-[19px] font-black text-[#10233d]">{{ $item?->name ?? $fallback['title'] }}
                            </h3>
                            <p class="mt-3 text-[11px] font-black uppercase tracking-[.13em] text-[#657891]">
                                {{ $item?->category?->name ?? $fallback['category'] }}
                            </p>
                            <div class="mt-7 flex items-center justify-between">
                                <p class="text-[13px] font-black text-[#10233d]">
                                    Rp {{ number_format($item?->price ?? $fallback['price'], 0, ',', '.') }}
                                </p>
                                <a href="{{ $item ? route('pelanggan.products.show', $item) : route('pelanggan.products.index') }}"
                                    class="text-[#c8102e]" aria-label="Open product">
                                    <iconify-icon icon="mdi:arrow-top-right" class="fs-5"></iconify-icon>
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-[#f7faff] px-8 py-[100px]">
        <div class="mx-auto max-w-[1290px]">
            <div class="mx-auto mb-16 max-w-[560px] text-center">
                <h2 class="text-[34px] font-black uppercase tracking-[-.03em] text-[#10233d]">Our Products</h2>
                <p class="mt-4 text-[13px] leading-6 text-[#657891]">
                    Sourced from global leaders in manufacturing, our product range ensures technical excellence for
                    every project.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                @foreach (range(0, 3) as $index)
                    @php
                        $product = $products->get($index);
                        $fallback = $productFallbacks[$index];
                        $productImage = $product?->images->first()?->image_path
                            ? asset('storage/' . $product->images->first()->image_path)
                            : $fallback['image'];
                    @endphp
                    <a href="{{ $product ? route('pelanggan.products.show', $product) : route('pelanggan.products.index') }}"
                        class="group bg-white">
                        <div class="relative h-[295px] overflow-hidden bg-[#d9e2ee]">
                            @if ($index === 0)
                                <span
                                    class="absolute left-3 top-3 z-10 bg-white px-3 py-1 text-[10px] font-black uppercase tracking-[.12em] text-[#10233d]">New</span>
                            @endif
                            <img src="{{ $productImage }}" alt="{{ $product?->name ?? $fallback['title'] }}"
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        </div>
                        <div class="p-5">
                            <p class="text-[10px] font-black uppercase tracking-[.16em] text-[#7b8799]">
                                Category: {{ $product?->category?->name ?? $fallback['category'] }}
                            </p>
                            <h3 class="mt-3 min-h-[42px] text-[14px] font-black leading-5 text-[#10233d]">
                                {{ $product?->name ?? $fallback['title'] }}
                            </h3>
                            <p class="mt-3 text-[14px] font-black text-[#c8102e]">
                                Rp {{ number_format($product?->price ?? $fallback['price'], 0, ',', '.') }}
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-[#071d33] px-8 py-[105px] text-white">
        <div class="mx-auto grid max-w-[1290px] grid-cols-1 items-center gap-16 lg:grid-cols-2">
            <div>
                <h2 class="text-[36px] font-black uppercase leading-tight tracking-[-.03em]">
                    Technical Standards &<br>Certification
                </h2>
                <p class="mt-8 max-w-[520px] text-[14px] leading-7 text-[#b8c7d9]">
                    We provide full technical documentation for contractors and architects, ensuring every material
                    meets international safety and environmental standards.
                </p>

                <div class="mt-10 max-w-[610px] space-y-4">
                    <div class="flex items-center justify-between bg-white/7 px-6 py-5 text-[13px]">
                        <span class="font-black uppercase tracking-[.13em] text-[#ffe2e7]">Fire Rating</span>
                        <span class="font-semibold text-[#c8a77d]">Class A / ASTM E84</span>
                    </div>
                    <div class="flex items-center justify-between bg-white/7 px-6 py-5 text-[13px]">
                        <span class="font-black uppercase tracking-[.13em] text-[#ffe2e7]">Formaldehyde Emission</span>
                        <span class="font-semibold text-[#c8a77d]">E1 Standard</span>
                    </div>
                    <div class="flex items-center justify-between bg-white/7 px-6 py-5 text-[13px]">
                        <span class="font-black uppercase tracking-[.13em] text-[#ffe2e7]">Water Resistance</span>
                        <span class="font-semibold text-[#c8a77d]">Boiling Water Proof (BWP)</span>
                    </div>
                </div>
            </div>

            <div class="h-[520px] overflow-hidden bg-[#102c4a]">
                <div class="h-full w-full"
                    style="background-image: linear-gradient(135deg, rgba(255,255,255,.08), rgba(255,255,255,0)), repeating-linear-gradient(90deg, rgba(255,255,255,.12) 0 1px, transparent 1px 78px), repeating-linear-gradient(0deg, rgba(255,255,255,.08) 0 1px, transparent 1px 78px), url('{{ $localImages[1] }}'); background-size: cover, auto, auto, cover; background-position: center;">
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-[#fff1f3] px-8 pt-20 text-[#5d7190]">
        <div class="mx-auto grid max-w-[1290px] grid-cols-1 gap-12 border-b border-[#d9e7f7] pb-16 md:grid-cols-4">
            <div>
                <h4 class="text-[13px] font-black uppercase tracking-[.08em] text-[#c8102e]">Sarana Fittindo Palembang 
                </h4>
                <p class="mt-8 text-[12px] leading-6">
                    CV Sarana Fittindo is a leading provider of high-end interior building materials, specializing in
                    high-pressure laminates and architectural plywood.
                </p>
            </div>

            <div>
                <h4 class="text-[12px] font-black uppercase tracking-[.16em] text-[#c8102e]">Collections</h4>
                <ul class="mt-8 space-y-4 text-[12px]">
                    <li><a href="{{ route('pelanggan.products.index', ['category' => 'hpl']) }}"
                            class="hover:text-[#c8102e]">HPL Panels</a></li>
                    <li><a href="{{ route('pelanggan.products.index', ['category' => 'plywood']) }}"
                            class="hover:text-[#c8102e]">Marine Plywood</a></li>
                    <li><a href="{{ route('pelanggan.products.index', ['category' => 'adhesives']) }}"
                            class="hover:text-[#c8102e]">Contact Adhesives</a></li>
                    <li><a href="{{ route('pelanggan.products.index', ['category' => 'laminate']) }}"
                            class="hover:text-[#c8102e]">Edge Banding</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-[12px] font-black uppercase tracking-[.16em] text-[#c8102e]">Quick Links</h4>
                <ul class="mt-8 space-y-4 text-[12px]">
                    <li><a href="" class="hover:text-[#c8102e]">About Us</a></li>
                    <li><a href="{{ route('pelanggan.products.index') }}" class="hover:text-[#c8102e]">Shipping
                            Policy</a></li>
                    <li><a href="{{ route('pelanggan.products.index') }}" class="hover:text-[#c8102e]">Privacy
                            Policy</a></li>
                    <li><a href="{{ route('pelanggan.products.index') }}" class="hover:text-[#c8102e]">FAQ</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-[12px] font-black uppercase tracking-[.16em] text-[#c8102e]">Newsletter</h4>
                <p class="mt-8 text-[12px] leading-6">Stay updated with our latest material arrivals and industrial
                    news.</p>
                <form class="mt-5">
                    <input type="email" placeholder="Email address"
                        class="h-11 w-full border-0 bg-[#ffe2e7] px-4 text-[12px] text-[#244263] placeholder:text-[#7d91ab] focus:border-[#c8102e] focus:ring-[#c8102e]">
                    <button type="submit"
                        class="mt-3 h-11 w-full bg-[#c8102e] text-[11px] font-black uppercase tracking-[.18em] text-white hover:bg-[#9f0d24]">
                        Subscribe Now
                    </button>
                </form>
            </div>
        </div>

        <div class="mx-auto flex max-w-[1290px] items-center justify-between py-8 text-[11px]">
            <p>© 2024 CV Sarana Fittindo. High-End Material Solutions.</p>
            <div class="flex gap-5 text-[#6c83a6]">
                <iconify-icon icon="mdi:web"></iconify-icon>
                <iconify-icon icon="mdi:email-outline"></iconify-icon>
                <iconify-icon icon="mdi:shield-check-outline"></iconify-icon>
            </div>
        </div>
    </footer>
</x-pelanggan-layout>
