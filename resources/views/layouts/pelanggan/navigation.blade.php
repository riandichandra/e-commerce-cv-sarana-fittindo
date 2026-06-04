<nav x-data="{ open: false, profileOpen: false }" class="sticky top-0 z-50 h-[50px] border-b border-[#f2c8d0] bg-white/95 backdrop-blur">
    <div class="mx-auto flex h-full max-w-[1365px] items-center px-8">
        <a href="{{ route('dashboard') }}"
            class="mr-20 text-[13px] font-extrabold uppercase tracking-wide text-[#163f8f]">
            SARANA FITTINDO PALEMBANG
        </a>

        <div class="hidden h-full flex-1 items-center justify-center gap-10 md:flex">
            <a href="{{ route('pelanggan.products.index') }}"
                class="flex h-full items-center border-b-2 {{ request()->routeIs('pelanggan.products.*') && !request('category') ? 'border-[#c8102e] text-[#c8102e]' : 'border-transparent text-[#436aa6]' }} text-[12px] font-semibold">
                Semua Produk
            </a>
            <a href="{{ route('pelanggan.products.index', ['category' => 'hpl']) }}"
                class="flex h-full items-center border-b-2 {{ request('category') === 'hpl' ? 'border-[#c8102e] text-[#c8102e]' : 'border-transparent text-[#436aa6]' }} text-[12px] font-semibold">
                HPL
            </a>
            <a href="{{ route('pelanggan.products.index', ['category' => 'plywood']) }}"
                class="flex h-full items-center border-b-2 {{ request('category') === 'plywood' ? 'border-[#c8102e] text-[#c8102e]' : 'border-transparent text-[#436aa6]' }} text-[12px] font-semibold">
                Plywood
            </a>
            <a href="{{ route('pelanggan.products.index', ['category' => 'laminate']) }}"
                class="flex h-full items-center border-b-2 {{ request('category') === 'laminate' ? 'border-[#c8102e] text-[#c8102e]' : 'border-transparent text-[#436aa6]' }} text-[12px] font-semibold">
                Laminate
            </a>
            <a href="{{ route('pelanggan.products.index', ['category' => 'adhesives']) }}"
                class="flex h-full items-center border-b-2 {{ request('category') === 'adhesives' ? 'border-[#c8102e] text-[#c8102e]' : 'border-transparent text-[#436aa6]' }} text-[12px] font-semibold">
                Adhesives
            </a>
            @auth
                <a href="{{ route('pelanggan.orders.index') }}"
                    class="flex h-full items-center border-b-2 {{ request()->routeIs('pelanggan.orders.*') ? 'border-[#c8102e] text-[#c8102e]' : 'border-transparent text-[#436aa6]' }} text-[12px] font-semibold">
                    Pesanan Saya
                </a>
            @endauth
        </div>

        <div class="ml-auto flex items-center gap-4">
            <form action="{{ route('pelanggan.products.index') }}" method="GET" class="hidden sm:block">
                <input id="customer-search" type="search" name="q" value="{{ request('q') }}"
                    placeholder="Cari Material..." onkeydown="if (event.key === 'Enter') this.form.submit();"
                    class="h-8 w-[270px] border-0 bg-[#fff1f3] px-4 text-[11px] text-[#244263] placeholder:text-[#7d91ab] focus:border-[#c8102e] focus:ring-[#c8102e]">
            </form>

            <a href="{{ Auth::check() ? route('pelanggan.cart.index') : route('login') }}"
                class="text-[#c8102e] hover:text-[#9f0d24]" title="Keranjang">
                <iconify-icon icon="mdi:cart-outline" class="fs-5"></iconify-icon>
            </a>

            @auth
                <div class="relative">
                    <button type="button" @click="profileOpen = ! profileOpen" @click.outside="profileOpen = false"
                        class="flex h-7 w-7 items-center justify-center rounded-full border border-[#ef9aaa] text-[#c8102e] hover:bg-[#fff1f3]"
                        aria-label="Open profile menu">
                        <iconify-icon icon="mdi:account-circle-outline" class="fs-5"></iconify-icon>
                    </button>

                    <div x-cloak x-show="profileOpen" x-transition
                        class="absolute right-0 mt-3 w-56 border border-[#f2c8d0] bg-white py-2 shadow-xl">
                        <div class="border-b border-[#edf2f8] px-4 py-3">
                            <p class="text-sm font-bold text-[#0c2037]">{{ Auth::user()->name }}</p>
                            <p class="truncate text-xs text-[#6b7c91]">{{ Auth::user()->email }}</p>
                        </div>
                        <a href="{{ route('profile.edit') }}"
                            class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-[#244263] hover:bg-[#fff1f3]">
                            <iconify-icon icon="mdi:account-edit-outline"></iconify-icon>
                            Profil
                        </a>
                        <a href="{{ route('pelanggan.orders.index') }}"
                            class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-[#244263] hover:bg-[#fff1f3]">
                            <iconify-icon icon="mdi:receipt-text-outline"></iconify-icon>
                            Pesanan Saya
                        </a>
                        <a href="{{ route('pelanggan.orders.history') }}"
                            class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-[#244263] hover:bg-[#fff1f3]">
                            <iconify-icon icon="mdi:history"></iconify-icon>
                            Riwayat Pesanan
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm font-semibold text-[#244263] hover:bg-[#fff1f3]">
                                <iconify-icon icon="mdi:logout"></iconify-icon>
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}"
                    class="flex h-7 w-7 items-center justify-center rounded-full border border-[#ef9aaa] text-[#c8102e] hover:bg-[#fff1f3]"
                    aria-label="Log in">
                    <iconify-icon icon="mdi:account-circle-outline" class="fs-5"></iconify-icon>
                </a>
            @endauth

            <button type="button" @click="open = ! open" class="text-[#c8102e] md:hidden" aria-label="Open menu">
                <iconify-icon icon="mdi:menu" class="fs-5"></iconify-icon>
            </button>
        </div>
    </div>

    <div x-cloak x-show="open" x-transition class="border-b border-[#f2c8d0] bg-white px-8 py-4 md:hidden">
        <form action="{{ route('pelanggan.products.index') }}" method="GET">
            <input id="customer-mobile-search" type="search" name="q" value="{{ request('q') }}"
                placeholder="Cari Material..." onkeydown="if (event.key === 'Enter') this.form.submit();"
                class="mb-4 h-9 w-full border-0 bg-[#fff1f3] px-4 text-sm text-[#244263] placeholder:text-[#7d91ab] focus:border-[#c8102e] focus:ring-[#c8102e]">
        </form>
        <div class="flex flex-col gap-3 text-sm font-semibold text-[#436aa6]">
            <a href="{{ route('pelanggan.products.index') }}">Semua Produk</a>
            <a href="{{ route('pelanggan.products.index', ['category' => 'hpl']) }}">HPL</a>
            <a href="{{ route('pelanggan.products.index', ['category' => 'plywood']) }}">Plywood</a>
            <a href="{{ route('pelanggan.products.index', ['category' => 'laminate']) }}">Laminate</a>
            <a href="{{ route('pelanggan.products.index', ['category' => 'adhesives']) }}">Adhesives</a>
            @auth
                <a href="{{ route('pelanggan.orders.index') }}"
                    class="{{ request()->routeIs('pelanggan.orders.index') ? 'text-[#c8102e]' : '' }}">Pesanan Saya</a>
                <a href="{{ route('pelanggan.orders.history') }}"
                    class="{{ request()->routeIs('pelanggan.orders.history') ? 'text-[#c8102e]' : '' }}">Riwayat
                    Pesanan</a>
            @endauth
        </div>
    </div>
</nav>
