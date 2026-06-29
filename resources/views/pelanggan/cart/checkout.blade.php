@php
    $selectedAddressId = (string) old('shipping_address_id', optional($addresses->firstWhere('is_main', true))->id);
    $addressOptions = $addresses->map(fn ($address) => [
        'id' => (string) $address->id,
        'label' => $address->label,
        'receiver_name' => $address->receiver_name,
        'receiver_phone' => $address->receiver_phone,
        'full_address' => $address->full_address,
        'province' => $address->province_display_name,
        'city' => $address->city_display_name,
        'district' => $address->district_display_name,
        'village' => $address->village_display_name,
        'postal_code' => $address->postal_code,
        'region_source' => $address->region_source,
        'district_id' => (string) $address->district_id,
        'is_main' => $address->is_main,
        'summary' => $address->region_summary,
    ])->values();
@endphp

<x-pelanggan-layout>
    <section class="bg-[#071d33] px-8 py-14 text-white">
        <div class="mx-auto max-w-[1290px]">
            <p class="text-xs font-black uppercase tracking-[.24em] text-[#c8d8ee]">Pesanan Detail</p>
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

            <form
                action="{{ route('pelanggan.cart.checkout.process') }}"
                method="POST"
                class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_420px]"
                x-data="{
                    addresses: @js($addressOptions),
                    hasPaymentMethods: @js($paymentMethods->isNotEmpty()),
                    subtotalAfterDiscount: @js((float) $discountSummary['subtotal_after_discount']),
                    selectedAddressId: @js($selectedAddressId),
                    shippingNama: '',
                    shippingTelepon: '',
                    shippingAddress: '',
                    shippingProvince: '',
                    shippingCity: '',
                    shippingDistrict: '',
                    shippingVillage: '',
                    shippingPostalCode: '',
                    shippingQuoteUrl: @js(route('pelanggan.shipping.domestic-cost')),
                    csrfToken: @js(csrf_token()),
                    shippingOptions: [],
                    selectedShippingQuoteToken: @js(old('shipping_quote_token')),
                    shippingFallback: @js(old('shipping_fallback')),
                    shippingLoading: false,
                    shippingError: '',
                    shippingMeta: null,
                    async initCheckout() {
                        this.applySelectedAddress();
                        await this.refreshShippingOptions();
                    },
                    selectedAddress() {
                        return this.addresses.find((address) => address.id === this.selectedAddressId);
                    },
                    addressSelectionChanged() {
                        this.applySelectedAddress();
                        this.refreshShippingOptions();
                    },
                    applySelectedAddress() {
                        const address = this.selectedAddress();

                        if (! address) {
                            this.shippingNama = '';
                            this.shippingTelepon = '';
                            this.shippingAddress = '';
                            this.shippingProvince = '';
                            this.shippingCity = '';
                            this.shippingDistrict = '';
                            this.shippingVillage = '';
                            this.shippingPostalCode = '';
                            return;
                        }

                        this.shippingNama = address.receiver_name || '';
                        this.shippingTelepon = address.receiver_phone || '';
                        this.shippingAddress = address.full_address || '';
                        this.shippingProvince = address.province || '';
                        this.shippingCity = address.city || '';
                        this.shippingDistrict = address.district || '';
                        this.shippingVillage = address.village || '';
                        this.shippingPostalCode = address.postal_code || '';
                    },
                    shippingDestinationReady() {
                        return Boolean(this.selectedAddressId);
                    },
                    selectedShippingOption() {
                        return this.shippingOptions.find((option) => option.quote_token === this.selectedShippingQuoteToken);
                    },
                    clearShippingQuote() {
                        this.shippingOptions = [];
                        this.selectedShippingQuoteToken = '';
                        this.shippingFallback = '';
                        this.shippingLoading = false;
                        this.shippingError = '';
                        this.shippingMeta = null;
                    },
                    async refreshShippingOptions() {
                        this.shippingOptions = [];
                        this.selectedShippingQuoteToken = '';
                        this.shippingFallback = '';
                        this.shippingError = '';
                        this.shippingMeta = null;

                        if (! this.shippingDestinationReady()) {
                            return;
                        }

                        this.shippingLoading = true;

                        try {
                            const response = await fetch(this.shippingQuoteUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': this.csrfToken,
                                },
                                body: JSON.stringify({
                                    shipping_address_id: this.selectedAddressId || null,
                                }),
                            });
                            const payload = await response.json().catch(() => null);

                            if (! response.ok) {
                                throw new Error(payload?.message || 'Gagal menghitung ongkos kirim.');
                            }

                            this.shippingOptions = Array.isArray(payload?.data) ? payload.data : [];
                            this.shippingMeta = payload?.meta || null;

                            if (! this.shippingOptions.length) {
                                this.shippingError = 'Tidak ada layanan pengiriman tersedia untuk alamat ini.';
                            }
                        } catch (error) {
                            this.shippingError = error.message || 'Gagal menghitung ongkos kirim.';
                        } finally {
                            this.shippingLoading = false;
                        }
                    },
                    useAdminFallback() {
                        this.selectedShippingQuoteToken = '';
                        this.shippingFallback = 'admin_manual';
                    },
                    formatRupiah(value) {
                        return new Intl.NumberFormat('id-ID').format(Number(value || 0));
                    },
                    canSubmit() {
                        if (! this.hasPaymentMethods) {
                            return false;
                        }

                        if (this.shippingLoading) {
                            return false;
                        }

                        return Boolean(this.selectedAddressId) && (Boolean(this.selectedShippingQuoteToken) || this.shippingFallback === 'admin_manual');
                    },
                    shippingCostLabel() {
                        const option = this.selectedShippingOption();

                        if (option) {
                            return `Rp ${this.formatRupiah(option.cost)}`;
                        }

                        if (this.shippingFallback === 'admin_manual') {
                            return 'Menunggu konfirmasi admin';
                        }

                        if (this.shippingLoading) {
                            return 'Memuat ongkir...';
                        }

                        if (! this.shippingDestinationReady()) {
                            return 'Lengkapi alamat';
                        }

                        return 'Pilih layanan';
                    },
                    totalPaymentLabel() {
                        const option = this.selectedShippingOption();
                        const total = this.subtotalAfterDiscount + Number(option?.cost || 0);

                        return this.formatRupiah(total);
                    },
                    totalPaymentTitle() {
                        return this.shippingFallback === 'admin_manual' || ! this.selectedShippingOption()
                            ? 'Total sementara'
                            : 'Total pembayaran';
                    },
                }"
                x-init="initCheckout()"
            >
                @csrf
                <input type="hidden" name="shipping_quote_token" x-model="selectedShippingQuoteToken">
                <input type="hidden" name="shipping_fallback" x-model="shippingFallback">

                <div class="space-y-6">
                    <div class="bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-black uppercase text-[#10233d]">Detail Pengiriman</h2>

                        <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label for="shipping_address_id" class="text-sm font-bold text-[#10233d]">Pilih alamat tersimpan</label>
                                <select id="shipping_address_id" name="shipping_address_id" x-model="selectedAddressId" x-on:change="addressSelectionChanged()" required
                                    class="mt-2 w-full border-[#d8e2f0] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                                    <option value="">Pilih alamat</option>
                                    <template x-for="address in addresses" :key="address.id">
                                        <option :value="address.id" x-text="`${address.label}${address.is_main ? ' (Utama)' : ''} - ${address.summary}`"></option>
                                    </template>
                                </select>
                                <x-input-error :messages="$errors->get('shipping_address_id')" class="mt-2" />
                                @if ($addresses->isEmpty())
                                    <p class="mt-2 text-xs font-semibold text-[#657891]">Belum ada alamat tersimpan di profil. Tambahkan alamat sebelum checkout.</p>
                                @endif
                                @unless ($hasRajaOngkirConfig)
                                    <p class="mt-2 text-xs font-semibold text-red-700">API key RajaOngkir belum dikonfigurasi. Ongkos kirim otomatis belum dapat dihitung.</p>
                                @endunless
                            </div>

                            <div>
                                <label for="shipping_name" class="text-sm font-bold text-[#10233d]">Nama penerima</label>
                                <input id="shipping_name" type="text" x-model="shippingNama" readonly
                                    class="mt-2 w-full border-[#d8e2f0] bg-[#f7faff] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                            </div>

                            <div>
                                <label for="shipping_phone" class="text-sm font-bold text-[#10233d]">Nomor telepon</label>
                                <input id="shipping_phone" type="text" x-model="shippingTelepon" readonly
                                    class="mt-2 w-full border-[#d8e2f0] bg-[#f7faff] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                            </div>

                            <div class="md:col-span-2">
                                <label for="shipping_address" class="text-sm font-bold text-[#10233d]">Alamat lengkap</label>
                                <textarea id="shipping_address" rows="4" x-model="shippingAddress" readonly
                                    class="mt-2 w-full border-[#d8e2f0] bg-[#f7faff] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]"></textarea>
                            </div>

                            <div>
                                <label for="shipping_province" class="text-sm font-bold text-[#10233d]">Provinsi</label>
                                <input id="shipping_province" type="text" x-model="shippingProvince" readonly
                                    class="mt-2 w-full border-[#d8e2f0] bg-[#f7faff] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                            </div>

                            <div>
                                <label for="shipping_city" class="text-sm font-bold text-[#10233d]">Kota/Kabupaten</label>
                                <input id="shipping_city" type="text" x-model="shippingCity" readonly
                                    class="mt-2 w-full border-[#d8e2f0] bg-[#f7faff] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                            </div>

                            <div>
                                <label for="shipping_district" class="text-sm font-bold text-[#10233d]">Kecamatan</label>
                                <input id="shipping_district" type="text" x-model="shippingDistrict" readonly
                                    class="mt-2 w-full border-[#d8e2f0] bg-[#f7faff] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                            </div>

                            <div>
                                <label for="shipping_village" class="text-sm font-bold text-[#10233d]">Desa/Kelurahan</label>
                                <input id="shipping_village" type="text" x-model="shippingVillage" readonly
                                    class="mt-2 w-full border-[#d8e2f0] bg-[#f7faff] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                            </div>

                            <div>
                                <label for="shipping_postal_code" class="text-sm font-bold text-[#10233d]">Kode pos</label>
                                <input id="shipping_postal_code" type="text" x-model="shippingPostalCode" readonly
                                    class="mt-2 w-full border-[#d8e2f0] bg-[#f7faff] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
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
                        <h2 class="text-xl font-black uppercase text-[#10233d]">Layanan Pengiriman</h2>
                        <x-input-error :messages="$errors->get('shipping_quote_token')" class="mt-3" />

                        <div class="mt-5 space-y-4">
                            <div x-show="! shippingDestinationReady()" class="border border-[#d8e2f0] bg-[#f7faff] p-4 text-sm font-semibold text-[#657891]">
                                Lengkapi alamat pengiriman untuk melihat layanan kurir.
                            </div>

                            <div x-show="shippingLoading" x-cloak class="border border-[#d8e2f0] bg-[#f7faff] p-4 text-sm font-semibold text-[#10233d]">
                                Memuat ongkos kirim...
                            </div>

                            <div x-show="shippingError" x-cloak class="border border-orange-200 bg-orange-50 p-4 text-sm font-semibold leading-6 text-orange-800">
                                <p x-text="shippingError"></p>
                                <button type="button" x-on:click="useAdminFallback()"
                                    class="mt-3 inline-flex h-10 items-center justify-center bg-[#10233d] px-4 text-xs font-black uppercase tracking-[.12em] text-white hover:bg-[#071d33]">
                                    Konfirmasi Ongkir Admin
                                </button>
                            </div>

                            <div x-show="shippingFallback === 'admin_manual'" x-cloak class="border border-orange-200 bg-orange-50 p-4 text-sm font-semibold leading-6 text-orange-800">
                                Ongkos kirim akan dikonfirmasi admin sebelum pembayaran.
                            </div>

                            <div x-show="shippingOptions.length" x-cloak class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <template x-for="option in shippingOptions" :key="option.quote_token">
                                    <label class="block cursor-pointer border border-[#d8e2f0] p-4 transition hover:border-[#c8102e]"
                                        x-bind:class="selectedShippingQuoteToken === option.quote_token ? 'border-[#c8102e] bg-[#fff7f8]' : 'bg-white'">
                                        <div class="flex items-start gap-3">
                                            <input type="radio" name="shipping_quote_choice" x-model="selectedShippingQuoteToken" x-bind:value="option.quote_token" x-on:change="shippingFallback = ''"
                                                class="mt-1 border-[#d8e2f0] text-[#c8102e] focus:ring-[#c8102e]">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div>
                                                        <p class="font-black text-[#10233d]" x-text="option.courier_name"></p>
                                                        <p class="mt-1 text-xs font-black uppercase tracking-[.12em] text-[#657891]" x-text="option.service"></p>
                                                    </div>
                                                    <p class="shrink-0 font-black text-[#c8102e]">Rp <span x-text="formatRupiah(option.cost)"></span></p>
                                                </div>
                                                <p class="mt-2 text-sm text-[#657891]" x-text="option.description"></p>
                                                <p class="mt-2 text-xs font-semibold text-[#10233d]" x-show="option.etd">Estimasi <span x-text="option.etd"></span></p>
                                            </div>
                                        </div>
                                    </label>
                                </template>
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
                        @if ($discountSummary['promotion'])
                            <div class="flex items-start justify-between gap-4">
                                <span class="text-[#657891]">Promosi</span>
                                <span class="text-right font-black text-[#10233d]">
                                    {{ $discountSummary['promotion']->name }}
                                    @if ($discountSummary['promotion']->code)
                                        <span class="block text-xs font-semibold text-[#657891]">{{ $discountSummary['promotion']->code }}</span>
                                    @endif
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#657891]">Diskon</span>
                                <span class="font-black text-green-700">-Rp {{ number_format($discountSummary['discount_amount'], 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#657891]">Subtotal setelah diskon</span>
                                <span class="font-black text-[#10233d]">Rp {{ number_format($discountSummary['subtotal_after_discount'], 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="flex items-start justify-between gap-4">
                            <span class="text-[#657891]">Ongkos kirim</span>
                            <span class="text-right font-black text-[#10233d]" x-text="shippingCostLabel()"></span>
                        </div>
                        <div class="flex justify-between border-t border-[#e8eef7] pt-4">
                            <span class="text-[#657891]" x-text="totalPaymentTitle()"></span>
                            <span class="font-black text-[#c8102e]">Rp <span x-text="totalPaymentLabel()"></span></span>
                        </div>
                        <div class="border-l-4 border-orange-400 bg-orange-50 px-4 py-3 text-xs font-semibold leading-5 text-orange-800" x-show="shippingFallback === 'admin_manual'">
                            Ongkos kirim akan dikonfirmasi admin. Anda dapat membayar setelah total pembayaran final.
                        </div>
                        <div class="border-l-4 border-green-400 bg-green-50 px-4 py-3 text-xs font-semibold leading-5 text-green-800" x-show="selectedShippingOption()">
                            Total pembayaran sudah termasuk layanan pengiriman yang dipilih.
                        </div>
                    </div>

                    <button type="submit" x-bind:disabled="! canSubmit()" @disabled($paymentMethods->isEmpty())
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
