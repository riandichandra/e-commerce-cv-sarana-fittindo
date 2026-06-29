# Planning Menghapus Opsi Tambah Alamat Manual Saat Checkout

## Tujuan

Menghapus opsi input alamat manual pada proses checkout. Setelah perubahan, pelanggan wajib memilih alamat yang sudah tersimpan di profil sebelum dapat checkout.

Target akhir:

- Halaman checkout tidak lagi menampilkan opsi `Isi alamat manual`.
- Field manual seperti nama penerima, telepon, alamat lengkap, provinsi, kota/kabupaten, kecamatan, desa/kelurahan, dan kode pos tidak bisa digunakan sebagai sumber data checkout.
- Backend checkout menolak request tanpa `shipping_address_id` yang valid milik user.
- Endpoint hitung ongkir hanya menerima `shipping_address_id`, bukan input wilayah manual.
- Pesan UI mengarahkan pelanggan untuk menambahkan atau memperbarui alamat di profil.

## Struktur Database Terkait

### `user_addresses`

Tabel ini menjadi satu-satunya sumber alamat checkout setelah opsi manual dihapus.

Kolom penting:

- `id`
- `user_id`
- `label`
- `receiver_name`
- `receiver_phone`
- `full_address`
- `province_id`
- `regency_id`
- `district_id`
- `village_id`
- `province_name`
- `city_name`
- `district_name`
- `village_name`
- `region_source`
- `postal_code`
- `is_main`

Model terkait: `app/Models/UserAddress.php`

Accessor penting:

- `province_display_name`
- `city_display_name`
- `district_display_name`
- `village_display_name`
- `region_summary`

### `orders`

Tabel `orders` tetap menyimpan snapshot alamat pengiriman. Kolom ini tetap diperlukan walaupun alamat checkout wajib berasal dari profil.

Kolom snapshot alamat:

- `shipping_name`
- `shipping_phone`
- `shipping_address`
- `shipping_province`
- `shipping_city`
- `shipping_district`
- `shipping_village`
- `shipping_postal_code`
- `shipping_destination_district_id`

Catatan penting:

- Tidak perlu menambah `shipping_address_id` ke `orders` untuk requirement ini.
- Snapshot order harus tetap disimpan supaya riwayat pesanan tidak berubah jika alamat profil diedit atau dihapus setelah checkout.

## Analisis Codebase Saat Ini

## 1. Halaman Checkout

File: `resources/views/pelanggan/cart/checkout.blade.php`

Kondisi saat ini:

- Dropdown alamat tersimpan memiliki opsi kosong:

  ```html
  <option value="">Isi alamat manual</option>
  ```

- Jika user belum punya alamat tersimpan, halaman memberi pesan:

  ```text
  Belum ada alamat tersimpan di profil. Anda tetap bisa mengisi alamat secara manual.
  ```

- Field alamat manual masih tersedia dan akan aktif ketika `selectedAddressId` kosong:
  - `shipping_name`
  - `shipping_phone`
  - `shipping_address`
  - `shipping_province_id`
  - `shipping_city_id`
  - `shipping_district_id`
  - `shipping_village_id`
  - `shipping_postal_code`

- Alpine state dan method masih mendukung manual shipping:
  - `manualShippingActive()`
  - `hasManualShipping()`
  - `clearSelectedAddress()`
  - `clearRegionSelection()`
  - `loadProvinces()`, `loadRegencies()`, `loadDistricts()`, `loadVillages()` untuk manual wilayah
  - `manualRegionReady()`
  - `shippingDestinationReady()` menerima alamat tersimpan atau region manual
  - `refreshShippingOptions()` mengirim `shipping_address_id` atau field wilayah manual

- Fallback ongkir admin masih tersedia ketika ongkir otomatis gagal:
  - `useAdminFallback()`
  - hidden input `shipping_fallback`
  - tombol `Konfirmasi Ongkir Admin`
  - label/summary untuk `admin_manual`

Dampak jika hanya field manual disembunyikan:

- Request manual masih bisa dikirim langsung ke backend.
- Endpoint ongkir masih menerima wilayah manual.
- Checkout tanpa alamat tersimpan masih mungkin jika payload dikirim langsung.

Jadi perubahan harus dilakukan di UI dan backend.

## 2. Backend Checkout

File: `app/Http/Controllers/Pelanggan/CartController.php`

