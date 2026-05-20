<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div>
                    <h2 class="text-lg font-medium text-gray-900">
                        Wishlist Produk
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Produk yang Anda tandai dengan love akan muncul di sini.
                    </p>

                    <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @forelse ($wishlistItems as $wishlist)
                            @if ($wishlist->product)
                                <div class="border border-gray-200 bg-white">
                                    <a href="{{ route('pelanggan.products.show', $wishlist->product) }}" class="block h-40 overflow-hidden bg-gray-100">
                                        @if ($wishlist->product->images->first())
                                            <img src="{{ asset('storage/' . $wishlist->product->images->first()->image_path) }}"
                                                alt="{{ $wishlist->product->name }}" class="h-full w-full object-cover">
                                        @endif
                                    </a>
                                    <div class="p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            {{ $wishlist->product->category?->name ?? 'Product' }}
                                        </p>
                                        <a href="{{ route('pelanggan.products.show', $wishlist->product) }}"
                                            class="mt-2 block text-sm font-bold text-gray-900 hover:text-red-700">
                                            {{ $wishlist->product->name }}
                                        </a>
                                        <p class="mt-2 text-sm font-bold text-red-700">
                                            Rp {{ number_format($wishlist->product->price, 0, ',', '.') }}
                                        </p>
                                        <form action="{{ route('pelanggan.wishlist.toggle', $wishlist->product) }}" method="POST" class="mt-4">
                                            @csrf
                                            <button type="submit" class="text-xs font-bold uppercase tracking-wide text-red-600 hover:text-red-800">
                                                Remove Wishlist
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <div class="col-span-full border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500">
                                Belum ada produk wishlist.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
