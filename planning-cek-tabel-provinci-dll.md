# Planning Cek Tabel Province, Regency, District, dan Village

## Status

Dokumen ini berisi hasil analisis dan rencana implementasi. Belum ada model, tabel, migration, seeder, package, atau data wilayah yang dihapus pada tahap ini.

## Tujuan

Memastikan apakah tabel dan model wilayah lokal berikut masih diperlukan setelah aplikasi menggunakan RajaOngkir:

- `provinces` / `App\Models\Province`
- `regencies` / `App\Models\Regency`
- `districts` / `App\Models\District`
- `villages` / `App\Models\Village`

Penghapusan harus menjaga alamat pelanggan, checkout, ongkos kirim, histori pesanan, dan data produk tetap utuh.

## Kesimpulan

Keempat tabel dan model wilayah lokal sudah bukan sumber data wilayah aktif. Pilihan provinsi sampai desa/kelurahan di profil dan checkout diambil dari API RajaOngkir melalui `RajaOngkirService`.

Keempat tabel/model tersebut merupakan kandidat kuat untuk dihapus, tetapi tidak boleh langsung dijatuhkan tanpa perubahan pendamping karena:

1. `UserAddress` masih memiliki relasi dan fallback ke model lokal.
2. `ProfileController` dan `CartController` masih melakukan eager-load relasi wilayah lokal.
3. Tabel lama `shipping_costs` masih memiliki foreign key ke `provinces` dan `regencies`.
4. Test masih membuat record wilayah lokal sebagai fixture.
5. Package `azishapidin/indoregion` masih terpasang dan dipakai oleh empat model serta seeder lama.

Rekomendasi: hapus keempat model/tabel dalam satu pekerjaan terkoordinasi bersama tabel `shipping_costs` lama, fallback model, seeder IndoRegion, package IndoRegion, test fixture, dan ERD.

## Bukti Pemakaian RajaOngkir

### Sumber Data Wilayah Aktif

`RajaOngkirService` mengambil data langsung dari endpoint:

- `destination/province`
- `destination/city/{provinceId}`
- `destination/district/{cityId}`
- `destination/sub-district/{districtId}`

Data tersebut digunakan oleh:

- `RajaOngkirRegionController`
- form alamat profil
- form checkout
- validasi rantai wilayah
- perhitungan ongkir berdasarkan district

API key dan origin district RajaOngkir pada environment aktif sudah terkonfigurasi.

### Route yang Harus Dipertahankan

Route berikut masih digunakan oleh tampilan, tetapi tidak membaca tabel lokal:

```text
regions.provinces.index
regions.regencies.index
regions.districts.index
regions.villages.index
```

Nama route tetap boleh memakai istilah province/regency/district/village karena route tersebut merupakan proxy ke API RajaOngkir. Route dan `RajaOngkirRegionController` tidak boleh ikut dihapus.

## Kondisi Database Aktif

Audit MySQL `sarana_fittindo` pada 11 Juni 2026:

| Tabel | Jumlah record |
|---|---:|
| `provinces` | 0 |
| `regencies` | 0 |
| `districts` | 0 |
| `villages` | 0 |
| `shipping_costs` | 0 |
| `user_addresses` | 1 |

Alamat aktif tersebut memiliki:

- `region_source`: `rajaongkir`
- seluruh ID wilayah RajaOngkir terisi
- `province_name`, `city_name`, `district_name`, dan `village_name` terisi
- tidak ada snapshot nama wilayah yang `NULL`

`user_addresses` sudah tidak memiliki foreign key ke empat tabel wilayah. Index dengan nama lama seperti `user_addresses_province_id_foreign` masih ada, tetapi hanya berupa index dan bukan constraint.

Foreign key yang masih mengunci tabel wilayah:

- `regencies.province_id` ke `provinces.id`
- `districts.regency_id` ke `regencies.id`
- `villages.district_id` ke `districts.id`
- `shipping_costs.province_id` ke `provinces.id`
- `shipping_costs.regency_id` ke `regencies.id`

## Komponen yang Masih Bergantung pada Model Lokal

### Model `UserAddress`

Relasi lokal yang perlu dihapus:

- `province()`
- `regency()`
- `district()`
- `village()`

Accessor berikut masih mempunyai fallback ke relasi lokal:

- `province_display_name`
- `city_display_name`
- `district_display_name`
- `village_display_name`

Setelah migrasi, accessor harus membaca kolom snapshot secara langsung:

```text
province_name
city_name
district_name
village_name
```

Kolom snapshot tersebut tidak boleh dihapus.

### Controller

Hapus eager-load lokal dari:

- `ProfileController::edit()`
- `CartController::checkoutForm()`
- `CartController::checkout()` saat memilih alamat tersimpan

Validasi dan lookup melalui `RajaOngkirService` harus dipertahankan.

### Test

Fixture lokal berada pada:

- `tests/Feature/ProfileTest.php`
- `tests/Feature/Pelanggan/CheckoutTest.php`