### `checkoutForm()`

Saat ini:

- Mengambil alamat user:

  ```php
  $addresses = $request->user()
      ->addresses()
      ->orderByDesc('is_main')
      ->latest()
      ->get();
  ```

- Jika alamat kosong, halaman checkout tetap dirender dan user bisa input manual.

Rencana:

- Tetap ambil alamat seperti saat ini.
- Jika alamat kosong, ada dua opsi:
  1. Redirect ke halaman profil dengan pesan agar menambahkan alamat terlebih dahulu.
  2. Tetap render checkout tetapi tampilkan empty state dan tombol menuju profil, tombol checkout disabled.

Rekomendasi: opsi 1 lebih tegas dan sesuai requirement bahwa user harus memilih alamat profil.

Alur rekomendasi:

```php
if ($addresses->isEmpty()) {
    return redirect()
        ->route('profile.edit')
        ->with('error', 'Tambahkan alamat pengiriman di profil sebelum checkout.');
}
```

Catatan:

- Jika ingin langsung fokus ke section alamat profil, bisa redirect ke `route('profile.edit') . '#address-form'` jika route helper dan fragment disusun manual.
- Perlu cek layout profil apakah session `error` ditampilkan. Jika tidak, gunakan session `status` yang sudah dipakai di profil, atau tambahkan handling pesan.

### `checkout()`

Saat ini:

- `shipping_address_id` nullable.
- Jika `shipping_address_id` ada, data order diisi dari alamat profil.
- Jika tidak ada, data order diisi dari field manual.
- `shipping_fallback=admin_manual` bisa membuat order menunggu konfirmasi ongkir admin.

Rencana:

- `shipping_address_id` wajib.
- Hapus dukungan field alamat manual dari validasi checkout.
- Hapus dukungan `shipping_fallback` dari validasi checkout jika fallback admin tidak lagi diinginkan pada checkout pelanggan.
- Ambil alamat dengan `findOrFail` atau hasil validasi `exists` milik user.
- Isi semua snapshot order dari alamat tersimpan.
- `destinationDistrictId` selalu dari alamat tersimpan.

Validasi baru yang disarankan:

```php
'shipping_address_id' => [
    'required',
    Rule::exists('user_addresses', 'id')->where('user_id', $request->user()->id),
],
'payment_method_id' => [
    'required',
    Rule::exists('payment_methods', 'id')->where('is_active', true),
],
'shipping_quote_token' => ['required', 'string', 'max:120'],
'notes' => ['nullable', 'string'],
```

Field yang tidak lagi diterima untuk checkout:

- `shipping_name`
- `shipping_phone`
- `shipping_address`
- `shipping_province_id`
- `shipping_city_id`
- `shipping_district_id`
- `shipping_village_id`
- `shipping_province`
- `shipping_city`
- `shipping_district`
- `shipping_village`
- `shipping_postal_code`
- `shipping_fallback`

Catatan:

- Data field manual boleh tetap ada di request, tetapi sebaiknya tidak dipakai.
- Lebih aman jika validasi tidak memasukkan field manual sama sekali.

### Validasi alamat tersimpan untuk ongkir

Saat ini jika alamat tersimpan tidak punya `district_id`, checkout mengembalikan error:

```text
Alamat tersimpan perlu diperbarui ke data RajaOngkir sebelum ongkir otomatis dapat dihitung.
```

Rencana:

- Pertahankan guard ini.
- Karena tidak ada input manual, jika alamat belum valid RajaOngkir, user harus memperbarui alamat di profil.

## 3. Endpoint Ongkir

File: `app/Http/Controllers/RajaOngkirShippingController.php`

Saat ini endpoint `pelanggan.shipping.domestic-cost` menerima dua mode:

1. `shipping_address_id`
2. Field wilayah manual:
   - `shipping_province_id`
   - `shipping_city_id`
   - `shipping_district_id`
   - `shipping_village_id`

Rencana:

- Ubah validasi agar `shipping_address_id` wajib.
- Hapus validasi field wilayah manual.
- Ubah `resolveDestinationDistrictId()` agar hanya menerima alamat tersimpan.
- Hapus branch `resolveRegionChain()` untuk wilayah manual dari endpoint ini.

Validasi baru:

