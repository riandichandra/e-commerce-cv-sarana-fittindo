@php
    $storedRegionIsRajaOngkir = $editingAddress?->region_source === 'rajaongkir';
    $selectedProvince = (string) old('province_id', $storedRegionIsRajaOngkir ? $editingAddress?->province_id : '');
    $selectedRegency = (string) old('regency_id', $storedRegionIsRajaOngkir ? $editingAddress?->regency_id : '');
    $selectedDistrict = (string) old('district_id', $storedRegionIsRajaOngkir ? $editingAddress?->district_id : '');
    $selectedVillage = (string) old('village_id', $storedRegionIsRajaOngkir ? $editingAddress?->village_id : '');
    $needsRegionReselect = $editingAddress && !$storedRegionIsRajaOngkir && !session()->hasOldInput('province_id');
    $editingRegionSummary = $editingAddress?->region_summary;
    $addressAlert = match (session('status')) {
        'address-created' => [
            'tone' => 'success',
            'icon' => 'mdi:check-circle-outline',
            'title' => 'Alamat berhasil ditambahkan',
            'message' => 'Alamat baru sudah tersimpan dan siap digunakan saat checkout.',
        ],
        'address-updated' => [
            'tone' => 'success',
            'icon' => 'mdi:check-circle-outline',
            'title' => 'Alamat berhasil diperbarui',
            'message' => 'Perubahan alamat tersimpan dengan baik.',
        ],
        'address-deleted' => [
            'tone' => 'success',
            'icon' => 'mdi:trash-can-check-outline',
            'title' => 'Alamat berhasil dihapus',
            'message' => 'Daftar alamat tersimpan Anda sudah diperbarui.',
        ],
        default => null,
    };
@endphp

