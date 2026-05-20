<x-pelanggan-layout>
    <!-- Breadcrumb -->
    <div class="bg-gray-100 px-12 py-4">
        <div class="text-sm text-gray-600">
            <a href="{{ route('pelanggan.dashboard') }}" class="hover:text-red-600">Home</a> /
            <a href="{{ route('pelanggan.products.index') }}" class="hover:text-red-600">Products</a> /
            <span>{{ $product->name }}</span>
        </div>
    </div>

    <!-- Product Detail Section -->
    <div class="px-12 py-12 bg-white">
        <div class="grid grid-cols-2 gap-12">
            <!-- Product Images -->
            <div>
                <!-- Main Image -->
                <div class="mb-6 bg-gray-200 rounded-lg overflow-hidden h-96">
                    @if ($product->images && $product->images->count() > 0)
                        <img id="mainImage" src="{{ asset('storage/' . $product->images->first()->image_path) }}"
                            alt="{{ $product->name }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-gray-300">
                            <span class="text-gray-500">No Image Available</span>
                        </div>
                    @endif
                </div>

                <!-- Image Thumbnails -->
                @if ($product->images && $product->images->count() > 1)
                    <div class="grid grid-cols-4 gap-4">
                        @foreach ($product->images as $image)
                            <div class="cursor-pointer border-2 border-gray-300 rounded-lg overflow-hidden hover:border-red-600 transition"
                                onclick="document.getElementById('mainImage').src = '{{ asset('storage/' . $image->image_path) }}';">
                                <img src="{{ asset('storage/' . $image->image_path) }}" alt="{{ $product->name }}"
                                    class="w-full h-20 object-cover">
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Product Information -->
            <div>
                <!-- Category & Brand -->
                <div class="mb-4">
                    <p class="text-sm text-gray-500 font-semibold uppercase">
                        {{ $product->category?->name ?? 'Uncategorized' }}
                    </p>
                    @if ($product->brand)
                        <p class="text-sm text-gray-600">By {{ $product->brand->name }}</p>
                    @endif
                </div>

                <!-- Product Name -->
                <h1 class="text-4xl font-bold text-gray-900 mb-6">
                    {{ $product->name }}
                </h1>

                <!-- Price -->
                <div class="mb-6">
                    <p class="text-sm text-gray-600 mb-2">PRICE</p>
                    <p class="text-3xl font-bold text-gray-900">
                        Rp {{ number_format($product->price, 0, ',', '.') }}
                    </p>
                </div>

                <!-- Stock Status -->
                <div class="mb-6">
                    @if ($product->stock > 0)
                        <div class="inline-flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-green-600"></div>
                            <span class="text-sm font-semibold text-green-600">TERSEDIA</span>
                        </div>
                    @else
                        <div class="inline-flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-600"></div>
                            <span class="text-sm font-semibold text-red-600">OUT OF STOCK</span>
                        </div>
                    @endif
                </div>

                <!-- Description -->
                @if ($product->description)
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-900 mb-3">DESCRIPTION</h3>
                        <p class="text-gray-700 leading-relaxed">
                            {{ $product->description }}
                        </p>
                    </div>
                @endif

                <!-- Quantity & Add to Cart -->
                <div class="mb-8 flex items-center gap-4">
                    <div class="flex items-center border border-gray-300 rounded-lg">
                        <button class="px-4 py-2 text-gray-600 hover:text-gray-900"
                            onclick="decreaseQuantity()">−</button>
                        <input type="number" id="quantity" value="1" min="1"
                            class="w-16 text-center border-l border-r border-gray-300 py-2 focus:outline-none" readonly>
                        <button class="px-4 py-2 text-gray-600 hover:text-gray-900"
                            onclick="increaseQuantity()">+</button>
                    </div>
                    @if ($product->stock > 0)
                        <form action="{{ Auth::check() ? route('pelanggan.cart.store', $product) : route('login') }}"
                            method="{{ Auth::check() ? 'POST' : 'GET' }}" class="flex-1">
                            @auth
                                @csrf
                            @endauth
                            <input type="hidden" name="quantity" id="cart_quantity" value="1">
                            <button type="submit"
                                class="w-full bg-red-700 hover:bg-red-800 text-white font-bold py-3 px-6 transition duration-300 rounded-lg">
                                ADD TO CART
                            </button>
                        </form>
                    @else
                        <button class="flex-1 bg-gray-400 text-white font-bold py-3 px-6 rounded-lg cursor-not-allowed"
                            disabled>
                            OUT OF STOCK
                        </button>
                    @endif
                </div>

                <!-- Wishlist & Share -->
                <div class="flex items-center gap-4 border-t border-b border-gray-200 py-4 mb-8">
                    @php
                        $isWishlisted = Auth::check() && Auth::user()->wishlists()->where('product_id', $product->id)->exists();
                    @endphp
                    <form action="{{ Auth::check() ? route('pelanggan.wishlist.toggle', $product) : route('login') }}"
                        method="{{ Auth::check() ? 'POST' : 'GET' }}">
                        @auth
                            @csrf
                        @endauth
                    <button type="submit" class="flex items-center gap-2 {{ $isWishlisted ? 'text-red-600' : 'text-gray-700 hover:text-red-600' }} transition">
                        <iconify-icon icon="{{ $isWishlisted ? 'mdi:heart' : 'mdi:heart-outline' }}" width="20"></iconify-icon>
                        <span class="text-sm font-semibold">WISHLIST</span>
                    </button>
                    </form>
                    <button class="flex items-center gap-2 text-gray-700 hover:text-red-600 transition">
                        <iconify-icon icon="mdi:share-variant-outline" width="20"></iconify-icon>
                        <span class="text-sm font-semibold">SHARE</span>
                    </button>
                </div>

                <!-- Additional Details -->
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-gray-700 font-semibold">CATEGORY:</span>
                        <span class="text-gray-900">{{ $product->category?->name ?? 'N/A' }}</span>
                    </div>
                    @if ($product->brand)
                        <div class="flex justify-between">
                            <span class="text-gray-700 font-semibold">BRAND:</span>
                            <span class="text-gray-900">{{ $product->brand->name }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-gray-700 font-semibold">STOCK:</span>
                        <span class="text-gray-900">{{ $product->stock ? 'Tersedia' : 'Tidak Tersedia' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products Section -->
    @if ($relatedProducts && $relatedProducts->count() > 0)
        <div class="px-12 py-16 bg-gray-50">
            <div class="mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">RELATED PRODUCTS</h2>
                <div class="w-12 h-1 bg-red-600"></div>
            </div>

            <div class="grid grid-cols-4 gap-8">
                @foreach ($relatedProducts as $related)
                    <div class="bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-lg transition duration-300">
                        <!-- Product Image -->
                        <a href="{{ route('pelanggan.products.show', $related) }}"
                            class="relative h-56 bg-gray-200 overflow-hidden group block">
                            @if ($related->images && $related->images->count() > 0)
                                <img src="{{ asset('storage/' . $related->images->first()->image_path) }}"
                                    alt="{{ $related->name }}"
                                    class="w-full h-full object-cover group-hover:scale-110 transition duration-300">
                            @else
                                <div class="w-full h-full bg-gray-300 flex items-center justify-center">
                                    <span class="text-gray-500">No Image</span>
                                </div>
                            @endif

                            <!-- Stock Badge -->
                            @if ($related->stock > 0)
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

                        <!-- Product Info -->
                        <div class="p-5">
                            <!-- Category -->
                            <p class="text-xs text-gray-500 font-semibold mb-2 uppercase">
                                {{ $related->category?->name ?? 'Uncategorized' }}
                            </p>

                            <!-- Product Name -->
                            <h3
                                class="text-sm font-bold text-gray-900 mb-2 line-clamp-2 hover:text-red-600 transition">
                                <a href="{{ route('pelanggan.products.show', $related) }}">
                                    {{ $related->name }}
                                </a>
                            </h3>

                            <!-- Price -->
                            <p class="text-lg font-bold text-gray-900 mb-4">
                                Rp {{ number_format($related->price, 0, ',', '.') }}
                            </p>

                            <!-- Add to Cart Button -->
                            <button
                                class="w-full bg-red-700 hover:bg-red-800 text-white font-bold py-2 px-4 transition duration-300 text-sm rounded">
                                ADD TO CART
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <script>
        function increaseQuantity() {
            const input = document.getElementById('quantity');
            input.value = parseInt(input.value) + 1;
            if (document.getElementById('cart_quantity')) {
                document.getElementById('cart_quantity').value = input.value;
            }
        }

        function decreaseQuantity() {
            const input = document.getElementById('quantity');
            if (parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
                if (document.getElementById('cart_quantity')) {
                    document.getElementById('cart_quantity').value = input.value;
                }
            }
        }
    </script>
</x-pelanggan-layout>
