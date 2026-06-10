@php
    $selectedAddressId = (string) old('shipping_address_id', optional($addresses->firstWhere('is_main', true))->id);
    $selectedProvinceId = (string) old('shipping_province_id');
    $selectedCityId = (string) old('shipping_city_id');
    $selectedDistrictId = (string) old('shipping_district_id');
    $selectedVillageId = (string) old('shipping_village_id');
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
                    rajaOngkirConfigured: @js($hasRajaOngkirConfig),
                    subtotalAfterDiscount: @js((float) $discountSummary['subtotal_after_discount']),
                    selectedAddressId: @js($selectedAddressId),
                    shippingNama: @js(old('shipping_name', auth()->user()->name)),
                    shippingTelepon: @js(old('shipping_phone', auth()->user()->phone)),
                    shippingAddress: @js(old('shipping_address')),
                    shippingProvince: @js(old('shipping_province')),
                    shippingCity: @js(old('shipping_city')),
                    shippingDistrict: @js(old('shipping_district')),
                    shippingVillage: @js(old('shipping_village')),
                    shippingPostalCode: @js(old('shipping_postal_code')),
                    shippingProvinceId: @js($selectedProvinceId),
                    shippingCityId: @js($selectedCityId),
                    shippingDistrictId: @js($selectedDistrictId),
                    shippingVillageId: @js($selectedVillageId),
                    provinces: [],
                    regencies: [],
                    districts: [],
                    villages: [],
                    regionError: '',
                    loadingProvinces: false,
                    loadingRegencies: false,
                    loadingDistricts: false,
                    loadingVillages: false,
                    shippingQuoteUrl: @js(route('pelanggan.shipping.domestic-cost')),
                    csrfToken: @js(csrf_token()),
                    shippingOptions: [],
                    selectedShippingQuoteToken: @js(old('shipping_quote_token')),
                    shippingFallback: @js(old('shipping_fallback')),
                    shippingLoading: false,
                    shippingError: '',
                    shippingMeta: null,
                    regionUrls: {
                        provinces: @js(route('regions.provinces.index')),
                        regencies: @js(route('regions.regencies.index', ['province' => '__province__'])),
                        districts: @js(route('regions.districts.index', ['regency' => '__regency__'])),
                        villages: @js(route('regions.villages.index', ['district' => '__district__'])),
                    },
                    async initCheckout() {
                        this.applySelectedAddress(false);
                        await this.initRegions();
                        await this.refreshShippingOptions();
                    },
                    async initRegions() {
                        if (! this.rajaOngkirConfigured) {
                            this.regionError = 'API key RajaOngkir belum dikonfigurasi.';
                            return;
                        }

                        await this.loadProvinces();

                        if (this.shippingProvinceId) {
                            await this.loadRegencies();
                        }

                        if (this.shippingCityId) {
                            await this.loadDistricts();
                        }

                        if (this.shippingDistrictId) {
                            await this.loadVillages();
                        }

                        this.syncRegionNames();
                    },
                    selectedAddress() {
                        return this.addresses.find((address) => address.id === this.selectedAddressId);
                    },
                    manualShippingActive() {
                        return ! Boolean(this.selectedAddressId);
                    },
                    hasManualShipping() {
                        return Boolean(this.shippingAddress || this.shippingProvince || this.shippingCity || this.shippingDistrict || this.shippingVillage || this.shippingPostalCode || this.shippingProvinceId || this.shippingCityId || this.shippingDistrictId || this.shippingVillageId);
                    },
                    addressSelectionChanged() {
                        if (this.selectedAddressId) {
                            this.applySelectedAddress(true);
                            this.refreshShippingOptions();
                            return;
                        }

                        this.shippingAddress = '';
                        this.clearRegionSelection();
                        this.clearShippingQuote();
                    },
                    applySelectedAddress(force = true) {
                        const address = this.selectedAddress();

                        if (! address || (! force && this.hasManualShipping())) {
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
                        this.clearRegionIds();
                    },
                    clearSelectedAddress() {
                        if (this.selectedAddressId) {
                            this.selectedAddressId = '';
                            this.clearShippingQuote();
                        }
                    },
                    clearRegionIds() {
                        this.shippingProvinceId = '';
                        this.shippingCityId = '';
                        this.shippingDistrictId = '';
                        this.shippingVillageId = '';
                        this.regencies = [];
                        this.districts = [];
                        this.villages = [];
                    },
                    clearRegionSelection() {
                        this.clearRegionIds();
                        this.shippingProvince = '';
                        this.shippingCity = '';
                        this.shippingDistrict = '';
                        this.shippingVillage = '';
                        this.shippingPostalCode = '';
                    },
                    async fetchRegions(url) {
                        this.regionError = '';

                        try {
                            const response = await fetch(url, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });
                            const payload = await response.json().catch(() => null);

                            if (! response.ok) {
                                throw new Error(payload?.message || 'Gagal memuat data wilayah.');
                            }

                            return Array.isArray(payload) ? payload : [];
                        } catch (error) {
                            this.regionError = error.message || 'Gagal memuat data wilayah.';
                            return [];
                        }
                    },
                    regionById(items, id) {
                        return items.find((item) => String(item.id) === String(id));
                    },
                    setRegionName(items, id, target) {
                        const region = this.regionById(items, id);

                        if (region) {
                            this[target] = region.name || '';
                        } else if (! id) {
                            this[target] = '';
                        }
                    },
                    syncRegionNames() {
                        this.setRegionName(this.provinces, this.shippingProvinceId, 'shippingProvince');
                        this.setRegionName(this.regencies, this.shippingCityId, 'shippingCity');
                        this.setRegionName(this.districts, this.shippingDistrictId, 'shippingDistrict');
                        this.setRegionName(this.villages, this.shippingVillageId, 'shippingVillage');
                    },
                    async loadProvinces() {
                        this.loadingProvinces = true;
                        this.provinces = await this.fetchRegions(this.regionUrls.provinces);
                        this.loadingProvinces = false;
                        this.setRegionName(this.provinces, this.shippingProvinceId, 'shippingProvince');
                    },
                    async provinceChanged() {
                        this.clearSelectedAddress();
                        this.clearShippingQuote();
                        this.shippingCityId = '';
                        this.shippingDistrictId = '';
                        this.shippingVillageId = '';
                        this.regencies = [];
                        this.districts = [];
                        this.villages = [];
                        this.shippingCity = '';
                        this.shippingDistrict = '';
                        this.shippingVillage = '';
                        this.shippingPostalCode = '';
                        this.setRegionName(this.provinces, this.shippingProvinceId, 'shippingProvince');

                        if (! this.shippingProvinceId) {
                            this.shippingProvince = '';
                            return;
                        }

                        await this.loadRegencies();
                    },
                    async loadRegencies() {
                        this.loadingRegencies = true;
                        this.regencies = await this.fetchRegions(this.regionUrls.regencies.replace('__province__', this.shippingProvinceId));
                        this.loadingRegencies = false;
                        this.setRegionName(this.regencies, this.shippingCityId, 'shippingCity');
                    },
                    async regencyChanged() {
                        this.clearSelectedAddress();
                        this.clearShippingQuote();
                        this.shippingDistrictId = '';
                        this.shippingVillageId = '';
                        this.districts = [];
                        this.villages = [];
                        this.shippingDistrict = '';
                        this.shippingVillage = '';
                        this.shippingPostalCode = '';
                        this.setRegionName(this.regencies, this.shippingCityId, 'shippingCity');

                        if (! this.shippingCityId) {
                            this.shippingCity = '';
                            return;
                        }

                        await this.loadDistricts();
                    },
                    async loadDistricts() {
                        this.loadingDistricts = true;
                        this.districts = await this.fetchRegions(this.regionUrls.districts.replace('__regency__', this.shippingCityId));
                        this.loadingDistricts = false;
                        this.setRegionName(this.districts, this.shippingDistrictId, 'shippingDistrict');
                    },
                    async districtChanged() {
                        this.clearSelectedAddress();
                        this.clearShippingQuote();
                        this.shippingVillageId = '';
                        this.villages = [];
                        this.shippingVillage = '';
                        this.shippingPostalCode = '';
                        this.setRegionName(this.districts, this.shippingDistrictId, 'shippingDistrict');

                        if (! this.shippingDistrictId) {
                            this.shippingDistrict = '';
                            return;
                        }

                        await this.loadVillages();
                    },
                    async loadVillages() {
                        this.loadingVillages = true;
                        this.villages = await this.fetchRegions(this.regionUrls.villages.replace('__district__', this.shippingDistrictId));
                        this.loadingVillages = false;
                        this.setRegionName(this.villages, this.shippingVillageId, 'shippingVillage');
                    },
                    villageChanged() {
                        this.clearSelectedAddress();
                        const village = this.regionById(this.villages, this.shippingVillageId);
                        this.shippingVillage = village?.name || '';
                        this.shippingPostalCode = village?.postal_code || '';
                        this.refreshShippingOptions();
                    },
                    isRegionBusy() {
                        return this.loadingProvinces || this.loadingRegencies || this.loadingDistricts || this.loadingVillages;
                    },
                    manualRegionReady() {
                        return Boolean(this.shippingProvinceId && this.shippingCityId && this.shippingDistrictId && this.shippingVillageId);
                    },
                    shippingDestinationReady() {
                        return Boolean(this.selectedAddressId) || this.manualRegionReady();
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
                                    shipping_province_id: this.shippingProvinceId || null,
                                    shipping_city_id: this.shippingCityId || null,
                                    shipping_district_id: this.shippingDistrictId || null,
                                    shipping_village_id: this.shippingVillageId || null,
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

                        const addressReady = this.selectedAddressId
                            ? true
                            : this.rajaOngkirConfigured && this.manualRegionReady() && ! this.isRegionBusy();

                        return addressReady && (Boolean(this.selectedShippingQuoteToken) || this.shippingFallback === 'admin_manual');
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
                                <select id="shipping_address_id" name="shipping_address_id" x-model="selectedAddressId" x-on:change="addressSelectionChanged()"
                                    class="mt-2 w-full border-[#d8e2f0] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                                    <option value="">Isi alamat manual</option>
                                    <template x-for="address in addresses" :key="address.id">
                                        <option :value="address.id" x-text="`${address.label}${address.is_main ? ' (Utama)' : ''} - ${address.summary}`"></option>
                                    </template>
                                </select>
                                <x-input-error :messages="$errors->get('shipping_address_id')" class="mt-2" />
                                @if ($addresses->isEmpty())
                                    <p class="mt-2 text-xs font-semibold text-[#657891]">Belum ada alamat tersimpan di profil. Anda tetap bisa mengisi alamat secara manual.</p>
                                @endif
                                @unless ($hasRajaOngkirConfig)
                                    <p class="mt-2 text-xs font-semibold text-red-700">API key RajaOngkir belum dikonfigurasi. Alamat manual belum dapat digunakan sampai variabel RAJAONGKIR_API_KEY diisi.</p>
                                @endunless
                            </div>

                            <div>
                                <label for="shipping_name" class="text-sm font-bold text-[#10233d]">Nama penerima</label>
                                <input id="shipping_name" name="shipping_name" type="text" x-model="shippingNama" x-bind:readonly="Boolean(selectedAddressId)" x-on:input="clearSelectedAddress()"
                                    class="mt-2 w-full border-[#d8e2f0] text-sm focus:border-[#c8102e] focus:ring-[#c8102e] read-only:bg-[#f7faff]">
                                <x-input-error :messages="$errors->get('shipping_name')" class="mt-2" />
                            </div>

                            <div>
                                <label for="shipping_phone" class="text-sm font-bold text-[#10233d]">Nomor telepon</label>
                                <input id="shipping_phone" name="shipping_phone" type="text" x-model="shippingTelepon" x-bind:readonly="Boolean(selectedAddressId)" x-on:input="clearSelectedAddress()"
                                    class="mt-2 w-full border-[#d8e2f0] text-sm focus:border-[#c8102e] focus:ring-[#c8102e] read-only:bg-[#f7faff]">
                                <x-input-error :messages="$errors->get('shipping_phone')" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <label for="shipping_address" class="text-sm font-bold text-[#10233d]">Alamat lengkap</label>
                                <textarea id="shipping_address" name="shipping_address" rows="4" x-model="shippingAddress" x-bind:readonly="Boolean(selectedAddressId)" x-on:input="clearSelectedAddress()"
                                    class="mt-2 w-full border-[#d8e2f0] text-sm focus:border-[#c8102e] focus:ring-[#c8102e] read-only:bg-[#f7faff]"></textarea>
                                <x-input-error :messages="$errors->get('shipping_address')" class="mt-2" />
                            </div>

                            <input type="hidden" name="shipping_province" x-model="shippingProvince">
                            <input type="hidden" name="shipping_city" x-model="shippingCity">
                            <input type="hidden" name="shipping_district" x-model="shippingDistrict">
                            <input type="hidden" name="shipping_village" x-model="shippingVillage">

                            <div x-show="manualShippingActive() && regionError" x-cloak class="border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700 md:col-span-2">
                                <span x-text="regionError"></span>
                            </div>

                            <div>
                                <label for="shipping_province_id" class="text-sm font-bold text-[#10233d]">Provinsi</label>
                                <input type="text" x-show="selectedAddressId" x-cloak x-model="shippingProvince" readonly
                                    class="mt-2 w-full border-[#d8e2f0] bg-[#f7faff] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                                <select id="shipping_province_id" name="shipping_province_id" x-show="! selectedAddressId" x-model="shippingProvinceId" x-on:change="provinceChanged()" x-bind:required="manualShippingActive()" x-bind:disabled="! rajaOngkirConfigured || loadingProvinces"
                                    class="mt-2 w-full border-[#d8e2f0] text-sm focus:border-[#c8102e] focus:ring-[#c8102e] disabled:bg-[#f7faff]">
                                    <option value="" x-text="loadingProvinces ? 'Memuat provinsi...' : 'Pilih provinsi'"></option>
                                    <template x-for="province in provinces" :key="province.id">
                                        <option :value="province.id" x-text="province.name"></option>
                                    </template>
                                </select>
                                <x-input-error :messages="$errors->get('shipping_province_id')" class="mt-2" />
                            </div>

                            <div>
                                <label for="shipping_city_id" class="text-sm font-bold text-[#10233d]">Kota/Kabupaten</label>
                                <input type="text" x-show="selectedAddressId" x-cloak x-model="shippingCity" readonly
                                    class="mt-2 w-full border-[#d8e2f0] bg-[#f7faff] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                                <select id="shipping_city_id" name="shipping_city_id" x-show="! selectedAddressId" x-model="shippingCityId" x-on:change="regencyChanged()" x-bind:required="manualShippingActive()" x-bind:disabled="! rajaOngkirConfigured || ! shippingProvinceId || loadingRegencies"
                                    class="mt-2 w-full border-[#d8e2f0] text-sm focus:border-[#c8102e] focus:ring-[#c8102e] disabled:bg-[#f7faff]">
                                    <option value="" x-text="loadingRegencies ? 'Memuat kabupaten/kota...' : 'Pilih kabupaten/kota'"></option>
                                    <template x-for="regency in regencies" :key="regency.id">
                                        <option :value="regency.id" x-text="regency.name"></option>
                                    </template>
                                </select>
                                <x-input-error :messages="$errors->get('shipping_city_id')" class="mt-2" />
                            </div>

                            <div>
                                <label for="shipping_district_id" class="text-sm font-bold text-[#10233d]">Kecamatan</label>
                                <input type="text" x-show="selectedAddressId" x-cloak x-model="shippingDistrict" readonly
                                    class="mt-2 w-full border-[#d8e2f0] bg-[#f7faff] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                                <select id="shipping_district_id" name="shipping_district_id" x-show="! selectedAddressId" x-model="shippingDistrictId" x-on:change="districtChanged()" x-bind:required="manualShippingActive()" x-bind:disabled="! rajaOngkirConfigured || ! shippingCityId || loadingDistricts"
                                    class="mt-2 w-full border-[#d8e2f0] text-sm focus:border-[#c8102e] focus:ring-[#c8102e] disabled:bg-[#f7faff]">
                                    <option value="" x-text="loadingDistricts ? 'Memuat kecamatan...' : 'Pilih kecamatan'"></option>
                                    <template x-for="district in districts" :key="district.id">
                                        <option :value="district.id" x-text="district.name"></option>
                                    </template>
                                </select>
                                <x-input-error :messages="$errors->get('shipping_district_id')" class="mt-2" />
                            </div>

                            <div>
                                <label for="shipping_village_id" class="text-sm font-bold text-[#10233d]">Desa/Kelurahan</label>
                                <input type="text" x-show="selectedAddressId" x-cloak x-model="shippingVillage" readonly
                                    class="mt-2 w-full border-[#d8e2f0] bg-[#f7faff] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                                <select id="shipping_village_id" name="shipping_village_id" x-show="! selectedAddressId" x-model="shippingVillageId" x-on:change="villageChanged()" x-bind:required="manualShippingActive()" x-bind:disabled="! rajaOngkirConfigured || ! shippingDistrictId || loadingVillages"
                                    class="mt-2 w-full border-[#d8e2f0] text-sm focus:border-[#c8102e] focus:ring-[#c8102e] disabled:bg-[#f7faff]">
                                    <option value="" x-text="loadingVillages ? 'Memuat desa/kelurahan...' : 'Pilih desa/kelurahan'"></option>
                                    <template x-for="village in villages" :key="village.id">
                                        <option :value="village.id" x-text="village.name"></option>
                                    </template>
                                </select>
                                <x-input-error :messages="$errors->get('shipping_village_id')" class="mt-2" />
                            </div>

                            <div>
                                <label for="shipping_postal_code" class="text-sm font-bold text-[#10233d]">Kode pos</label>
                                <input id="shipping_postal_code" name="shipping_postal_code" type="text" x-model="shippingPostalCode" x-bind:readonly="Boolean(selectedAddressId)" x-on:input="clearSelectedAddress()"
                                    class="mt-2 w-full border-[#d8e2f0] text-sm focus:border-[#c8102e] focus:ring-[#c8102e] read-only:bg-[#f7faff]">
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
