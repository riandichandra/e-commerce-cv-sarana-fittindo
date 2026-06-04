<x-pelanggan-layout>
    <!-- Header Section -->
    <div class="bg-slate-900 text-white px-12 py-12">
        <div class="max-w-6xl">
            <h1 class="text-4xl font-bold mb-4">ALL PRODUCTS</h1>
            <p class="text-gray-300 text-base">Browse our complete collection of premium building materials and supplies
            </p>
        </div>
    </div>

    <!-- Filters and Produk Section -->
    <div class="px-12 py-12 bg-gray-50">
        <div class="grid grid-cols-4 gap-8">
            <!-- Sidebar Filters -->
            <div class="col-span-1">
                <div class="bg-white rounded-lg p-6 sticky top-4">
                    <h3 class="text-lg font-bold text-gray-900 mb-6">FILTERS</h3>

                    <!-- Kategori Filter -->
                    <div class="mb-8">
                        <h4 class="font-semibold text-gray-900 mb-4">CATEGORY</h4>
                        <div class="space-y-3">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" class="w-4 h-4 text-red-600 rounded"
                                    @if (request('category') === '') checked @endif
                                    onchange="document.location.href = '{{ route('pelanggan.products.index') }}';">
                                <span class="ml-3 text-sm text-gray-700">Semua Kategori</span>
                            </label>
                            @foreach ($categories as $category)
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" class="w-4 h-4 text-red-600 rounded"
                                        @if (request('category') === $category->slug) checked @endif
                                        onchange="document.location.href = '{{ route('pelanggan.products.index') }}?category={{ $category->slug }}';">
                                    <span class="ml-3 text-sm text-gray-700">{{ $category->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Harga Filter -->
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-4">PRICE RANGE</h4>
                        <div class="space-y-3">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" class="w-4 h-4 text-red-600 rounded"
                                    @if (!request('price_range')) checked @endif
                                    onchange="document.location.href = '{{ route('pelanggan.products.index') }}';">
                                <span class="ml-3 text-sm text-gray-700">All Hargas</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" class="w-4 h-4 text-red-600 rounded"
                                    @if (request('price_range') === 'under_100k') checked @endif
                                    onchange="document.location.href = '{{ route('pelanggan.products.index') }}?price_range=under_100k';">
                                <span class="ml-3 text-sm text-gray-700">Under Rp 100K</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" class="w-4 h-4 text-red-600 rounded"
                                    @if (request('price_range') === '100k_500k') checked @endif
                                    onchange="document.location.href = '{{ route('pelanggan.products.index') }}?price_range=100k_500k';">
                                <span class="ml-3 text-sm text-gray-700">Rp 100K - 500K</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" class="w-4 h-4 text-red-600 rounded"
                                    @if (request('price_range') === 'above_500k') checked @endif
                                    onchange="document.location.href = '{{ route('pelanggan.products.index') }}?price_range=above_500k';">
                                <span class="ml-3 text-sm text-gray-700">Above Rp 500K</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Produk Grid -->
            <div class="col-span-3">
                <div class="flex justify-between items-center mb-8">
                    <p class="text-sm text-gray-600">Showing {{ $products->count() }} of {{ $products->total() }}
                        products</p>
                    <select
                        class="border border-gray-300 rounded-lg px-4 py-2 text-sm text-gray-700 focus:outline-none focus:border-red-600"
                        onchange="document.location.href = this.value;">
                        <option value="{{ route('pelanggan.products.index') }}">Sort by Latest</option>
                        <option value="{{ route('pelanggan.products.index') }}?sort=price_asc">Harga: Low to High
                        </option>
                        <option value="{{ route('pelanggan.products.index') }}?sort=price_desc">Harga: High to Low
                        </option>
                        <option value="{{ route('pelanggan.products.index') }}?sort=popular">Most Popular</option>
                    </select>
                </div>

                <div class="grid grid-cols-3 gap-8">
                    @forelse ($products as $product)
                        <div
                            class="bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-lg transition duration-300 flex flex-col">
                            <!-- Gambar Produk -->
                            <a href="{{ route('pelanggan.products.show', $product) }}"
                                class="relative h-56 bg-gray-200 overflow-hidden group block">
                                @if ($product->primary_image)
                                    <img src="{{ asset('storage/' . $product->images->first()->image_path) }}"
                                        alt="{{ $product->name }}"
                                        class="w-full h-full object-cover group-hover:scale-110 transition duration-300">
                                @else
                                    <div class="w-full h-full bg-gray-300 flex items-center justify-center">
                                        <span class="text-gray-500">Tidak Ada Gambar</span>
                                    </div>
                                @endif

                                <!-- Wishlist Button -->
                                <form
                                    action="{{ Auth::check() ? route('pelanggan.wishlist.toggle', $product) : route('login') }}"
                                    method="{{ Auth::check() ? 'POST' : 'GET' }}" class="absolute top-4 right-4 z-10"
                                    onclick="event.stopPropagation();">
                                    @auth
                                        @csrf
                                    @endauth
                                    @php
                                        $isWishlisted =
                                            Auth::check() &&
                                            Auth::user()->wishlists()->where('product_id', $product->id)->exists();
                                    @endphp
                                    <button type="submit"
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-white shadow-md transition duration-300 {{ $isWishlisted ? 'text-red-600' : 'text-gray-700 hover:bg-red-600 hover:text-white' }}"
                                        aria-label="Toggle wishlist">
                                        <iconify-icon
                                            icon="{{ $isWishlisted ? 'mdi:heart' : 'mdi:heart-outline' }}"></iconify-icon>
                                    </button>
                                </form>

                                <!-- Label Stok -->
                                @if ($product->stock > 0)
                                    <div
                                        class="absolute bottom-4 left-4 bg-green-600 text-white px-3 py-1 rounded text-xs font-semibold">
                                        IN STOCK
                                    </div>
                                @else
                                    <div
                                        class="absolute bottom-4 left-4 bg-red-600 text-white px-3 py-1 rounded text-xs font-semibold">
                                        OUT OF STOCK
                                    </div>
                                @endif
                            </a>

                            <!-- Info Produk -->
                            <div class="p-5 flex flex-col flex-1">
                                <!-- Kategori -->
                                <p class="text-xs text-gray-500 font-semibold mb-2 uppercase">
                                    {{ $product->category?->name ?? 'Uncategorized' }}
                                </p>

                                <!-- Nama Produk -->
                                <h3
                                    class="text-sm font-bold text-gray-900 mb-2 line-clamp-2 hover:text-red-600 transition">
                                    <a href="{{ route('pelanggan.products.show', $product) }}">
                                        {{ $product->name }}
                                    </a>
                                </h3>

                                <!-- Merek -->
                                @if ($product->brand)
                                    <p class="text-xs text-gray-500 mb-3">By {{ $product->brand->name }}</p>
                                @endif

                                <!-- Harga -->
                                <p class="text-lg font-bold text-gray-900 mb-4">
                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                </p>

                                <!-- Tombol Tambah ke Keranjang -->
                                @if ($product->stock > 0)
                                    <form
                                        action="{{ Auth::check() ? route('pelanggan.cart.store', $product) : route('login') }}"
                                        method="{{ Auth::check() ? 'POST' : 'GET' }}" class="mt-auto">
                                        @auth
                                            @csrf
                                            <input type="hidden" name="quantity" value="1">
                                        @endauth
                                        <button type="submit"
                                            class="w-full bg-red-700 hover:bg-red-800 text-white font-bold py-2 px-4 transition duration-300 text-sm rounded">
                                            ADD TO CART
                                        </button>
                                    </form>
                                @else
                                    <button type="button"
                                        class="w-full bg-gray-400 text-white font-bold py-2 px-4 transition duration-300 text-sm rounded cursor-not-allowed"
                                        disabled>
                                        OUT OF STOCK
                                    </button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="col-span-3 py-12 text-center">
                            <p class="text-gray-500 text-lg">No products found</p>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                @if ($products->hasPages())
                    <div class="mt-12 flex justify-center">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-pelanggan-layout>
