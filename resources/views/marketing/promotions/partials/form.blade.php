@csrf

<div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
    <div>
        <label for="name" class="text-sm font-bold text-texthighlight">Nama</label>
        <input id="name" name="name" type="text" value="{{ old('name', $promotion->name) }}"
            class="mt-2 w-full border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary" required>
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <label for="type" class="text-sm font-bold text-texthighlight">Tipe</label>
        <select id="type" name="type"
            class="mt-2 w-full border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary" required>
            <option value="percent" @selected(old('type', $promotion->type) === 'percent')>Persen</option>
            <option value="nominal" @selected(old('type', $promotion->type) === 'nominal')>Nominal</option>
        </select>
        <x-input-error :messages="$errors->get('type')" class="mt-2" />
    </div>

    <div>
        <label for="value" class="text-sm font-bold text-texthighlight">Nilai</label>
        <input id="value" name="value" type="number" step="0.01" min="0"
            value="{{ old('value', $promotion->value) }}"
            class="mt-2 w-full border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary" required>
        <x-input-error :messages="$errors->get('value')" class="mt-2" />
    </div>

    <div>
        <label for="min_purchase" class="text-sm font-bold text-texthighlight">Minimal Pembelian</label>
        <input id="min_purchase" name="min_purchase" type="number" step="0.01" min="0"
            value="{{ old('min_purchase', $promotion->min_purchase) }}"
            class="mt-2 w-full border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
        <x-input-error :messages="$errors->get('min_purchase')" class="mt-2" />
    </div>

    <div>
        <label for="max_discount" class="text-sm font-bold text-texthighlight">Diskon Maksimum</label>
        <input id="max_discount" name="max_discount" type="number" step="0.01" min="0"
            value="{{ old('max_discount', $promotion->max_discount) }}"
            class="mt-2 w-full border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
        <x-input-error :messages="$errors->get('max_discount')" class="mt-2" />
    </div>

    <div>
        <label for="start_date" class="text-sm font-bold text-texthighlight">Tanggal Mulai</label>
        <input id="start_date" name="start_date" type="date"
            value="{{ old('start_date', optional($promotion->start_date)->format('Y-m-d')) }}"
            class="mt-2 w-full border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary" required>
        <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
    </div>

    <div>
        <label for="end_date" class="text-sm font-bold text-texthighlight">Tanggal Selesai</label>
        <input id="end_date" name="end_date" type="date"
            value="{{ old('end_date', optional($promotion->end_date)->format('Y-m-d')) }}"
            class="mt-2 w-full border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary" required>
        <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
    </div>

    <div>
        <label for="banner_url" class="text-sm font-bold text-texthighlight">Banner URL</label>
        <input id="banner_url" name="banner_url" type="url" value="{{ old('banner_url', $promotion->banner_url) }}"
            class="mt-2 w-full border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
        <x-input-error :messages="$errors->get('banner_url')" class="mt-2" />
    </div>

    <div>
        <label for="banner_image" class="text-sm font-bold text-texthighlight">Gambar Banner</label>
        <input id="banner_image" name="banner_image" type="file" accept="image/*"
            class="mt-2 w-full border border-gray-300 bg-white p-2 text-sm shadow-sm focus:border-primary focus:ring-primary">
        <x-input-error :messages="$errors->get('banner_image')" class="mt-2" />
    </div>
</div>

<div class="mt-5">
    <label for="description" class="text-sm font-bold text-texthighlight">Deskripsi</label>
    <textarea id="description" name="description" rows="4"
        class="mt-2 w-full border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">{{ old('description', $promotion->description) }}</textarea>
    <x-input-error :messages="$errors->get('description')" class="mt-2" />
</div>

<label class="mt-5 flex items-center gap-3 text-sm font-bold text-texthighlight">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $promotion->is_active))
        class="border-gray-300 text-primary shadow-sm focus:ring-primary">
    Aktif Promosi
</label>

<div class="mt-7 flex items-center justify-end gap-3">
    <a href="{{ route('marketing.promotions.index') }}"
        class="bg-gray-200 px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-300">
        BATAL
    </a>
    <button type="submit" class="bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary-dark">
        SIMPAN PROMOSI
    </button>
</div>