```php
$validated = $request->validate([
    'shipping_address_id' => [
        'required',
        Rule::exists('user_addresses', 'id')->where('user_id', $request->user()->id),
    ],
]);
```

Method `resolveDestinationDistrictId()` bisa disederhanakan:

```php
$address = $request->user()
    ->addresses()
    ->findOrFail($validated['shipping_address_id']);

if ($address->region_source !== 'rajaongkir' || ! filled($address->district_id)) {
    throw new RuntimeException('Alamat tersimpan perlu diperbarui ke data RajaOngkir sebelum ongkir otomatis dapat dihitung.');
}

return (string) $address->district_id;
```

## 4. Profile Address UI

File: `resources/views/profile/partials/user-addresses-form.blade.php`

Ada teks saat menghapus satu-satunya alamat:

```text
Ini satu-satunya alamat tersimpan. Checkout tetap bisa memakai input alamat manual.
```

Rencana:

- Ubah teks karena tidak lagi benar.
- Pesan baru yang disarankan:

```text
Ini satu-satunya alamat tersimpan. Setelah dihapus, Anda perlu menambahkan alamat baru sebelum checkout.
```

Jika ada pesan lain yang menyebut input manual checkout, harus diganti.

## 5. Checkout View Detail Perubahan

File: `resources/views/pelanggan/cart/checkout.blade.php`

Perubahan yang direncanakan:

### Data awal

- `$selectedAddressId` tetap default ke alamat utama.
- Jika tidak ada alamat, halaman seharusnya tidak dirender jika `checkoutForm()` redirect. Namun view tetap aman jika alamat kosong.

### Dropdown alamat

Hapus opsi:

```html
<option value="">Isi alamat manual</option>
```

Ganti placeholder jika perlu:

```html
<option value="">Pilih alamat</option>
```

Tetapi karena `shipping_address_id` wajib, lebih baik:

- Jika ada alamat, pilih alamat utama otomatis.
- Select tetap required.
- Tidak ada opsi manual.

### Empty state alamat

Jika `$addresses->isEmpty()` tetap mungkin terjadi, tampilkan pesan:

```text
Belum ada alamat tersimpan. Tambahkan alamat di profil sebelum checkout.
```

Tambahkan link ke profil:

```php
route('profile.edit')
```

### Field manual

Hapus atau ubah menjadi readonly preview:

- Nama penerima: readonly dari alamat terpilih.
- Telepon: readonly dari alamat terpilih.
- Alamat lengkap: readonly dari alamat terpilih.
- Provinsi/kota/kecamatan/desa/kode pos: readonly dari alamat terpilih.

Rekomendasi:

- Jangan kirim field manual sebagai input editable.
- Untuk snapshot order, backend mengambil dari `UserAddress`, jadi field hidden/manual tidak diperlukan.
- View cukup menampilkan ringkasan alamat terpilih dan wilayah readonly.

### Alpine state yang bisa dihapus/disederhanakan

Hapus kebutuhan state manual:

- `selectedProvinceId`
- `selectedCityId`
- `selectedDistrictId`
- `selectedVillageId`
- `shippingProvinceId`
- `shippingCityId`
- `shippingDistrictId`
- `shippingVillageId`
- `provinces`
- `regencies`
- `districts`
- `villages`
- `regionUrls`
- loading region manual
- semua method load region manual
- `manualShippingActive()`
- `manualRegionReady()`
- `clearSelectedAddress()`
- `clearRegionSelection()`
- `hasManualShipping()`

Pertahankan atau sederhanakan:

- `addresses`
- `selectedAddressId`
- `selectedAddress()`
- `addressSelectionChanged()`
- `applySelectedAddress()` sebagai pengisi preview readonly
- `refreshShippingOptions()` hanya mengirim `shipping_address_id`
- `shippingDestinationReady()` menjadi `Boolean(this.selectedAddressId)`

### Fallback ongkir admin

Keputusan yang perlu ditegaskan:

- Requirement hanya menghapus input alamat manual, bukan selalu menghapus fallback admin.
- Namun saat ini fallback admin dipakai sebagai alternatif jika ongkir otomatis gagal. Dengan alamat tersimpan wajib, fallback admin masih bisa secara bisnis dipakai untuk alamat tersimpan yang tidak bisa dihitung otomatis.

Rekomendasi teknis:

