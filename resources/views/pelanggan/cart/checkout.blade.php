<x-pelanggan-layout>
    <section class="bg-[#071d33] px-8 py-14 text-white">
        <div class="mx-auto max-w-[1290px]">
            <p class="text-xs font-black uppercase tracking-[.24em] text-[#c8d8ee]">Order Detail</p>
            <h1 class="mt-4 text-4xl font-black uppercase tracking-[-.03em]">Detail Pemesanan</h1>
        </div>
    </section>

    <section class="bg-[#f7faff] px-8 py-14">
        <div class="mx-auto max-w-[1290px]">
            @if (session('success') || session('error'))
                <div class="mb-6 border-l-4 {{ session('success') ? 'border-green-500 bg-green-50 text-green-700' : 'border-red-500 bg-red-50 text-red-700' }} px-4 py-3 text-sm font-semibold">
                    {{ session('success') ?? session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 border-l-4 border-red-500 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                    Periksa kembali detail pemesanan Anda.
                </div>
            @endif

            <form action="{{ route('pelanggan.cart.checkout.process') }}" method="POST" class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_420px]">
                @csrf

                <div class="space-y-6">
                    <div class="bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-black uppercase text-[#10233d]">Detail Pengiriman</h2>

                        <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div>
                                <label for="shipping_name" class="text-sm font-bold text-[#10233d]">Nama penerima</label>
                                <input id="shipping_name" name="shipping_name" type="text" value="{{ old('shipping_name', auth()->user()->name) }}"
                                    class="mt-2 w-full border-[#d8e2f0] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                                <x-input-error :messages="$errors->get('shipping_name')" class="mt-2" />
                            </div>

                            <div>
                                <label for="shipping_phone" class="text-sm font-bold text-[#10233d]">Nomor telepon</label>
                                <input id="shipping_phone" name="shipping_phone" type="text" value="{{ old('shipping_phone', auth()->user()->phone) }}"
                                    class="mt-2 w-full border-[#d8e2f0] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                                <x-input-error :messages="$errors->get('shipping_phone')" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <label for="shipping_address" class="text-sm font-bold text-[#10233d]">Alamat lengkap</label>
                                <textarea id="shipping_address" name="shipping_address" rows="4"
                                    class="mt-2 w-full border-[#d8e2f0] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">{{ old('shipping_address') }}</textarea>
                                <x-input-error :messages="$errors->get('shipping_address')" class="mt-2" />
                            </div>

                            <div>
                                <label for="shipping_province" class="text-sm font-bold text-[#10233d]">Provinsi</label>
                                <input id="shipping_province" name="shipping_province" type="text" value="{{ old('shipping_province') }}"
                                    class="mt-2 w-full border-[#d8e2f0] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                                <x-input-error :messages="$errors->get('shipping_province')" class="mt-2" />
                            </div>

                            <div>
                                <label for="shipping_city" class="text-sm font-bold text-[#10233d]">Kota/Kabupaten</label>
                                <input id="shipping_city" name="shipping_city" type="text" value="{{ old('shipping_city') }}"
                                    class="mt-2 w-full border-[#d8e2f0] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                                <x-input-error :messages="$errors->get('shipping_city')" class="mt-2" />
                            </div>

                            <div>
                                <label for="shipping_district" class="text-sm font-bold text-[#10233d]">Kecamatan</label>
                                <input id="shipping_district" name="shipping_district" type="text" value="{{ old('shipping_district') }}"
                                    class="mt-2 w-full border-[#d8e2f0] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                                <x-input-error :messages="$errors->get('shipping_district')" class="mt-2" />
                            </div>

                            <div>
                                <label for="shipping_village" class="text-sm font-bold text-[#10233d]">Desa/Kelurahan</label>
                                <input id="shipping_village" name="shipping_village" type="text" value="{{ old('shipping_village') }}"
                                    class="mt-2 w-full border-[#d8e2f0] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                                <x-input-error :messages="$errors->get('shipping_village')" class="mt-2" />
                            </div>

                            <div>
                                <label for="shipping_postal_code" class="text-sm font-bold text-[#10233d]">Kode pos</label>
                                <input id="shipping_postal_code" name="shipping_postal_code" type="text" value="{{ old('shipping_postal_code') }}"
                                    class="mt-2 w-full border-[#d8e2f0] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                                <x-input-error :messages="$errors->get('shipping_postal_code')" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <label for="notes" class="text-sm font-bold text-[#10233d]">Catatan pesanan</label>
                                <textarea id="notes" name="notes" rows="3"
                                    class="mt-2 w-full border-[#d8e2f0] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">{{ old('notes') }}</textarea>
                                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-black uppercase text-[#10233d]">Metode Pembayaran</h2>
                        <x-input-error :messages="$errors->get('payment_method_id')" class="mt-3" />

                        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                            @forelse ($paymentMethods as $method)
                                <label class="block cursor-pointer border border-[#d8e2f0] p-4 transition hover:border-[#c8102e]">
                                    <div class="flex items-start gap-3">
                                        <input type="radio" name="payment_method_id" value="{{ $method->id }}" @checked((string) old('payment_method_id') === (string) $method->id)
                                            class="mt-1 border-[#d8e2f0] text-[#c8102e] focus:ring-[#c8102e]">
                                        <div class="min-w-0">
                                            <p class="font-black text-[#10233d]">{{ $method->name }}</p>
                                            <p class="mt-1 text-sm text-[#657891]">{{ $method->bank_name }}</p>
                                            <p class="mt-2 text-sm font-bold text-[#10233d]">{{ $method->account_number }}</p>
                                            <p class="text-xs font-semibold uppercase tracking-[.12em] text-[#7b8799]">{{ $method->account_name }}</p>
                                            @if ($method->instructions)
                                                <p class="mt-3 text-xs leading-5 text-[#657891]">{{ $method->instructions }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @empty
                                <div class="border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700 md:col-span-2">
                                    Belum ada metode pembayaran aktif. Hubungi admin sebelum checkout.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <aside class="h-fit bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-black uppercase text-[#10233d]">Produk Dipesan</h2>

                    <div class="mt-6 space-y-4">
                        @foreach ($cart->items as $item)
                            <div class="grid grid-cols-[72px_1fr] gap-4 border-b border-[#e8eef7] pb-4">
                                <a href="{{ route('pelanggan.products.show', $item->product) }}" class="block h-20 overflow-hidden bg-[#dce8f7]">
                                    @if ($item->product->images->first())
                                        <img src="{{ asset('storage/' . $item->product->images->first()->image_path) }}" alt="{{ $item->product->name }}" class="h-full w-full object-cover">
                                    @endif
                                </a>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-black text-[#10233d]">{{ $item->product->name }}</p>
                                    <p class="mt-1 text-xs text-[#657891]">{{ $item->quantity }} x Rp {{ number_format($item->product->price, 0, ',', '.') }}</p>
                                    <p class="mt-2 text-sm font-black text-[#c8102e]">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

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

                    <button type="submit" @disabled($paymentMethods->isEmpty())
                        class="mt-7 flex h-11 w-full items-center justify-center bg-[#c8102e] text-xs font-black uppercase tracking-[.16em] text-white hover:bg-[#9f0d24] disabled:cursor-not-allowed disabled:bg-gray-400">
                        Checkout
                    </button>
                    <a href="{{ route('pelanggan.cart.index') }}" class="mt-3 flex h-11 w-full items-center justify-center border border-[#d8e2f0] text-xs font-black uppercase tracking-[.16em] text-[#10233d] hover:border-[#c8102e] hover:text-[#c8102e]">
                        Kembali
                    </a>
                </aside>
            </form>
        </div>
    </section>
</x-pelanggan-layout>