Test harus membuat `user_addresses` dengan ID dan snapshot nama RajaOngkir secara langsung. Test endpoint wilayah tetap menggunakan mock `RajaOngkirService` dan tidak memerlukan tabel lokal.

## Tabel `shipping_costs`

Tabel `shipping_costs` berbeda dari kolom `orders.shipping_cost`.

- `shipping_costs` adalah tabel tarif wilayah lokal lama.
- Tidak ada model `ShippingCost`.
- Tidak ada controller, route, service, view, atau query runtime yang membaca tabel ini.
- Tabel aktif berisi 0 record.
- Ongkir saat ini dihitung oleh RajaOngkir atau dikonfirmasi manual dan disimpan langsung pada `orders`.

Karena foreign key-nya menghalangi penghapusan `provinces` dan `regencies`, tabel `shipping_costs` direkomendasikan ikut dihapus.

Kolom berikut pada `orders` harus tetap dipertahankan:

- `shipping_cost`
- `shipping_cost_status`
- `shipping_cost_source`
- `shipping_origin_district_id`
- `shipping_destination_district_id`
- `shipping_courier_code`
- `shipping_courier_name`
- `shipping_service`
- `shipping_etd`
- `shipping_rate_snapshot`

## Komponen IndoRegion yang Dapat Dibersihkan

Setelah semua referensi runtime dan test dilepas:

1. Hapus model:
   - `app/Models/Province.php`
   - `app/Models/Regency.php`
   - `app/Models/District.php`
   - `app/Models/Village.php`

2. Hapus seeder lama:
   - `IndoRegionSeeder.php`
   - `IndoRegionProvinceSeeder.php`
   - `IndoRegionRegencySeeder.php`
   - `IndoRegionDistrictSeeder.php`
   - `IndoRegionVillageSeeder.php`
   - `WilayahSeeder.php`

3. Hapus dependency Composer:

```bash
composer remove azishapidin/indoregion
```

4. Pertahankan migration historis tahun 2017 agar histori migration lama tetap konsisten.

## Rencana Implementasi

### Fase 1 - Backup dan Audit

1. Buat backup penuh MySQL sebelum perubahan.
2. Catat baseline:
   - jumlah dan isi `user_addresses`
   - jumlah `orders`
   - jumlah `products`
   - total stok produk
   - jumlah `provinces`, `regencies`, `districts`, `villages`, dan `shipping_costs`
3. Pastikan setiap `user_addresses` memiliki:
   - `region_source = rajaongkir`
   - empat ID wilayah
   - empat snapshot nama wilayah
4. Hentikan migration jika ada alamat tanpa snapshot lengkap.
5. Periksa integrasi atau script eksternal di luar repository yang mungkin masih membaca tabel lokal.

Contoh query audit:

```sql
SELECT region_source, COUNT(*)
FROM user_addresses
GROUP BY region_source;

SELECT *
FROM user_addresses
WHERE province_name IS NULL
   OR city_name IS NULL
   OR district_name IS NULL
   OR village_name IS NULL;

SELECT COUNT(*) FROM provinces;
SELECT COUNT(*) FROM regencies;
SELECT COUNT(*) FROM districts;
SELECT COUNT(*) FROM villages;
SELECT COUNT(*) FROM shipping_costs;
```

### Fase 2 - Lepas Ketergantungan Aplikasi

1. Hapus empat relasi wilayah dari `UserAddress`.
2. Ubah accessor display agar hanya memakai kolom snapshot.
3. Hapus eager-load wilayah lokal dari controller profil dan checkout.
4. Pertahankan ID wilayah di `user_addresses` sebagai ID RajaOngkir.
5. Pertahankan snapshot nama wilayah untuk tampilan dan histori.
6. Pertahankan seluruh service, controller, route, cache, dan UI RajaOngkir.

### Fase 3 - Perbarui Test

1. Hapus helper yang menulis ke tabel lokal.
2. Isi fixture alamat dengan ID dan nama wilayah RajaOngkir.
3. Tambahkan test bahwa profil dan checkout dapat menampilkan alamat ketika tabel lokal tidak ada.
4. Tambahkan test bahwa checkout dari alamat tersimpan tetap mengirim district ID RajaOngkir.
5. Tambahkan test bahwa tabel berikut tidak ada setelah migration:
   - `provinces`
   - `regencies`
   - `districts`
   - `villages`
   - `shipping_costs`
6. Pertahankan test mock endpoint RajaOngkir.

### Fase 4 - Migration Penghapusan

Buat satu migration maju baru, misalnya:

```text
YYYY_MM_DD_HHMMSS_drop_legacy_region_tables.php
```

Urutan `up()` wajib mengikuti foreign key:

1. `Schema::dropIfExists('shipping_costs')`
2. `Schema::dropIfExists('villages')`
3. `Schema::dropIfExists('districts')`
4. `Schema::dropIfExists('regencies')`
5. `Schema::dropIfExists('provinces')`