- Jika ingin checkout selalu butuh layanan ongkir otomatis, hapus `shipping_fallback=admin_manual` dari checkout pelanggan.
- Jika bisnis masih butuh admin konfirmasi ongkir, fallback admin boleh tetap ada, tetapi tetap harus memakai `shipping_address_id` valid.

Karena user hanya meminta menghapus opsi tambah alamat manual, rekomendasi implementasi konservatif:

- Hapus input alamat manual.
- Pertahankan fallback admin hanya untuk kasus alamat tersimpan tidak mendapat layanan ongkir otomatis, jika fitur ini masih diperlukan.
- Namun validasi backend tetap wajib `shipping_address_id`.

Jika fallback admin dipertahankan, validasi `shipping_quote_token` tetap:

```php
'shipping_quote_token' => ['required_unless:shipping_fallback,admin_manual', 'nullable', 'string', 'max:120'],
'shipping_fallback' => ['nullable', Rule::in(['admin_manual'])],
```

Tetapi `shipping_fallback` tidak boleh menjadi pengganti alamat. Alamat tetap wajib dari profil.

## 6. Test yang Perlu Diubah

File utama: `tests/Feature/Pelanggan/CheckoutTest.php`

Test saat ini banyak memakai helper `manualCheckoutPayload()` untuk checkout tanpa alamat tersimpan. Setelah opsi manual dihapus, test harus dipindahkan ke alamat tersimpan.

### Test yang harus disesuaikan

- `test_customer_can_fetch_rajaongkir_shipping_options`
  - Saat ini post JSON mengirim wilayah manual.
  - Ubah agar membuat alamat tersimpan dan mengirim `shipping_address_id`.

- `test_customer_can_checkout_with_shipping_detail_and_payment_method`
  - Saat ini memakai `manualCheckoutPayload()`.
  - Ubah menjadi `savedAddressCheckoutPayload()` yang mengirim `shipping_address_id`, `payment_method_id`, dan `shipping_quote_token`.

- `test_customer_checkout_to_palembang_gets_fixed_shipping_cost`
  - Ubah alamat tersimpan menjadi Palembang dan quote destination sesuai `district_id` alamat.

- Semua test promosi checkout:
  - Ubah payload dari manual ke saved address.

- `test_checkout_outside_palembang_uses_rajaongkir_shipping_quote_in_total`
  - Ubah ke alamat tersimpan luar Palembang.

- `test_checkout_reduces_stock_to_zero_and_sets_status_unavailable`
  - Ubah payload ke alamat tersimpan.

- `test_customer_cannot_checkout_when_cart_quantity_exceeds_stock`
  - Ubah payload ke alamat tersimpan.

- `test_customer_cannot_checkout_when_product_category_is_inactive`
  - Ubah payload ke alamat tersimpan.

- `test_customer_cannot_upload_payment_proof_before_shipping_cost_is_confirmed`
  - Jika fallback admin dipertahankan, payload harus tetap memakai alamat tersimpan plus `shipping_fallback=admin_manual`.
  - Jika fallback admin dihapus, test ini perlu dihapus atau dipindahkan ke fitur admin/manual shipping lain.

- `test_customer_cannot_checkout_with_invalid_manual_raja_ongkir_region_chain`
  - Hapus atau ubah menjadi `test_customer_cannot_checkout_when_saved_address_region_is_invalid`.
  - Skenario baru: alamat tersimpan punya `region_source` bukan `rajaongkir` atau `district_id` kosong.

- `test_customer_cannot_checkout_manual_address_when_raja_ongkir_key_is_missing`
  - Ubah nama menjadi `test_customer_cannot_fetch_shipping_options_when_raja_ongkir_key_is_missing` atau tetap cek checkout saved address ketika config missing.

### Test baru yang perlu ditambahkan

1. Checkout page redirect jika user belum punya alamat tersimpan.

   Ekspektasi:
   - Redirect ke profil atau halaman tertentu.
   - Session error/status menjelaskan harus tambah alamat.

2. Checkout process menolak request tanpa `shipping_address_id`.

   Ekspektasi:
   - Session error `shipping_address_id`.
   - Tidak ada order dibuat.

3. Shipping quote endpoint menolak request tanpa `shipping_address_id`.

   Ekspektasi:
   - Status 422.

4. Checkout process mengabaikan/menolak field manual jika dikirim langsung.

   Ekspektasi:
   - Tanpa `shipping_address_id`, tetap gagal walaupun field manual lengkap.

