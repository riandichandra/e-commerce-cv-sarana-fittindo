<x-pelanggan-layout>
    <section class="bg-[#071d33] px-8 py-14 text-white">
        <div class="mx-auto max-w-[1290px]">
            <p class="text-xs font-black uppercase tracking-[.24em] text-[#c8d8ee]">Customer Cart</p>
            <h1 class="mt-4 text-4xl font-black uppercase tracking-[-.03em]">Keranjang Belanja</h1>
        </div>
    </section>

    <section class="bg-[#f7faff] px-8 py-14">
        <div class="mx-auto max-w-[1290px]">
            @if (session('success') || session('error'))
                <div class="mb-6 border-l-4 {{ session('success') ? 'border-green-500 bg-green-50 text-green-700' : 'border-red-500 bg-red-50 text-red-700' }} px-4 py-3 text-sm font-semibold">
                    {{ session('success') ?? session('error') }}
                </div>
            @endif

            @if ($cart->items->count())
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_360px]">
                    <div class="space-y-4">
                        @foreach ($cart->items as $item)
                            <div class="grid grid-cols-1 gap-5 bg-white p-5 shadow-sm md:grid-cols-[120px_1fr_auto] md:items-center">
                                <a href="{{ route('pelanggan.products.show', $item->product) }}" class="block h-28 overflow-hidden bg-[#dce8f7]">
                                    @if ($item->product->images->first())
                                        <img src="{{ asset('storage/' . $item->product->images->first()->image_path) }}" alt="{{ $item->product->name }}" class="h-full w-full object-cover">
                                    @endif
                                </a>

                                <div>
                                    <p class="text-xs font-black uppercase tracking-[.16em] text-[#7b8799]">{{ $item->product->category?->name ?? 'Product' }}</p>
                                    <a href="{{ route('pelanggan.products.show', $item->product) }}" class="mt-2 block text-lg font-black text-[#10233d] hover:text-[#c8102e]">
                                        {{ $item->product->name }}
                                    </a>
                                    <p class="mt-2 text-sm font-bold text-[#c8102e]">Rp {{ number_format($item->product->price, 0, ',', '.') }}</p>
                                </div>

                                <div class="flex items-center gap-3 md:justify-end">
                                    <form action="{{ route('pelanggan.cart.update', $item) }}" method="POST" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="number" name="quantity" value="{{ $item->quantity }}" min="1"
                                            class="h-10 w-20 border-[#f2c8d0] text-center text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                                        <button type="submit" class="h-10 bg-[#c8102e] px-4 text-xs font-black uppercase tracking-[.12em] text-white hover:bg-[#9f0d24]">
                                            Update
                                        </button>
                                    </form>

                                    <form action="{{ route('pelanggan.cart.destroy', $item) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="flex h-10 w-10 items-center justify-center bg-red-50 text-red-600 hover:bg-red-100" aria-label="Remove item">
                                            <iconify-icon icon="mdi:trash-can-outline"></iconify-icon>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <aside class="h-fit bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-black uppercase text-[#10233d]">Ringkasan</h2>
                        <div class="mt-6 space-y-4 text-sm">
                            <div class="flex justify-between">
                                <span class="text-[#657891]">Total item</span>
                                <span class="font-black text-[#10233d]">{{ $cart->total_items }}</span>
                            </div>
                            <div class="flex justify-between border-t border-[#f2c8d0] pt-4">
                                <span class="text-[#657891]">Subtotal</span>
                                <span class="font-black text-[#c8102e]">Rp {{ number_format($cart->subtotal, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        <a href="{{ route('pelanggan.cart.checkout') }}" class="mt-7 flex h-11 w-full items-center justify-center bg-[#c8102e] text-xs font-black uppercase tracking-[.16em] text-white hover:bg-[#9f0d24]">
                            Lanjut Belanja
                        </a>
                    </aside>
                </div>
            @else
                <div class="bg-white px-6 py-16 text-center">
                    <iconify-icon icon="mdi:cart-outline" class="text-6xl text-[#9db2cf]"></iconify-icon>
                    <h2 class="mt-4 text-2xl font-black text-[#10233d]">Keranjang masih kosong</h2>
                    <p class="mt-2 text-sm text-[#657891]">Tambahkan produk material pilihan Anda terlebih dahulu.</p>
                    <a href="{{ route('pelanggan.products.index') }}" class="mt-7 inline-flex h-11 items-center bg-[#c8102e] px-6 text-xs font-black uppercase tracking-[.16em] text-white hover:bg-[#9f0d24]">
                        Lihat Produk
                    </a>
                </div>
            @endif
        </div>
    </section>
</x-pelanggan-layout>