Urutan `down()`:

1. buat kembali `provinces`
2. buat kembali `regencies`
3. buat kembali `districts`
4. buat kembali `villages`
5. buat kembali `shipping_costs`

Method `down()` cukup memulihkan struktur. Pemulihan data lama harus memakai backup karena kelima tabel aktif saat ini kosong.

Jangan memakai:

- `migrate:fresh`
- `migrate:refresh`
- rollback massal
- penghapusan manual seluruh database

Migration historis berikut harus tetap disimpan:

- empat migration IndoRegion tahun 2017
- `2026_04_30_141340_create_user_addresses_table.php`
- `2026_04_30_141341_create_shipping_costs_table.php`
- `2026_06_09_171000_update_user_addresses_for_rajaongkir_regions.php`

Urutan historis tersebut diperlukan agar migration dari database kosong tetap berjalan sebelum migration baru membersihkan tabel lama.

### Fase 5 - Bersihkan Package dan Artefak

1. Hapus empat model lokal.
2. Hapus seeder IndoRegion dan `WilayahSeeder`.
3. Jalankan `composer remove azishapidin/indoregion`.
4. Jalankan `composer dump-autoload`.
5. Regenerasi `output.txt` dan `graph.png`.
6. Pastikan ERD tidak lagi menampilkan Province, Regency, District, Village, atau relasinya.

Index lama pada `user_addresses` dapat dievaluasi terpisah. Index tersebut tidak menghalangi penghapusan tabel dan boleh dipertahankan jika masih bermanfaat untuk pencarian berdasarkan ID RajaOngkir.

### Fase 6 - Verifikasi

Jalankan minimal:

```bash
php artisan test tests/Unit/RajaOngkirServiceTest.php
php artisan test tests/Feature/ProfileTest.php
php artisan test tests/Feature/Pelanggan/CheckoutTest.php
php artisan test tests/Feature/Pelanggan/OrderListTest.php
php artisan test tests/Feature/Admin/ShippingCostConfirmationTest.php
```

Setelah itu jalankan:

```bash
php artisan test
```

Verifikasi manual:

1. Profil dapat menambah dan mengubah alamat.
2. Dropdown wilayah tetap mengambil data RajaOngkir.
3. Alamat tersimpan tetap tampil lengkap.
4. Checkout dengan alamat tersimpan berhasil.
5. Checkout dengan alamat baru berhasil.
6. Perhitungan ongkir otomatis berhasil.
7. Fallback konfirmasi ongkir manual tetap berhasil.
8. Histori pesanan tetap menampilkan snapshot alamat.
9. Daftar produk, stok, gambar, dan item pesanan tidak berubah.

Uji migration pada salinan database:

1. migration maju menghapus kelima tabel lama
2. rollback terisolasi membuat kembali strukturnya
3. migration maju dapat dijalankan lagi
4. fresh migration untuk environment test tetap lulus

## Data yang Tidak Boleh Dihapus

Penghapusan tabel wilayah lokal tidak berarti menghapus data wilayah RajaOngkir yang tersimpan pada transaksi.

Pertahankan:

- seluruh kolom `province_id`, `regency_id`, `district_id`, dan `village_id` pada `user_addresses`
- seluruh kolom snapshot nama pada `user_addresses`
- `region_source`
- seluruh kolom alamat dan pengiriman pada `orders`
- cache dan konfigurasi RajaOngkir
- route serta controller region RajaOngkir
- data produk, gambar produk, stok, cart, wishlist, dan `order_items`

## Kriteria Selesai

- Tidak ada model Province, Regency, District, atau Village.
- Tidak ada relasi/eager-load ke model wilayah lokal.
- Tidak ada seeder atau package IndoRegion.
- Tidak ada query runtime ke tabel wilayah lokal.
- `shipping_costs` lama dan empat tabel wilayah sudah tidak ada.
- Route wilayah RajaOngkir tetap aktif.
- Semua alamat memiliki snapshot nama lengkap.
- Profil, checkout, ongkir, dan histori pesanan lulus test.
- Data produk dan pesanan tidak berubah.
- ERD tidak lagi menampilkan entitas wilayah lokal.

## Risiko dan Rollback

Risiko utama bukan data produk, melainkan alamat lama yang belum memiliki snapshot nama wilayah. Karena itu audit snapshot wajib dilakukan sebelum migration.

Jika rollback diperlukan:

1. rollback hanya migration penghapusan tabel wilayah
2. pulihkan data tabel lama dari backup jika diperlukan
3. kembalikan model, relasi, seeder, dan package IndoRegion hanya jika aplikasi benar-benar harus kembali memakai wilayah lokal
4. jangan menimpa `user_addresses` atau snapshot alamat pada `orders`

Keempat tabel wilayah tidak memiliki relasi langsung ke `products`, `product_images`, atau `order_items`, sehingga penghapusan terkoordinasi tidak boleh mengubah data produk.