## 7. File yang Direncanakan Berubah

File produksi:

- `app/Http/Controllers/Pelanggan/CartController.php`
- `app/Http/Controllers/RajaOngkirShippingController.php`
- `resources/views/pelanggan/cart/checkout.blade.php`
- `resources/views/profile/partials/user-addresses-form.blade.php`

File test:

- `tests/Feature/Pelanggan/CheckoutTest.php`

Kemungkinan file lain:

- Jika ada komponen/partial checkout terpisah, sesuaikan juga.
- Jika profil tidak menampilkan flash error dari redirect checkout, perlu update layout/profile view agar pesan terlihat.

## 8. Urutan Implementasi yang Disarankan

1. Ubah backend `CartController::validateCheckout()` agar `shipping_address_id` wajib dan field manual tidak lagi diterima.
2. Ubah `CartController::checkout()` agar selalu mengambil snapshot alamat dari `UserAddress`.
3. Tentukan dan implementasikan perilaku `checkoutForm()` jika user belum punya alamat:
   - rekomendasi: redirect ke profil dengan pesan.
4. Ubah `RajaOngkirShippingController` agar endpoint ongkir hanya menerima `shipping_address_id`.
5. Sederhanakan `checkout.blade.php`:
   - hapus opsi `Isi alamat manual`
   - hapus field manual editable
   - tampilkan alamat tersimpan sebagai pilihan wajib dan preview readonly
   - update teks empty state
6. Ubah teks di `profile/partials/user-addresses-form.blade.php` yang masih menyebut checkout manual.
7. Update test checkout dari payload manual ke payload alamat tersimpan.
8. Tambahkan test untuk menolak checkout tanpa alamat tersimpan.
9. Jalankan pencarian:

   ```bash
   rg "manual|shipping_fallback|shipping_province_id|required_without:shipping_address_id|Isi alamat manual|alamat manual" app resources tests -n
   ```

   Pastikan sisa pemakaian memang masih valid, terutama jika fallback admin tetap dipertahankan.

10. Jalankan validasi:
    - `php -l` untuk controller dan test yang berubah.
    - `php artisan view:cache` untuk Blade.
    - `php artisan test tests\Feature\Pelanggan\CheckoutTest.php`.
    - Test tambahan yang relevan untuk profile jika teks profil berubah.

## 9. Risiko dan Mitigasi

### Risiko: User tidak bisa checkout jika belum punya alamat

Ini memang sesuai requirement.

Mitigasi:

- Redirect atau tampilkan CTA jelas ke halaman profil.
- Pesan harus eksplisit: user perlu menambahkan alamat terlebih dahulu.

### Risiko: Alamat tersimpan belum lengkap data RajaOngkir

Tanpa input manual, alamat lama yang belum punya `region_source=rajaongkir` atau `district_id` tidak bisa dipakai untuk ongkir otomatis.

Mitigasi:

- Tampilkan error yang mengarahkan user memperbarui alamat di profil.
- Di dropdown alamat, bisa tampilkan indikator jika alamat perlu diperbarui.

### Risiko: Fallback admin ambiguity

Jika fallback admin tetap ada, user bisa checkout tanpa quote otomatis tetapi tetap harus memilih alamat tersimpan.

Mitigasi:

- Pastikan `shipping_address_id` tetap required meskipun `shipping_fallback=admin_manual`.
- Ubah teks fallback agar jelas: admin mengonfirmasi ongkir untuk alamat tersimpan yang dipilih.

### Risiko: Test existing banyak bergantung pada manual payload

Mitigasi:

- Buat helper test baru, misalnya `savedAddressCheckoutPayload()`.
- Ubah test checkout secara konsisten.

## 10. Definition of Done

Implementasi dianggap selesai jika:

- UI checkout tidak memiliki opsi `Isi alamat manual`.
- User tanpa alamat tersimpan tidak dapat membuka/melanjutkan checkout tanpa diarahkan menambah alamat.
- Backend checkout menolak request tanpa `shipping_address_id` valid.
- Endpoint ongkir menolak request tanpa `shipping_address_id` valid.
- Order tetap menyimpan snapshot alamat dari `user_addresses`.
- Tidak ada teks yang mengatakan checkout masih bisa memakai input alamat manual.
- Test checkout relevan lulus.