<section x-data="{
    deleteModalOpen: false,
    deleteSubmitting: false,
    deleteFormAction: '',
    deleteAddress: null,
    openDeleteModal(address, action) {
        this.deleteAddress = address;
        this.deleteFormAction = action;
        this.deleteSubmitting = false;
        this.deleteModalOpen = true;
        document.body.classList.add('overflow-hidden');
    },
    closeDeleteModal() {
        if (this.deleteSubmitting) {
            return;
        }

        this.deleteModalOpen = false;
        this.deleteAddress = null;
        this.deleteFormAction = '';
        document.body.classList.remove('overflow-hidden');
    },
    submitDeleteForm() {
        this.deleteSubmitting = true;
    },
}" x-on:keydown.escape.window="closeDeleteModal">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Alamat Pengiriman
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Kelola alamat yang dapat digunakan untuk proses checkout.
        </p>
    </header>

    @if ($addressAlert)
        <div class="{{ $addressAlert['tone'] === 'danger' ? 'border-red-200 bg-red-50 text-red-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800' }} mt-5 flex items-start gap-3 border p-4"
            x-data="{ visible: true }" x-show="visible" x-transition.opacity.duration.200ms>
            <div
                class="{{ $addressAlert['tone'] === 'danger' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }} flex h-10 w-10 shrink-0 items-center justify-center">
                <iconify-icon icon="{{ $addressAlert['icon'] }}" class="text-xl"></iconify-icon>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-black">{{ $addressAlert['title'] }}</p>
                <p class="mt-1 text-sm font-medium opacity-90">{{ $addressAlert['message'] }}</p>
            </div>
            <button type="button" class="shrink-0 text-current opacity-70 hover:opacity-100"
                x-on:click="visible = false" aria-label="Tutup notifikasi">
                <iconify-icon icon="mdi:close" class="text-lg"></iconify-icon>
            </button>
        </div>
    @endif

    @unless ($hasRajaOngkirConfig)
        <div class="mt-6 border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
            API key RajaOngkir belum dikonfigurasi. Isi variabel <span class="font-bold">RAJAONGKIR_API_KEY</span> di file
            .env agar data wilayah dapat dimuat.
        </div>
    @endunless

    @if ($needsRegionReselect)
        <div class="mt-6 border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
            Alamat ini masih memakai data wilayah lama: <span class="font-bold">{{ $editingRegionSummary }}</span>.
            Pilih ulang provinsi sampai desa/kelurahan dari RajaOngkir sebelum menyimpan perubahan.
        </div>
    @endif

    <form method="post" id="address-form"
        action="{{ $editingAddress ? route('profile.addresses.update', $editingAddress) : route('profile.addresses.store') }}"
        class="mt-6 space-y-6" x-data="{
            rajaOngkirConfigured: @js($hasRajaOngkirConfig),
            provinces: [],
            regencies: [],
            districts: [],
            villages: [],
            provinceId: @js($selectedProvince),
            regencyId: @js($selectedRegency),
            districtId: @js($selectedDistrict),
            villageId: @js($selectedVillage),
            postalCode: @js(old('postal_code', $editingAddress?->postal_code)),
            regionError: '',
            loadingProvinces: false,
            loadingRegencies: false,
            loadingDistricts: false,
            loadingVillages: false,
            regionUrls: {
                provinces: @js(route('regions.provinces.index')),
                regencies: @js(route('regions.regencies.index', ['province' => '__province__'])),
                districts: @js(route('regions.districts.index', ['regency' => '__regency__'])),
                villages: @js(route('regions.villages.index', ['district' => '__district__'])),
            },
            async initRegions() {
                if (!this.rajaOngkirConfigured) {
                    this.regionError = 'API key RajaOngkir belum dikonfigurasi.';
                    return;
                }
        
                await this.loadProvinces();
        
                if (this.provinceId) {
                    await this.loadRegencies();
                }
        
                if (this.regencyId) {
                    await this.loadDistricts();
                }
        
                if (this.districtId) {
                    await this.loadVillages();
                }
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
        
                    if (!response.ok) {
                        throw new Error(payload?.message || 'Gagal memuat data wilayah.');
                    }
        
                    return Array.isArray(payload) ? payload : [];
                } catch (error) {
                    this.regionError = error.message || 'Gagal memuat data wilayah.';
                    return [];
                }
            },
            async loadProvinces() {
                this.loadingProvinces = true;
                this.provinces = await this.fetchRegions(this.regionUrls.provinces);
                this.loadingProvinces = false;
            },
            async provinceChanged() {
                this.regencyId = '';
                this.districtId = '';
                this.villageId = '';
                this.regencies = [];
                this.districts = [];
                this.villages = [];
        
                if (!this.provinceId) {
                    return;
                }
        
                await this.loadRegencies();
            },
            async loadRegencies() {
                this.loadingRegencies = true;
                this.regencies = await this.fetchRegions(this.regionUrls.regencies.replace('__province__', this.provinceId));
                this.loadingRegencies = false;
            },
            async regencyChanged() {
                this.districtId = '';
                this.villageId = '';
                this.districts = [];
                this.villages = [];
        
                if (!this.regencyId) {
                    return;
                }
        
                await this.loadDistricts();
            },
            async loadDistricts() {
                this.loadingDistricts = true;
                this.districts = await this.fetchRegions(this.regionUrls.districts.replace('__regency__', this.regencyId));
                this.loadingDistricts = false;
            },
            async districtChanged() {
                this.villageId = '';
                this.villages = [];
        
                if (!this.districtId) {
                    return;
                }
        
                await this.loadVillages();
            },
            async loadVillages() {
                this.loadingVillages = true;
                this.villages = await this.fetchRegions(this.regionUrls.villages.replace('__district__', this.districtId));
                this.loadingVillages = false;
            },
            villageChanged() {
                const village = this.villages.find((item) => String(item.id) === String(this.villageId));
        
                if (village?.postal_code) {
                    this.postalCode = village.postal_code;
                }
            },
            isRegionBusy() {
                return this.loadingProvinces || this.loadingRegencies || this.loadingDistricts || this.loadingVillages;
            },
            canSubmit() {
                return this.rajaOngkirConfigured && !this.isRegionBusy();
            },
        }" x-init="initRegions()">
        @csrf
        @if ($editingAddress)
            @method('patch')
        @endif

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="address_label" value="Label Alamat" />
                <x-text-input id="address_label" name="label" type="text" class="mt-1 block w-full"
                    :value="old('label', $editingAddress?->label)" placeholder="Rumah, Kantor, dll" required />
                <x-input-error class="mt-2" :messages="$errors->get('label')" />
            </div>

            <div>
                <x-input-label for="receiver_name" value="Nama Penerima" />
                <x-text-input id="receiver_name" name="receiver_name" type="text" class="mt-1 block w-full"
                    :value="old('receiver_name', $editingAddress?->receiver_name)" required />
                <x-input-error class="mt-2" :messages="$errors->get('receiver_name')" />
            </div>

            <div>
                <x-input-label for="receiver_phone" value="No. HP Penerima" />
                <x-text-input id="receiver_phone" name="receiver_phone" type="text" class="mt-1 block w-full"
                    :value="old('receiver_phone', $editingAddress?->receiver_phone)" required />
                <x-input-error class="mt-2" :messages="$errors->get('receiver_phone')" />
            </div>

            <div>
                <x-input-label for="postal_code" value="Kode Pos" />
                <x-text-input id="postal_code" name="postal_code" type="text" class="mt-1 block w-full"
                    x-model="postalCode" required />
                <x-input-error class="mt-2" :messages="$errors->get('postal_code')" />
            </div>
        </div>

        <div>
            <x-input-label for="full_address" value="Alamat Lengkap" />
            <textarea id="full_address" name="full_address" rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required>{{ old('full_address', $editingAddress?->full_address) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('full_address')" />
        </div>

        <div x-show="regionError" x-cloak class="border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-800">
            <span x-text="regionError"></span>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="province_id" value="Provinsi" />
                <select id="province_id" name="province_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    x-model="provinceId" x-on:change="provinceChanged()"
                    x-bind:disabled="!rajaOngkirConfigured || loadingProvinces" required>
                    <option value="" x-text="loadingProvinces ? 'Memuat provinsi...' : 'Pilih provinsi'"></option>
                    <template x-for="province in provinces" :key="province.id">
                        <option :value="province.id" x-text="province.name"></option>
                    </template>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('province_id')" />
            </div>

            <div>
                <x-input-label for="regency_id" value="Kabupaten/Kota" />
                <select id="regency_id" name="regency_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    x-model="regencyId" x-on:change="regencyChanged()"
                    x-bind:disabled="!rajaOngkirConfigured || !provinceId || loadingRegencies" required>
                    <option value=""
                        x-text="loadingRegencies ? 'Memuat kabupaten/kota...' : 'Pilih kabupaten/kota'"></option>
                    <template x-for="regency in regencies" :key="regency.id">
                        <option :value="regency.id" x-text="regency.name"></option>
                    </template>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('regency_id')" />
            </div>

            <div>
                <x-input-label for="district_id" value="Kecamatan" />
                <select id="district_id" name="district_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    x-model="districtId" x-on:change="districtChanged()"
                    x-bind:disabled="!rajaOngkirConfigured || !regencyId || loadingDistricts" required>
                    <option value="" x-text="loadingDistricts ? 'Memuat kecamatan...' : 'Pilih kecamatan'">
                    </option>
                    <template x-for="district in districts" :key="district.id">
                        <option :value="district.id" x-text="district.name"></option>
                    </template>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('district_id')" />
            </div>

            <div>
                <x-input-label for="village_id" value="Desa/Kelurahan" />
                <select id="village_id" name="village_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    x-model="villageId" x-on:change="villageChanged()"
                    x-bind:disabled="!rajaOngkirConfigured || !districtId || loadingVillages" required>
                    <option value=""
                        x-text="loadingVillages ? 'Memuat desa/kelurahan...' : 'Pilih desa/kelurahan'"></option>
                    <template x-for="village in villages" :key="village.id">
                        <option :value="village.id" x-text="village.name"></option>
                    </template>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('village_id')" />
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_main" value="1"
                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                @checked(old('is_main', $editingAddress?->is_main))>
            Jadikan alamat utama
        </label>

        <div class="flex flex-wrap items-center gap-3">
            <x-primary-button x-bind:disabled="!canSubmit()"
                x-bind:class="{ 'opacity-60 cursor-not-allowed': !canSubmit() }">
                {{ $editingAddress ? 'Edit Alamat' : 'Tambah Alamat' }}
            </x-primary-button>

            @if ($editingAddress)
                <a href="{{ route('profile.edit') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                    Batal edit
                </a>
            @endif
        </div>
    </form>

    <div class="mt-8 grid grid-cols-1 gap-4 lg:grid-cols-2">
        @forelse ($addresses as $address)
            <div class="border border-gray-200 bg-white p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-semibold text-gray-900">{{ $address->label }}</h3>
                            @if ($address->is_main)
                                <span
                                    class="bg-green-100 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-green-700">Utama</span>
                            @endif
                        </div>
                        <p class="mt-2 text-sm font-medium text-gray-900">{{ $address->receiver_name }} -
                            {{ $address->receiver_phone }}</p>
                        <p class="mt-2 text-sm text-gray-600">{{ $address->full_address }}</p>
                        <p class="mt-2 text-sm text-gray-600">
                            {{ $address->region_summary }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <a href="{{ route('profile.edit', ['address' => $address->id]) }}#address-form"
                        class="text-sm font-bold uppercase tracking-wide text-indigo-600 hover:text-indigo-800">
                        Edit
                    </a>
                    <button type="button"
                        class="text-sm font-bold uppercase tracking-wide text-red-600 hover:text-red-800"
                        x-on:click="openDeleteModal(@js([
    'label' => $address->label,
    'receiver_name' => $address->receiver_name,
    'receiver_phone' => $address->receiver_phone,
    'full_address' => $address->full_address,
    'region' => $address->region_summary,
    'is_main' => $address->is_main,
    'is_only_address' => $addresses->count() === 1,
]), @js(route('profile.addresses.destroy', $address)))">
                        Hapus
                    </button>
                </div>
            </div>
        @empty
            <div class="border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 lg:col-span-2">
                Belum ada alamat tersimpan.
            </div>
        @endforelse
    </div>

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 px-4 py-6" x-show="deleteModalOpen"
        x-transition.opacity x-cloak>
        <div class="absolute inset-0" x-on:click="closeDeleteModal"></div>

        <div class="relative max-h-[calc(100vh-3rem)] w-full max-w-lg overflow-y-auto bg-white p-6 shadow-2xl"
            x-show="deleteModalOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-3 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="translate-y-3 opacity-0" role="dialog" aria-modal="true"
            aria-labelledby="delete-address-title">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center bg-red-50 text-red-700">
                    <iconify-icon icon="mdi:trash-can-outline" class="text-2xl"></iconify-icon>
                </div>
                <div class="min-w-0 flex-1">
                    <p id="delete-address-title" class="text-xl font-black text-[#8A1022]">Hapus alamat ini?</p>
                    <p class="mt-2 text-sm font-medium leading-6 text-gray-600">
                        Alamat ini akan dihapus dari daftar alamat tersimpan Anda. Riwayat pesanan yang sudah dibuat
                        tidak akan berubah.
                    </p>
                </div>
                <button type="button" class="text-gray-400 hover:text-gray-700" x-on:click="closeDeleteModal"
                    x-bind:disabled="deleteSubmitting" aria-label="Tutup modal">
                    <iconify-icon icon="mdi:close" class="text-2xl"></iconify-icon>
                </button>
            </div>

            <div class="mt-5 border border-gray-200 bg-gray-50 p-4">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="font-black text-gray-900" x-text="deleteAddress?.label"></p>
                    <template x-if="deleteAddress?.is_main">
                        <span
                            class="bg-green-100 px-2 py-1 text-xs font-black uppercase tracking-wide text-green-700">Utama</span>
                    </template>
                </div>
                <p class="mt-3 text-sm font-bold text-gray-900">
                    <span x-text="deleteAddress?.receiver_name"></span>
                    <span x-show="deleteAddress?.receiver_phone"> - </span>
                    <span x-text="deleteAddress?.receiver_phone"></span>
                </p>
                <p class="mt-2 break-words text-sm leading-6 text-gray-600" x-text="deleteAddress?.full_address"></p>
                <p class="mt-2 break-words text-sm leading-6 text-gray-600" x-text="deleteAddress?.region"></p>
            </div>

            <template x-if="deleteAddress?.is_main">
                <div class="mt-4 border border-amber-200 bg-amber-50 p-4 text-sm font-medium leading-6 text-amber-900">
                    Alamat ini adalah alamat utama. Setelah dihapus, sistem akan memilih alamat terbaru lainnya sebagai
                    alamat utama.
                </div>
            </template>

            <template x-if="deleteAddress?.is_only_address">
                <div class="mt-4 border border-blue-200 bg-blue-50 p-4 text-sm font-medium leading-6 text-blue-900">
                    Ini satu-satunya alamat tersimpan. Setelah dihapus, Anda perlu menambahkan alamat baru sebelum checkout.
                </div>
            </template>

            <form method="post" x-bind:action="deleteFormAction" x-on:submit="submitDeleteForm"
                class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                @csrf
                @method('delete')
                <button type="button"
                    class="inline-flex items-center justify-center border border-gray-300 px-4 py-2 text-xs font-black uppercase tracking-[.14em] text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                    x-on:click="closeDeleteModal" x-bind:disabled="deleteSubmitting">
                    Batal
                </button>
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 bg-[#C8102E] px-4 py-2 text-xs font-black uppercase tracking-[.14em] text-white hover:bg-[#9F0D24] disabled:cursor-not-allowed disabled:opacity-70"
                    x-bind:disabled="deleteSubmitting">
                    <span x-show="deleteSubmitting"
                        class="h-4 w-4 animate-spin border-2 border-white/40 border-t-white"></span>
                    <span x-text="deleteSubmitting ? 'Menghapus...' : 'Ya, Hapus Alamat'"></span>
                </button>
            </form>
        </div>
    </div>
</section>
