<x-pelanggan-layout>
    @php
        $productImages = $product->images ?? collect();
        $primaryImage = $product->primary_image ?? $productImages->first();
        $isAvailable = $product->isAvailable();
        $isWishlisted = Auth::check() && Auth::user()->wishlists()->where('product_id', $product->id)->exists();
        $detailRows = [
            'Kategori' => $product->category?->name,
            'Merek' => $product->brand?->name,
            'Berat' => $product->weight ? number_format((float) $product->weight, 0, ',', '.') . ' gram' : null,
            'Ketebalan' => $product->thickness,
            'Dimensi' => $product->dimensions,
            'Stok' => $product->stock . ' item',
            'Status' => $isAvailable ? 'Tersedia' : 'Habis',
        ];
    @endphp

    <div class="bg-[#f5f8fc]">
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-[#436aa6]">
                <a href="{{ route('pelanggan.dashboard') }}" class="hover:text-[#c8102e]">Beranda</a>
                <span class="text-[#9caec6]">/</span>
                <a href="{{ route('pelanggan.products.index') }}" class="hover:text-[#c8102e]">Produk</a>
                <span class="text-[#9caec6]">/</span>
                <span class="max-w-[260px] truncate text-[#10233d] sm:max-w-none">{{ $product->name }}</span>
            </div>
        </div>
    </div>

    <div class="bg-white">
        <section class="mx-auto grid max-w-7xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[1.05fr_.95fr] lg:px-8 lg:py-14">
            <div class="space-y-4">
                <div class="overflow-hidden rounded-md border border-[#d8e2f0] bg-[#eef4fb]">
                    <div class="aspect-[4/3] w-full">
                        @if ($primaryImage)
                            <img id="mainImage" src="{{ asset('storage/' . $primaryImage->image_path) }}"
                                alt="{{ $product->name }}" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full flex-col items-center justify-center gap-3 text-[#6e84a3]">
                                <iconify-icon icon="mdi:image-off-outline" class="text-5xl"></iconify-icon>
                                <span class="text-sm font-bold">Gambar produk belum tersedia</span>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($productImages->count() > 1)
                    <div class="grid grid-cols-4 gap-3 sm:grid-cols-5">
                        @foreach ($productImages as $image)
                            <button type="button"
                                class="product-thumb aspect-square overflow-hidden rounded-md border-2 border-[#d8e2f0] bg-[#eef4fb] transition hover:border-[#c8102e] focus:border-[#c8102e] focus:outline-none"
                                data-image="{{ asset('storage/' . $image->image_path) }}"
                                aria-label="Lihat gambar {{ $loop->iteration }} untuk {{ $product->name }}">
                                <img src="{{ asset('storage/' . $image->image_path) }}" alt="{{ $product->name }}"
                                    class="h-full w-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="lg:sticky lg:top-6 lg:self-start">
                <div class="rounded-md border border-[#d8e2f0] bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="rounded-full bg-[#e9f1fb] px-3 py-1 text-xs font-black uppercase tracking-[.12em] text-[#436aa6]">
                            {{ $product->category?->name ?? 'Tanpa Kategori' }}
                        </span>
                        @if ($product->brand)
                            <span
                                class="rounded-full bg-[#fff1f3] px-3 py-1 text-xs font-black uppercase tracking-[.12em] text-[#c8102e]">
                                {{ $product->brand->name }}
                            </span>
                        @endif
                    </div>

                    <h1 class="mt-5 text-3xl font-black leading-tight text-[#10233d] sm:text-4xl">{{ $product->name }}
                    </h1>

                    <div class="mt-5 flex flex-wrap items-end justify-between gap-4 border-b border-[#d8e2f0] pb-5">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.18em] text-[#6e84a3]">Harga</p>
                            <p class="mt-1 text-3xl font-black text-[#10233d]">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                        </div>
                        <div class="text-right">
                            <span
                                class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-black {{ $isAvailable ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                <span
                                    class="h-2 w-2 rounded-full {{ $isAvailable ? 'bg-green-600' : 'bg-red-600' }}"></span>
                                {{ $isAvailable ? 'Tersedia' : 'Habis' }}
                            </span>
                            <p class="mt-2 text-xs font-semibold text-[#6e84a3]">Stok: {{ $product->stock }} item</p>
                        </div>
                    </div>

                    @if ($product->description)
                        <p class="mt-5 text-sm leading-7 text-[#4d6380]">{{ $product->description }}</p>
                    @else
                        <p class="mt-5 text-sm leading-7 text-[#6e84a3]">Deskripsi produk belum tersedia.</p>
                    @endif

                    <div class="mt-6 grid gap-3 sm:grid-cols-[140px_1fr]">
                        <div class="flex h-12 items-center overflow-hidden rounded-md border border-[#d8e2f0]">
                            <button type="button"
                                class="flex h-full w-11 items-center justify-center text-lg font-black text-[#436aa6] hover:bg-[#f5f8fc]"
                                onclick="decreaseQuantity()" aria-label="Kurangi jumlah">-</button>
                            <input type="number" id="quantity" value="1" min="1" max="{{ max((int) $product->stock, 1) }}" inputmode="numeric"
                                class="h-full w-full border-x border-[#d8e2f0] text-center text-sm font-black text-[#10233d] focus:outline-none">
                            <button type="button"
                                class="flex h-full w-11 items-center justify-center text-lg font-black text-[#436aa6] hover:bg-[#f5f8fc]"
                                onclick="increaseQuantity()" aria-label="Tambah jumlah">+</button>
                        </div>

                        @if ($isAvailable)
                            <form
                                action="{{ Auth::check() ? route('pelanggan.cart.store', $product) : route('login') }}"
                                method="{{ Auth::check() ? 'POST' : 'GET' }}">
                                @auth
                                    @csrf
                                @endauth
                                <input type="hidden" name="quantity" id="cart_quantity" value="1"
                                    data-max="{{ $product->stock }}">
                                <button type="submit"
                                    class="flex h-12 w-full items-center justify-center gap-2 rounded-md bg-[#c8102e] px-5 text-sm font-black uppercase tracking-[.12em] text-white transition hover:bg-[#a40d25]">
                                    <iconify-icon icon="mdi:cart-plus"></iconify-icon>
                                    Tambah ke Keranjang
                                </button>
                            </form>
                        @else
                            <button type="button" disabled
                                class="flex h-12 w-full cursor-not-allowed items-center justify-center rounded-md bg-gray-300 px-5 text-sm font-black uppercase tracking-[.12em] text-white">
                                Stok Habis
                            </button>
                        @endif
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <form
                            action="{{ Auth::check() ? route('pelanggan.wishlist.toggle', $product) : route('login') }}"
                            method="{{ Auth::check() ? 'POST' : 'GET' }}">
                            @auth
                                @csrf
                            @endauth
                            <button type="submit"
                                class="flex h-11 w-full items-center justify-center gap-2 rounded-md border border-[#d8e2f0] text-sm font-black uppercase tracking-[.12em] transition {{ $isWishlisted ? 'border-[#c8102e] text-[#c8102e]' : 'text-[#10233d] hover:border-[#c8102e] hover:text-[#c8102e]' }}">
                                <iconify-icon
                                    icon="{{ $isWishlisted ? 'mdi:heart' : 'mdi:heart-outline' }}"></iconify-icon>
                                Wishlist
                            </button>
                        </form>
                        <button type="button" id="shareButton"
                            class="flex h-11 w-full items-center justify-center gap-2 rounded-md border border-[#d8e2f0] text-sm font-black uppercase tracking-[.12em] text-[#10233d] transition hover:border-[#c8102e] hover:text-[#c8102e]">
                            <iconify-icon icon="mdi:share-variant-outline"></iconify-icon>
                            Bagikan
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto grid max-w-7xl gap-6 px-4 pb-12 sm:px-6 lg:grid-cols-[1fr_.9fr] lg:px-8">
            <div class="rounded-md border border-[#d8e2f0] bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-xl font-black uppercase text-[#10233d]">Detail Produk</h2>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach ($detailRows as $label => $value)
                        <div class="rounded-md bg-[#f5f8fc] p-4">
                            <p class="text-xs font-black uppercase tracking-[.14em] text-[#6e84a3]">{{ $label }}
                            </p>
                            <p class="mt-1 text-sm font-bold text-[#10233d]">{{ filled($value) ? $value : '-' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- <div class="rounded-md border border-[#d8e2f0] bg-[#10233d] p-5 text-white shadow-sm sm:p-6">
                <h2 class="text-xl font-black uppercase">Informasi Belanja</h2>
                <div class="mt-5 space-y-4">
                    <div class="flex items-start gap-3">
                        <iconify-icon icon="mdi:truck-fast-outline" class="mt-1 text-xl text-[#c8d8ee]"></iconify-icon>
                        <div>
                            <p class="font-bold">Pengiriman diproses admin</p>
                            <p class="mt-1 text-sm text-[#c8d8ee]">Biaya kirim akan dikonfirmasi sesuai alamat
                                pelanggan.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <iconify-icon icon="mdi:shield-check-outline"
                            class="mt-1 text-xl text-[#c8d8ee]"></iconify-icon>
                        <div>
                            <p class="font-bold">Produk aktif dan terkurasi</p>
                            <p class="mt-1 text-sm text-[#c8d8ee]">Produk yang tampil sudah aktif di katalog pelanggan.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <iconify-icon icon="mdi:store-outline" class="mt-1 text-xl text-[#c8d8ee]"></iconify-icon>
                        <div>
                            <p class="font-bold">CV Sarana Fittindo</p>
                            <p class="mt-1 text-sm text-[#c8d8ee]">Material interior untuk kebutuhan rumah, kantor, dan
                                proyek.</p>
                        </div>
                    </div>
                </div>
            </div> --}}
        </section>

        @if ($relatedProducts && $relatedProducts->count() > 0)
            <section class="bg-[#f5f8fc] px-4 py-12 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="text-sm font-black uppercase tracking-[.18em] text-[#c8102e]">Rekomendasi</p>
                            <h2 class="mt-2 text-3xl font-black text-[#10233d]">Produk Terkait</h2>
                        </div>
                        <a href="{{ route('pelanggan.products.index', ['category' => $product->category?->slug]) }}"
                            class="text-sm font-black uppercase tracking-[.14em] text-[#436aa6] hover:text-[#c8102e]">Lihat
                            Kategori</a>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($relatedProducts as $related)
                            <article
                                class="flex overflow-hidden rounded-md border border-[#d8e2f0] bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                <div class="flex w-full flex-col">
                                    <a href="{{ route('pelanggan.products.show', $related) }}"
                                        class="relative block aspect-[4/3] overflow-hidden bg-[#eef4fb]">
                                        @if ($related->primary_image)
                                            <img src="{{ asset('storage/' . $related->primary_image->image_path) }}"
                                                alt="{{ $related->name }}"
                                                class="h-full w-full object-cover transition duration-300 hover:scale-105">
                                        @else
                                            <div
                                                class="flex h-full w-full items-center justify-center text-sm font-bold text-[#6e84a3]">
                                                Tidak Ada Gambar</div>
                                        @endif
                                        <span
                                            class="absolute left-3 top-3 rounded-full px-3 py-1 text-xs font-black {{ $related->isAvailable() ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ $related->isAvailable() ? 'Tersedia' : 'Habis' }}
                                        </span>
                                    </a>

                                    <div class="flex flex-1 flex-col p-4">
                                        <p class="text-xs font-black uppercase tracking-[.12em] text-[#6e84a3]">
                                            {{ $related->category?->name ?? 'Tanpa Kategori' }}</p>
                                        <h3 class="mt-2 line-clamp-2 text-sm font-black leading-5 text-[#10233d]">
                                            <a href="{{ route('pelanggan.products.show', $related) }}"
                                                class="hover:text-[#c8102e]">{{ $related->name }}</a>
                                        </h3>
                                        @if ($related->brand)
                                            <p class="mt-1 text-xs font-semibold text-[#6e84a3]">
                                                {{ $related->brand->name }}</p>
                                        @endif
                                        <p class="mt-3 text-lg font-black text-[#10233d]">Rp
                                            {{ number_format($related->price, 0, ',', '.') }}</p>

                                        <div class="mt-auto pt-4">
                                            @if ($related->isAvailable())
                                                <form
                                                    action="{{ Auth::check() ? route('pelanggan.cart.store', $related) : route('login') }}"
                                                    method="{{ Auth::check() ? 'POST' : 'GET' }}">
                                                    @auth
                                                        @csrf
                                                        <input type="hidden" name="quantity" value="1">
                                                    @endauth
                                                    <button type="submit"
                                                        class="flex h-10 w-full items-center justify-center rounded-md bg-[#c8102e] text-xs font-black uppercase tracking-[.12em] text-white hover:bg-[#a40d25]">
                                                        Tambah
                                                    </button>
                                                </form>
                                            @else
                                                <button type="button" disabled
                                                    class="flex h-10 w-full cursor-not-allowed items-center justify-center rounded-md bg-gray-300 text-xs font-black uppercase tracking-[.12em] text-white">
                                                    Habis
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </div>

    <script>
        const mainImage = document.getElementById('mainImage');
        const thumbs = document.querySelectorAll('.product-thumb');

        thumbs.forEach((thumb) => {
            thumb.addEventListener('click', () => {
                if (mainImage && thumb.dataset.image) {
                    mainImage.src = thumb.dataset.image;
                    thumbs.forEach((item) => item.classList.remove('border-[#c8102e]'));
                    thumb.classList.add('border-[#c8102e]');
                }
            });
        });

        function increaseQuantity() {
            const input = document.getElementById('quantity');

            setQuantity((parseInt(input.value, 10) || 0) + 1);
        }

        function decreaseQuantity() {
            const input = document.getElementById('quantity');
            setQuantity((parseInt(input.value, 10) || 1) - 1);
        }

        function quantityMax() {
            const cartQuantity = document.getElementById('cart_quantity');

            return cartQuantity ? parseInt(cartQuantity.dataset.max, 10) || 1 : 1;
        }

        function syncCartQuantity(value) {
            const cartQuantity = document.getElementById('cart_quantity');

            if (cartQuantity) {
                cartQuantity.value = value;
            }
        }

        function setQuantity(value) {
            const input = document.getElementById('quantity');
            const maxStok = quantityMax();
            const quantity = Math.min(Math.max(parseInt(value, 10) || 1, 1), maxStok);

            input.value = quantity;
            syncCartQuantity(quantity);
        }

        const quantityInput = document.getElementById('quantity');
        quantityInput?.addEventListener('input', () => {
            const rawValue = quantityInput.value;

            if (rawValue === '') {
                syncCartQuantity(1);
                return;
            }

            setQuantity(rawValue);
        });
        quantityInput?.addEventListener('blur', () => setQuantity(quantityInput.value));

        const shareButton = document.getElementById('shareButton');
        shareButton?.addEventListener('click', async () => {
            const shareData = {
                title: @json($product->name),
                text: @json('Lihat produk ' . $product->name),
                url: window.location.href,
            };

            try {
                if (navigator.share) {
                    await navigator.share(shareData);
                    return;
                }

                await navigator.clipboard.writeText(window.location.href);
                shareButton.classList.add('border-[#c8102e]', 'text-[#c8102e]');
                setTimeout(() => shareButton.classList.remove('border-[#c8102e]', 'text-[#c8102e]'), 1200);
            } catch (error) {
                console.error(error);
            }
        });
    </script>
</x-pelanggan-layout>
