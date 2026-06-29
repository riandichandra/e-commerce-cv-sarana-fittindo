@php
    $isAvailable = $product->isAvailable();
@endphp

<article class="flex overflow-hidden rounded-md border border-[#d8e2f0] bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="flex w-full flex-col">
        <a href="{{ route('pelanggan.products.show', $product) }}"
            class="relative block aspect-[4/3] overflow-hidden bg-[#eef4fb]">
            @if ($product->primary_image)
                <img src="{{ asset('storage/' . $product->primary_image->image_path) }}" alt="{{ $product->name }}"
                    class="h-full w-full object-cover transition duration-300 hover:scale-105">
            @else
                <div class="flex h-full w-full items-center justify-center px-4 text-center text-sm font-bold text-[#6e84a3]">
                    Tidak Ada Gambar
                </div>
            @endif
            <span
                class="absolute left-3 top-3 rounded-full px-3 py-1 text-xs font-black {{ $isAvailable ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                {{ $isAvailable ? 'Tersedia' : 'Habis' }}
            </span>
        </a>

        <div class="flex flex-1 flex-col p-4">
            @if ($product->category)
                <a href="{{ route('pelanggan.categories.show', $product->category) }}"
                    class="text-xs font-black uppercase tracking-[.12em] text-[#6e84a3] hover:text-[#c8102e]">
                    {{ $product->category->name }}
                </a>
            @else
                <p class="text-xs font-black uppercase tracking-[.12em] text-[#6e84a3]">Tanpa Kategori</p>
            @endif

            <h3 class="mt-2 line-clamp-2 text-sm font-black leading-5 text-[#10233d]">
                <a href="{{ route('pelanggan.products.show', $product) }}" class="hover:text-[#c8102e]">
                    {{ $product->name }}
                </a>
            </h3>

            @if ($product->brand)
                <a href="{{ route('pelanggan.brands.show', $product->brand) }}"
                    class="mt-1 text-xs font-semibold text-[#6e84a3] hover:text-[#c8102e]">
                    {{ $product->brand->name }}
                </a>
            @endif

            <p class="mt-3 text-lg font-black text-[#10233d]">Rp {{ number_format($product->price, 0, ',', '.') }}</p>

            <div class="mt-auto pt-4">
                @if ($isAvailable)
                    <form action="{{ Auth::check() ? route('pelanggan.cart.store', $product) : route('login') }}"
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
