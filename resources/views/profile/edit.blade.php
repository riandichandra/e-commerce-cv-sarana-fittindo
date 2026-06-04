<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.18em] text-gray-500">Account</p>
                <h2 class="mt-1 text-2xl font-black text-[#8A1022]">
                    {{ __('Profile') }}
                </h2>
            </div>
            <a href="{{ route('pelanggan.dashboard') }}"
                class="inline-flex items-center gap-2 bg-[#C8102E] px-4 py-2 text-xs font-black uppercase tracking-[.14em] text-white hover:bg-[#9F0D24]">
                <iconify-icon icon="mdi:view-dashboard-outline" class="fs-6"></iconify-icon>
                Dasbor
            </a>
        </div>
    </x-slot>

    <div class="bg-[#FFF7F8] py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="overflow-hidden bg-[#10233d] text-white shadow-sm">
                <div class="grid grid-cols-1 gap-6 p-6 lg:grid-cols-[1fr_320px] lg:p-8">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[.22em] text-blue-100">Pusat Profil</p>
                        <h1 class="mt-4 text-4xl font-black tracking-tight">{{ $user->name }}</h1>
                        <p class="mt-3 max-w-2xl text-sm font-medium leading-6 text-blue-100">
                            Kelola informasi akun dan keamanan password untuk akses portal {{ $roleName }}.
                        </p>
                    </div>
                    <div class="bg-white/10 p-5">
                        <p class="text-xs font-black uppercase tracking-[.16em] text-blue-100">Signed in as</p>
                        <p class="mt-3 text-xl font-black">{{ $roleName }}</p>
                        <p class="mt-2 text-sm font-semibold text-blue-100">{{ $user->email }}</p>
                        <div class="mt-5 flex items-center gap-2 text-sm font-semibold text-blue-100">
                            <iconify-icon icon="mdi:shield-account-outline" class="fs-5"></iconify-icon>
                            <span>{{ $user->is_active ? 'Akun aktif' : 'Akun nonaktif' }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <section class="bg-white p-6 shadow-sm lg:p-8">
                    <div class="mb-6 flex items-start gap-4 border-b border-gray-100 pb-5">
                        <div class="flex h-12 w-12 items-center justify-center bg-[#FFF1F3] text-[#C8102E]">
                            <iconify-icon icon="mdi:account-edit-outline" class="fs-4"></iconify-icon>
                        </div>
                        <div>
                            <h3 class="text-xl font-black uppercase text-[#8A1022]">Informasi Profil</h3>
                            <p class="mt-1 text-sm font-medium text-gray-500">Perbarui nama dan email akun.</p>
                        </div>
                    </div>
                    @include('profile.partials.update-profile-information-form')
                </section>

                <section class="bg-white p-6 shadow-sm lg:p-8">
                    <div class="mb-6 flex items-start gap-4 border-b border-gray-100 pb-5">
                        <div class="flex h-12 w-12 items-center justify-center bg-blue-50 text-blue-700">
                            <iconify-icon icon="mdi:lock-reset" class="fs-4"></iconify-icon>
                        </div>
                        <div>
                            <h3 class="text-xl font-black uppercase text-[#8A1022]">Keamanan Akun</h3>
                            <p class="mt-1 text-sm font-medium text-gray-500">Gunakan password yang kuat dan aman.</p>
                        </div>
                    </div>
                    @include('profile.partials.update-password-form')
                </section>
            </div>

            @if ($isCustomer)
                <div id="address-form" class="bg-white p-6 shadow-sm lg:p-8">
                    <div class="mb-6 flex items-start gap-4 border-b border-gray-100 pb-5">
                        <div class="flex h-12 w-12 items-center justify-center bg-emerald-50 text-emerald-700">
                            <iconify-icon icon="mdi:map-marker-outline" class="fs-4"></iconify-icon>
                        </div>
                        <div>
                            <h3 class="text-xl font-black uppercase text-[#8A1022]">Alamat Pengiriman</h3>
                            <p class="mt-1 text-sm font-medium text-gray-500">Kelola alamat untuk checkout pelanggan.
                            </p>
                        </div>
                    </div>
                    @include('profile.partials.user-addresses-form')
                </div>

                <div class="bg-white p-6 shadow-sm lg:p-8">
                    <div class="mb-6 flex items-start gap-4 border-b border-gray-100 pb-5">
                        <div class="flex h-12 w-12 items-center justify-center bg-yellow-50 text-yellow-700">
                            <iconify-icon icon="mdi:heart-outline" class="fs-4"></iconify-icon>
                        </div>
                        <div>
                            <h3 class="text-xl font-black uppercase text-[#8A1022]">Wishlist Produk</h3>
                            <p class="mt-1 text-sm font-medium text-gray-500">Produk yang Anda tandai dengan love akan
                                muncul di sini.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @forelse ($wishlistItems as $wishlist)
                            @if ($wishlist->product)
                                <div class="border border-gray-200 bg-white">
                                    <a href="{{ route('pelanggan.products.show', $wishlist->product) }}"
                                        class="block h-40 overflow-hidden bg-gray-100">
                                        @if ($wishlist->product->images->first())
                                            <img src="{{ asset('storage/' . $wishlist->product->images->first()->image_path) }}"
                                                alt="{{ $wishlist->product->name }}" class="h-full w-full object-cover">
                                        @endif
                                    </a>
                                    <div class="p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            {{ $wishlist->product->category?->name ?? 'Produk' }}
                                        </p>
                                        <a href="{{ route('pelanggan.products.show', $wishlist->product) }}"
                                            class="mt-2 block text-sm font-bold text-gray-900 hover:text-red-700">
                                            {{ $wishlist->product->name }}
                                        </a>
                                        <p class="mt-2 text-sm font-bold text-red-700">
                                            Rp {{ number_format($wishlist->product->price, 0, ',', '.') }}
                                        </p>
                                        <form action="{{ route('pelanggan.wishlist.toggle', $wishlist->product) }}"
                                            method="POST" class="mt-4">
                                            @csrf
                                            <button type="submit"
                                                class="text-xs font-bold uppercase tracking-wide text-red-600 hover:text-red-800">
                                                Hapus Wishlist
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <div
                                class="col-span-full border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500">
                                Belum ada produk wishlist.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white p-6 shadow-sm lg:p-8">
                    <div class="max-w-xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
