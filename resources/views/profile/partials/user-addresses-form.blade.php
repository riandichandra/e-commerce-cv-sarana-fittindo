@php
    $selectedProvince = (string) old('province_id', $editingAddress?->province_id);
    $selectedRegency = (string) old('regency_id', $editingAddress?->regency_id);
    $selectedDistrict = (string) old('district_id', $editingAddress?->district_id);
    $selectedVillage = (string) old('village_id', $editingAddress?->village_id);
@endphp

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Alamat Pengiriman
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Kelola alamat yang dapat digunakan untuk proses checkout.
        </p>
    </header>

    @if (session('status') === 'address-created')
        <p class="mt-4 text-sm font-medium text-green-600">Alamat berhasil ditambahkan.</p>
    @elseif (session('status') === 'address-updated')
        <p class="mt-4 text-sm font-medium text-green-600">Alamat berhasil diperbarui.</p>
    @elseif (session('status') === 'address-deleted')
        <p class="mt-4 text-sm font-medium text-green-600">Alamat berhasil dihapus.</p>
    @endif

    @unless ($hasRegionData)
        <div class="mt-6 border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
            Data provinsi, kabupaten/kota, kecamatan, dan desa/kelurahan belum lengkap. Lengkapi data wilayah terlebih dahulu agar alamat bisa disimpan.
        </div>
    @endunless

    <form
        method="post"
        id="address-form"
        action="{{ $editingAddress ? route('profile.addresses.update', $editingAddress) : route('profile.addresses.store') }}"
        class="mt-6 space-y-6"
        x-data="{
            provinces: @js($provinces->map(fn ($province) => ['id' => $province->id, 'name' => $province->name])->values()),
            regencies: @js($regencies->map(fn ($regency) => ['id' => $regency->id, 'name' => $regency->name])->values()),
            districts: @js($districts->map(fn ($district) => ['id' => $district->id, 'name' => $district->name])->values()),
            villages: @js($villages->map(fn ($village) => ['id' => $village->id, 'name' => $village->name])->values()),
            provinceId: @js($selectedProvince),
            regencyId: @js($selectedRegency),
            districtId: @js($selectedDistrict),
            villageId: @js($selectedVillage),
            loadingRegencies: false,
            loadingDistricts: false,
            loadingVillages: false,
            regionUrls: {
                regencies: @js(route('regions.regencies.index', ['province' => '__province__'])),
                districts: @js(route('regions.districts.index', ['regency' => '__regency__'])),
                villages: @js(route('regions.villages.index', ['district' => '__district__'])),
            },
            async fetchRegions(url) {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (! response.ok) {
                    return [];
                }

                return await response.json();
            },
            async provinceChanged() {
                this.regencyId = '';
                this.districtId = '';
                this.villageId = '';
                this.regencies = [];
                this.districts = [];
                this.villages = [];

                if (! this.provinceId) {
                    return;
                }

                this.loadingRegencies = true;
                this.regencies = await this.fetchRegions(this.regionUrls.regencies.replace('__province__', this.provinceId));
                this.loadingRegencies = false;
            },
            async regencyChanged() {
                this.districtId = '';
                this.villageId = '';
                this.districts = [];
                this.villages = [];

                if (! this.regencyId) {
                    return;
                }

                this.loadingDistricts = true;
                this.districts = await this.fetchRegions(this.regionUrls.districts.replace('__regency__', this.regencyId));
                this.loadingDistricts = false;
            },
            async districtChanged() {
                this.villageId = '';
                this.villages = [];

                if (! this.districtId) {
                    return;
                }

                this.loadingVillages = true;
                this.villages = await this.fetchRegions(this.regionUrls.villages.replace('__district__', this.districtId));
                this.loadingVillages = false;
            },
        }"
    >
        @csrf
        @if ($editingAddress)
            @method('patch')
        @endif

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="address_label" value="Label Alamat" />
                <x-text-input id="address_label" name="label" type="text" class="mt-1 block w-full" :value="old('label', $editingAddress?->label)" placeholder="Rumah, Kantor, dll" required />
                <x-input-error class="mt-2" :messages="$errors->get('label')" />
            </div>

            <div>
                <x-input-label for="receiver_name" value="Nama Penerima" />
                <x-text-input id="receiver_name" name="receiver_name" type="text" class="mt-1 block w-full" :value="old('receiver_name', $editingAddress?->receiver_name)" required />
                <x-input-error class="mt-2" :messages="$errors->get('receiver_name')" />
            </div>

            <div>
                <x-input-label for="receiver_phone" value="No. HP Penerima" />
                <x-text-input id="receiver_phone" name="receiver_phone" type="text" class="mt-1 block w-full" :value="old('receiver_phone', $editingAddress?->receiver_phone)" required />
                <x-input-error class="mt-2" :messages="$errors->get('receiver_phone')" />
            </div>

            <div>
                <x-input-label for="postal_code" value="Kode Pos" />
                <x-text-input id="postal_code" name="postal_code" type="text" class="mt-1 block w-full" :value="old('postal_code', $editingAddress?->postal_code)" required />
                <x-input-error class="mt-2" :messages="$errors->get('postal_code')" />
            </div>
        </div>

        <div>
            <x-input-label for="full_address" value="Alamat Lengkap" />
            <textarea id="full_address" name="full_address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ old('full_address', $editingAddress?->full_address) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('full_address')" />
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="province_id" value="Provinsi" />
                <select id="province_id" name="province_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" x-model="provinceId" x-on:change="provinceChanged" required>
                    <option value="">Pilih provinsi</option>
                    <template x-for="province in provinces" :key="province.id">
                        <option :value="province.id" x-text="province.name"></option>
                    </template>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('province_id')" />
            </div>

            <div>
                <x-input-label for="regency_id" value="Kabupaten/Kota" />
                <select id="regency_id" name="regency_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" x-model="regencyId" x-on:change="regencyChanged" x-bind:disabled="! provinceId || loadingRegencies" required>
                    <option value="" x-text="loadingRegencies ? 'Memuat kabupaten/kota...' : 'Pilih kabupaten/kota'"></option>
                    <template x-for="regency in regencies" :key="regency.id">
                        <option :value="regency.id" x-text="regency.name"></option>
                    </template>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('regency_id')" />
            </div>

            <div>
                <x-input-label for="district_id" value="Kecamatan" />
                <select id="district_id" name="district_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" x-model="districtId" x-on:change="districtChanged" x-bind:disabled="! regencyId || loadingDistricts" required>
                    <option value="" x-text="loadingDistricts ? 'Memuat kecamatan...' : 'Pilih kecamatan'"></option>
                    <template x-for="district in districts" :key="district.id">
                        <option :value="district.id" x-text="district.name"></option>
                    </template>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('district_id')" />
            </div>

            <div>
                <x-input-label for="village_id" value="Desa/Kelurahan" />
                <select id="village_id" name="village_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" x-model="villageId" x-bind:disabled="! districtId || loadingVillages" required>
                    <option value="" x-text="loadingVillages ? 'Memuat desa/kelurahan...' : 'Pilih desa/kelurahan'"></option>
                    <template x-for="village in villages" :key="village.id">
                        <option :value="village.id" x-text="village.name"></option>
                    </template>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('village_id')" />
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_main" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('is_main', $editingAddress?->is_main))>
            Jadikan alamat utama
        </label>

        <div class="flex flex-wrap items-center gap-3">
            <x-primary-button :disabled="! $hasRegionData">
                {{ $editingAddress ? 'Perbarui Alamat' : 'Tambah Alamat' }}
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
                                <span class="bg-green-100 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-green-700">Utama</span>
                            @endif
                        </div>
                        <p class="mt-2 text-sm font-medium text-gray-900">{{ $address->receiver_name }} - {{ $address->receiver_phone }}</p>
                        <p class="mt-2 text-sm text-gray-600">{{ $address->full_address }}</p>
                        <p class="mt-2 text-sm text-gray-600">
                            {{ $address->village?->name }},
                            {{ $address->district?->name }},
                            {{ $address->regency?->name }},
                            {{ $address->province?->name }}
                            {{ $address->postal_code }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <a href="{{ route('profile.edit', ['address' => $address->id]) }}#address-form" class="text-sm font-bold uppercase tracking-wide text-indigo-600 hover:text-indigo-800">
                        Edit
                    </a>
                    <form method="post" action="{{ route('profile.addresses.destroy', $address) }}" onsubmit="return confirm('Hapus alamat ini?')">
                        @csrf
                        @method('delete')
                        <button type="submit" class="text-sm font-bold uppercase tracking-wide text-red-600 hover:text-red-800">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 lg:col-span-2">
                Belum ada alamat tersimpan.
            </div>
        @endforelse
    </div>
</section>
