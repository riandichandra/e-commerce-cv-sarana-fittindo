# Planning Audit Seluruh Tabel, Kolom, Model, dan Controller

## Status

Selesai diimplementasikan pada 11 Juni 2026.

Audit dilakukan pada 11 Juni 2026 terhadap:

- database MySQL `sarana_fittindo`
- 28 tabel aktif dan 254 kolom
- migration, model, controller, service, request, route, view, seeder, dan test
- konfigurasi cache, session, queue, Sanctum, serta Spatie Permission
- jumlah record, nilai `NULL`, nilai kosong, variasi nilai, foreign key, dan index

## Hasil Implementasi

### Backup

Backup database penuh sebelum cleanup:

```text
storage/app/backups/sarana_fittindo_before_database_cleanup_20260611_151804.sql
```

Snapshot data khusus:

```text
storage/app/backups/products_specifications_before_cleanup_20260611_151804.tsv
storage/app/backups/payment_methods_icon_before_cleanup_20260611_151804.tsv
storage/app/backups/product_brands_logo_before_cleanup_20260611_151804.tsv
```

### Perubahan yang Selesai

1. Menghapus model `Notification` dan `OrderStatusHistory` yang tidak memiliki tabel.
2. Menghapus relasi `Order::statusHistory()` dan `Promotion::products()` yang mengacu
   ke tabel yang tidak ada.
3. Membersihkan `$fillable` palsu pada Category dan Role.
4. Menghapus properti `$enumStatuses` yang tidak digunakan.
5. Menambahkan input dan tampilan `products.specifications` tanpa menghapus data lama.
6. Menampilkan `orders.notes` pada detail pesanan admin dan pelanggan.
7. Menghapus `product_brands.logo` dan `payment_methods.icon`.
8. Menghapus index biasa `orders_order_number_index` yang menduplikasi unique index.
9. Menambahkan unique index `payments_order_id_unique`.
10. Memperbarui seeder, test, dan ERD.

Migration implementasi:

```text
database/migrations/2026_06_11_153000_cleanup_unused_database_columns_and_indexes.php
```

Migration berhasil dijalankan pada batch 7. Rollback dan migration maju sudah diuji
langsung pada MySQL serta pada database SQLite terpisah.

### Perlindungan Data

| Data | Sebelum | Sesudah |
|---|---:|---:|
| Produk | 22 | 22 |
| Total stok | 1023 | 1023 |
| Gambar produk | 22 | 22 |
| User | 6 | 6 |
| Alamat | 1 | 1 |
| Pesanan | 8 | 8 |
| Item pesanan | 8 | 8 |
| Pembayaran | 8 | 8 |
| Promosi | 1 | 1 |
| Wishlist | 1 | 1 |

Seluruh spesifikasi produk dipertahankan. Tidak ada data produk, transaksi, alamat,
atau pembayaran yang dihapus.

### Verifikasi

- 63 test terfokus lulus dengan 490 assertions.
- Suite penuh menghasilkan 138 test lulus dengan 811 assertions.
- Satu test lama yang tidak terkait cleanup masih gagal pada
  `MarketingListUiTest`, karena test mengharapkan teks `WEEKEND` yang tidak
  ditampilkan oleh halaman promosi.
- Verifikasi browser lokal berhasil untuk form edit spesifikasi dan detail produk.
- Tidak ditemukan error console pada halaman yang diperiksa.

### Komponen yang Sengaja Dipertahankan

Tabel session, queue, Sanctum, permission, cache, dan seluruh snapshot transaksi tidak
dihapus. Komponen tersebut masih menjadi kontrak konfigurasi Laravel/package atau
menyimpan histori dan audit bisnis.

## Prinsip Utama

Kolom yang tidak tampil di halaman belum tentu tidak digunakan. Kolom dapat berfungsi
sebagai:

- foreign key dan penghubung relasi
- snapshot historis transaksi
- audit perubahan status
- pengaman idempotensi agar proses tidak dijalankan dua kali
- token autentikasi atau reset password
- kontrak internal Laravel atau package
- data teknis untuk integrasi RajaOngkir

Karena itu penghapusan hanya boleh dilakukan jika kolom:

1. tidak dibaca maupun ditulis oleh runtime;
2. tidak menjadi bagian relasi, index, validasi, cast, atau kontrak package;
3. tidak diperlukan untuk histori dan audit;
4. tidak menyimpan data bisnis yang perlu dipertahankan;
5. memiliki migration `down()` dan backup yang sudah diuji.

## Ringkasan Kesimpulan

### Kandidat Penghapusan Kuat

| Komponen | Kondisi | Rekomendasi |
|---|---|---|
| `product_brands.logo` | 2 dari 2 record `NULL`; tidak ada input, upload, tampilan, atau pembacaan runtime | Hapus kolom dan keluarkan dari `$fillable` |
| `payment_methods.icon` | Tidak ditampilkan; controller selalu mengisi `NULL`; 4 record lama masih berisi `mdi:bank` | Backup nilai, perbarui seeder, lalu hapus kolom |
| `orders_order_number_index` | Index biasa menduplikasi unique index pada `order_number` | Hapus index biasa, pertahankan unique index |

### Cleanup Kode Tanpa Menghapus Data

| Komponen | Masalah | Rekomendasi |
|---|---|---|
| `App\Models\Notification` | Tidak memiliki tabel dan tidak dipakai aplikasi; test memakai facade Laravel, bukan model ini | Hapus model |
| `App\Models\OrderStatusHistory` | Tidak memiliki tabel dan tidak memiliki pemakaian runtime | Hapus model dan relasi `Order::statusHistory()` |
| `Promotion::products()` | Mengacu ke pivot `promotion_product` yang tidak ada dan tidak pernah dipakai | Hapus relasi serta import `BelongsToMany` |
| `ProductCategory::$fillable['icon']` | Kolom `icon` tidak ada pada tabel kategori | Hapus dari `$fillable` |
| `Role::$fillable` | `display_name` dan `description` tidak ada pada tabel `roles` | Hapus dua atribut tersebut |
| `$enumStatuses` | Properti pada `Order` dan `Payment` tidak pernah dibaca | Hapus atau ubah menjadi constant/enum jika akan dipakai validasi |

### Jangan Langsung Dihapus

| Komponen | Temuan | Keputusan |
|---|---|---|
| `products.specifications` | Tidak ada input dan tidak ditampilkan, tetapi 8 produk menyimpan spesifikasi bernilai bisnis | Jangan hapus. Tambahkan pengelolaan/tampilan atau migrasikan isinya secara eksplisit setelah persetujuan |
| `orders.notes` | Form checkout menerima catatan, tetapi catatan pesanan belum ditampilkan | Lebih tepat ditampilkan pada detail pesanan admin dan pelanggan |
| snapshot promosi pada `orders` | Sebagian tidak ditampilkan langsung | Pertahankan agar histori tidak berubah saat promosi diedit |
| snapshot RajaOngkir pada `orders` | Sebagian hanya untuk audit teknis | Pertahankan untuk bukti tarif, debugging, dan histori pengiriman |
| audit pembatalan, pengiriman, penyelesaian, dan stok | Banyak nilai saat ini `NULL` | Pertahankan; nilai terisi sesuai siklus status |
| `products.deleted_at` | Seluruh data saat ini `NULL` | Pertahankan karena model menggunakan `SoftDeletes` |
| timestamps | Tidak semuanya tampil di UI | Pertahankan untuk urutan, audit, Eloquent, dan laporan |

## Kondisi Database Aktif

Jumlah record yang dihitung secara aktual:

| Tabel | Record | Keterangan |
|---|---:|---|
| `cache` | 163 | Aktif dipakai RajaOngkir dan cache aplikasi |
| `cache_locks` | 0 | Kontrak database cache |
| `cart_items` | 0 | Aktif |
| `carts` | 1 | Aktif |
| `failed_jobs` | 0 | Infrastruktur queue |
| `job_batches` | 0 | Infrastruktur queue batch |
| `jobs` | 0 | Infrastruktur queue |
| `migrations` | 37 | Wajib |
| `model_has_permissions` | 0 | Package Spatie |
| `model_has_roles` | 6 | Aktif untuk role user |
| `order_items` | 8 | Data transaksi |
| `orders` | 8 | Data transaksi |
| `password_reset_tokens` | 0 | Infrastruktur autentikasi |
| `payment_methods` | 5 | Aktif |
| `payments` | 8 | Data transaksi |
| `permissions` | 0 | Package Spatie |
| `personal_access_tokens` | 0 | Sanctum/API |
| `product_brands` | 2 | Data produk |
| `product_categories` | 4 | Data produk |
| `product_images` | 22 | Data produk |
| `products` | 22 | Data produk |
| `promotions` | 1 | Aktif |
| `role_has_permissions` | 0 | Package Spatie |
| `roles` | 5 | Aktif |
| `sessions` | 0 | Infrastruktur session |
| `user_addresses` | 1 | Data pelanggan |
| `users` | 6 | Aktif |
| `wishlists` | 1 | Aktif |

## Audit Per Tabel dan Kolom

### `cache`

Kolom: `key`, `value`, `expiration`.

Keputusan: pertahankan seluruhnya. `CACHE_STORE=database` dan tabel berisi 150 record.
Nama kolom tidak perlu muncul di source aplikasi karena dibaca oleh Laravel Cache.

### `cache_locks`

Kolom: `key`, `owner`, `expiration`.

Keputusan: pertahankan seluruhnya sebagai kontrak database cache dan atomic lock.

### `carts`

Kolom: `id`, `user_id`, `created_at`, `updated_at`.

Keputusan: pertahankan seluruhnya. `user_id` unik memastikan satu keranjang per user.

### `cart_items`

Kolom: `id`, `cart_id`, `product_id`, `quantity`, `created_at`, `updated_at`.

Keputusan: pertahankan seluruhnya. Unique index `cart_id, product_id` mencegah produk
yang sama menjadi dua baris dalam satu keranjang.

### `failed_jobs`

Kolom: `id`, `uuid`, `connection`, `queue`, `payload`, `exception`, `failed_at`.

Keputusan saat ini: pertahankan. Environment aktif memakai `QUEUE_CONNECTION=sync`,
tetapi `.env.example` memakai `database`. Tabel hanya boleh dihapus jika seluruh
environment dipastikan memakai queue `sync` dan tidak akan menjalankan worker.

### `job_batches`

Kolom: `id`, `name`, `total_jobs`, `pending_jobs`, `failed_jobs`,
`failed_job_ids`, `options`, `cancelled_at`, `created_at`, `finished_at`.

Keputusan saat ini: pertahankan sebagai kontrak batch queue. Kandidat penghapusan
bersyarat bersama seluruh subsistem queue database, bukan per kolom.

### `jobs`

Kolom: `id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`,
`created_at`.

Keputusan saat ini: pertahankan. Jangan menghapus sebagian kolom karena seluruhnya
dibutuhkan driver queue database.

### `migrations`

Kolom: `id`, `migration`, `batch`.

Keputusan: wajib dipertahankan. Laravel memakai tabel ini untuk status migration dan
rollback.

### `permissions`

Kolom: `id`, `name`, `guard_name`, `created_at`, `updated_at`.

Keputusan saat ini: pertahankan. Record masih kosong dan aplikasi baru memakai role,
tetapi tabel merupakan kontrak package Spatie Permission. Penghapusan hanya boleh
dilakukan jika fitur permission dihapus dari konfigurasi/package atau Spatie diganti.

### `model_has_permissions`

Kolom: `permission_id`, `model_type`, `model_id`.

Keputusan saat ini: pertahankan bersama package Spatie. Jangan menghapus kolom pivot
secara parsial.

### `role_has_permissions`

Kolom: `permission_id`, `role_id`.

Keputusan saat ini: pertahankan bersama package Spatie.

### `roles`

Kolom: `id`, `name`, `guard_name`, `created_at`, `updated_at`.

Keputusan: pertahankan seluruh kolom. Terdapat 5 role aktif dan `model_has_roles`
berisi 6 assignment. Bersihkan model lokal dari `$fillable` palsu `display_name` dan
`description`.

### `model_has_roles`

Kolom: `role_id`, `model_type`, `model_id`.

Keputusan: pertahankan seluruhnya. Tabel ini aktif menghubungkan user dengan role.

### `password_reset_tokens`

Kolom: `email`, `token`, `created_at`.

Keputusan: pertahankan seluruhnya. Walaupun kosong saat audit, route lupa password dan
test reset password aktif menggunakannya.

### `personal_access_tokens`

Kolom: `id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`,
`last_used_at`, `expires_at`, `created_at`, `updated_at`.

Keputusan saat ini: pertahankan. Tabel kosong, tetapi `User` memakai `HasApiTokens`
dan route `/api/user` memakai `auth:sanctum`.

Penghapusan bersyarat harus sekaligus:

1. menghapus route API yang tidak diperlukan;
2. menghapus `HasApiTokens`;
3. menghapus dependency Sanctum dan konfigurasinya;
4. menambah migration penghapusan tabel.

### `sessions`

Kolom: `id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`.

Environment aktif memakai `SESSION_DRIVER=file`, sedangkan `.env.example` memakai
`database`.

Keputusan saat ini: pertahankan. Tabel dapat dihapus hanya setelah standar session
seluruh environment ditetapkan ke `file` dan `.env.example` diperbarui.

### `users`

Kolom: `id`, `name`, `email`, `email_verified_at`, `password`, `phone`,
`is_active`, `remember_token`, `created_at`, `updated_at`.

Keputusan: pertahankan seluruhnya.

- `email_verified_at` ditampilkan dan digunakan proses verifikasi.
- `remember_token` merupakan kontrak autentikasi walaupun seluruh nilai saat audit
  masih `NULL`.
- `phone` digunakan registrasi, profil, dan data pelanggan.
- `is_active` digunakan filter dan pembatasan user.

### `user_addresses`

Kolom: `id`, `user_id`, `label`, `receiver_name`, `receiver_phone`,
`full_address`, `province_id`, `regency_id`, `district_id`, `village_id`,
`province_name`, `city_name`, `district_name`, `village_name`, `region_source`,
`postal_code`, `is_main`, `created_at`, `updated_at`.

Keputusan: pertahankan seluruhnya.

- empat ID wilayah diperlukan untuk rantai wilayah dan quote RajaOngkir;
- empat nama wilayah merupakan snapshot untuk tampilan tanpa tabel lokal;
- `region_source` menyimpan provenance provider;
- data penerima dan alamat dipakai profil serta checkout;
- `is_main` menentukan alamat utama.

Index wilayah memakai nama lama berakhiran `_foreign`, tetapi saat ini hanya index,
bukan foreign key. Nama index dapat dirapikan terpisah. Penghapusan index harus
didahului pemeriksaan query dan `EXPLAIN`.

### `product_categories`

Kolom: `id`, `name`, `slug`, `description`, `is_active`, `created_at`,
`updated_at`.

Keputusan: pertahankan seluruh kolom. Hapus hanya atribut `icon` dari `$fillable`
model karena kolom tersebut tidak ada.

### `product_brands`

Kolom: `id`, `name`, `slug`, `description`, `logo`, `is_active`, `created_at`,
`updated_at`.

Keputusan:

- pertahankan semua kecuali `logo`;
- `logo` adalah kandidat penghapusan kuat karena seluruh record `NULL` dan tidak ada
  controller, form, upload, atau view yang memakainya.

### `products`

Kolom: `id`, `category_id`, `brand_id`, `name`, `slug`, `description`, `price`,
`stock`, `status`, `weight`, `thickness`, `dimensions`, `specifications`,
`is_featured`, `is_active`, `created_at`, `updated_at`, `deleted_at`.

Keputusan:

- pertahankan seluruh kolom selain `specifications` yang perlu keputusan produk;
- `status` memang dapat diturunkan dari stok, tetapi dipakai luas untuk filter dan
  model menjaga sinkronisasinya;
- `deleted_at` wajib karena model menggunakan `SoftDeletes`;
- `weight` wajib untuk perhitungan ongkir;
- `is_featured` dan `is_active` memiliki fungsi berbeda;
- `specifications` tidak dikelola controller/form dan tidak ditampilkan, tetapi
  berisi data pada 8 produk.

Rekomendasi untuk `specifications`: jangan dihapus. Pilihan yang lebih aman adalah
menambahkan editor spesifikasi pada form produk dan menampilkannya pada detail produk.
Jika bisnis tetap ingin menghapusnya, export delapan nilai JSON terlebih dahulu dan
migrasikan informasi penting ke `description`.

### `product_images`

Kolom: `id`, `product_id`, `image_path`, `is_primary`, `sort_order`,
`created_at`, `updated_at`.

Keputusan: pertahankan seluruhnya. `is_primary` dan `sort_order` tetap dipakai walau
data seed saat ini memiliki variasi terbatas.

### `wishlists`

Kolom: `id`, `user_id`, `product_id`, `created_at`, `updated_at`.

Keputusan: pertahankan seluruhnya. Unique index `user_id, product_id` menjaga agar
wishlist tidak duplikat.

### `payment_methods`

Kolom: `id`, `name`, `code`, `account_number`, `account_name`, `bank_name`,
`instructions`, `icon`, `is_active`, `sort_order`, `created_at`, `updated_at`.

Keputusan:

- pertahankan semua kecuali `icon`;
- `code` tetap dipakai sebagai identifier stabil dan ditampilkan;
- `instructions` dipakai pada alur pembayaran;
- `sort_order` dipakai query dan tampilan;
- `icon` tidak pernah ditampilkan dan controller selalu menulis `NULL`.

Sebelum menghapus `icon`, perbarui `PaymentMethodSeeder` dan `DummyDataSeeder`.
Empat record lama berisi `mdi:bank`, sehingga backup tetap wajib walaupun nilainya
tidak digunakan.

### `payments`

Kolom: `id`, `order_id`, `payment_method_id`, `amount`, `proof_image`,
`transfer_date`, `sender_name`, `status`, `verified_by`, `verified_at`,
`rejection_reason`, `notes`, `created_at`, `updated_at`.

Keputusan: pertahankan seluruhnya. Semua kolom membentuk bukti pembayaran, verifikasi,
penolakan, dan laporan keuangan.

Perlu evaluasi constraint tambahan: relasi model adalah `HasOne`, tetapi database
belum memberi unique index pada `payments.order_id`. Pastikan tidak ada order dengan
lebih dari satu payment, lalu pertimbangkan unique index untuk menjaga integritas.

### `promotions`

Kolom: `id`, `name`, `code`, `description`, `type`, `value`, `min_purchase`,
`max_discount`, `start_date`, `end_date`, `is_active`, `banner_image`,
`banner_url`, `created_by`, `created_at`, `updated_at`.

Keputusan: pertahankan seluruhnya. Seluruh kolom dikelola form, perhitungan diskon,
dashboard, atau histori.

Cleanup kode: hapus relasi `products()` karena pivot `promotion_product` tidak ada dan
fitur promosi saat ini berlaku umum berdasarkan subtotal, bukan assignment produk.

### `orders`

Kolom inti:

- `id`, `user_id`, `order_number`, `status`
- `subtotal`, `discount_amount`, `shipping_cost`, `total_amount`
- `payment_method_id`, `created_at`, `updated_at`

Snapshot promosi:

- `promotion_id`, `promotion_code`, `promotion_name`, `promotion_type`,
  `promotion_value`

Snapshot ongkir:

- `shipping_cost_status`, `shipping_cost_source`
- `shipping_origin_district_id`, `shipping_destination_district_id`
- `shipping_weight_gram`
- `shipping_courier_code`, `shipping_courier_name`
- `shipping_service`, `shipping_service_description`, `shipping_etd`
- `shipping_rate_snapshot`
- `shipping_cost_confirmed_at`, `shipping_cost_confirmed_by`

Snapshot alamat:

- `shipping_name`, `shipping_phone`, `shipping_address`
- `shipping_province`, `shipping_city`, `shipping_district`, `shipping_village`
- `shipping_postal_code`

Audit status:

- `notes`
- `cancelled_by`, `cancellation_reason`, `cancelled_at`
- `received_image`
- `shipped_at`, `completed_at`, `auto_completed_at`
- `completion_source`, `completion_notes`
- `stock_restored_at`, `stock_restored_by`

Keputusan: tidak ada kolom order yang direkomendasikan untuk langsung dihapus.
Tabel ini sengaja terdenormalisasi agar histori transaksi tidak berubah saat user,
alamat, produk, promosi, rekening, atau tarif RajaOngkir berubah.

Catatan:

- `orders.notes` diterima saat checkout tetapi belum ditampilkan. Tambahkan ke halaman
  detail admin dan pelanggan, bukan menghapus input pelanggan.
- `shipping_courier_code`, `shipping_service_description`, dan
  `shipping_rate_snapshot` tidak semuanya ditampilkan, tetapi merupakan snapshot
  teknis untuk audit tarif.
- `stock_restored_at` mencegah stok dikembalikan dua kali dan tidak boleh dihapus.
- `shipped_at` diperlukan command penyelesaian otomatis.
- `auto_completed_at`, `completion_source`, dan `completion_notes` membedakan proses
  manual dengan otomatis.

Index `orders_order_number_index` redundan karena sudah ada
`orders_order_number_unique`. Hapus index biasa saja.

Index `orders_status_index` juga merupakan prefix dari index
`orders_status_shipped_at_index`, tetapi penghapusannya harus didahului `EXPLAIN`
untuk query daftar pesanan dan dashboard.

### `order_items`

Kolom: `id`, `order_id`, `product_id`, `product_name`, `product_price`,
`quantity`, `subtotal`, `created_at`, `updated_at`.

Keputusan: pertahankan seluruhnya. Nama, harga, dan subtotal adalah snapshot transaksi,
bukan duplikasi yang boleh dihitung ulang dari produk aktif.

## Ketidaksesuaian Model dan Schema

### Model Tanpa Tabel

1. `App\Models\Notification` mengharapkan tabel `notifications`, tetapi tabel tidak
   ada dan model tidak dipakai.
2. `App\Models\OrderStatusHistory` mengharapkan `order_status_histories`, tetapi tabel
   tidak ada dan model tidak dipakai.

Test reset password yang menyebut `Notification` memakai
`Illuminate\Support\Facades\Notification`, bukan `App\Models\Notification`.

### Atribut Model Tanpa Kolom

1. `ProductCategory::$fillable` memuat `icon`.
2. `Role::$fillable` memuat `display_name` dan `description`.

### Relasi ke Tabel yang Tidak Ada

1. `Promotion::products()` mengacu ke `promotion_product`.
2. `Order::statusHistory()` mengacu ke `order_status_histories`.

Cleanup ini harus dilakukan sebelum regenerasi ERD agar diagram sesuai database.

## Tabel Infrastruktur Bersyarat

### Session

Environment aktif:

```text
SESSION_DRIVER=file
```

Template environment:

```text
SESSION_DRIVER=database
```

Karena konfigurasi belum konsisten, `sessions` belum aman dihapus.

### Queue

Environment aktif:

```text
QUEUE_CONNECTION=sync
```

Template environment:

```text
QUEUE_CONNECTION=database
```

Tidak ditemukan job `ShouldQueue` atau dispatch aplikasi, tetapi `jobs`,
`job_batches`, dan `failed_jobs` tetap harus dipertahankan sampai keputusan queue
deployment dibuat.

### Sanctum

`personal_access_tokens` kosong, tetapi masih ada:

- trait `HasApiTokens` pada `User`;
- route `/api/user`;
- middleware `auth:sanctum`;
- dependency Sanctum.

Cleanup hanya boleh dilakukan sebagai penghapusan fitur API/Sanctum yang utuh.

### Spatie Permission

Aplikasi aktif memakai role, belum memakai permission. Tabel permission kosong, tetapi
masih menjadi kontrak package. Prioritaskan stabilitas daripada menghemat tiga tabel
kecil.

## Rencana Implementasi

### Fase 1 - Backup dan Baseline

1. Buat dump database penuh sebelum perubahan.
2. Export terpisah:
   - `products.id`, `products.name`, `products.specifications`
   - `payment_methods.id`, `payment_methods.name`, `payment_methods.icon`
   - `product_brands.id`, `product_brands.name`, `product_brands.logo`
3. Catat checksum dan jumlah:
   - produk, total stok, gambar produk
   - user, alamat, role
   - order, order item, payment
   - promotion, cart, wishlist
4. Catat jumlah `NULL`, distinct value, foreign key, dan index kandidat.
5. Simpan hasil baseline sebagai test atau laporan yang dapat dibandingkan sesudah
   migration.

### Fase 2 - Cleanup Kode Tanpa Perubahan Database

1. Hapus `App\Models\Notification`.
2. Hapus `App\Models\OrderStatusHistory`.
3. Hapus `Order::statusHistory()` dan import yang tidak diperlukan.
4. Hapus `Promotion::products()` dan import `BelongsToMany`.
5. Hapus `icon` dari `$fillable` `ProductCategory`.
6. Hapus `display_name` dan `description` dari `$fillable` `Role`.
7. Hapus `$enumStatuses` yang tidak digunakan atau ganti dengan enum/constant resmi.
8. Tambahkan test agar model dan relasi tidak lagi mengacu ke tabel yang tidak ada.

### Fase 3 - Perbaiki Data yang Disimpan tetapi Tidak Ditampilkan

1. Tampilkan `orders.notes` pada detail order admin dan pelanggan.
2. Pilih arah `products.specifications`:
   - rekomendasi: tambah input terstruktur dan tampilkan di detail produk;
   - alternatif: migrasikan ke deskripsi, export JSON, lalu hapus kolom.
3. Pastikan informasi snapshot ongkir yang memang dibutuhkan operasional dapat dilihat
   admin tanpa menampilkan JSON mentah.

Fase ini harus selesai sebelum menganggap suatu data tidak berguna.

### Fase 4 - Hapus Kolom Kandidat Kuat

Buat migration baru, jangan mengubah migration historis.

Urutan:

1. perbarui model, controller, seeder, factory, dan test;
2. guard migration memastikan `product_brands.logo` seluruhnya `NULL`;
3. export nilai `payment_methods.icon`;
4. hapus `product_brands.logo`;
5. hapus `payment_methods.icon`;
6. hapus `orders_order_number_index`;
7. pertahankan `orders_order_number_unique`.

`down()` harus membuat kembali kedua kolom dan index biasa. Nilai data yang sudah
dihapus tidak dapat dipulihkan hanya oleh schema rollback, sehingga dump/export tetap
menjadi sumber pemulihan data.

### Fase 5 - Evaluasi Index dan Constraint

1. Jalankan `EXPLAIN` untuk query order berdasarkan status sebelum menilai
   `orders_status_index`.
2. Audit query wilayah sebelum mengganti atau menghapus index ID pada
   `user_addresses`.
3. Periksa duplikasi `payments.order_id`.
4. Jika selalu satu payment per order, tambahkan unique index pada
   `payments.order_id`.
5. Periksa aturan satu gambar utama per produk dan satu alamat utama per user pada
   level aplikasi/test.

### Fase 6 - Keputusan Infrastruktur Opsional

Fase ini terpisah dari cleanup bisnis.

1. Tentukan driver session untuk semua environment.
2. Tentukan strategi queue sekarang dan ke depan.
3. Tentukan apakah endpoint API/Sanctum dipertahankan.
4. Tentukan apakah permission level granular akan digunakan.
5. Hapus tabel package/framework hanya setelah konfigurasi, route, trait, dependency,
   dan deployment sudah konsisten.

### Fase 7 - Verifikasi

Minimal jalankan:

```bash
php artisan migrate:fresh --env=testing
php artisan test tests/Feature/Admin/ProductListTest.php
php artisan test tests/Feature/Pelanggan/ProductDetailPageTest.php
php artisan test tests/Feature/Pelanggan/CheckoutTest.php
php artisan test tests/Feature/Pelanggan/OrderListTest.php
php artisan test tests/Feature/Admin/PaymentRejectionTest.php
php artisan test tests/Feature/Admin/ShippingCostConfirmationTest.php
php artisan test tests/Feature/ProfileTest.php
php artisan test
```

Verifikasi tambahan:

1. tambah/edit kategori, merek, produk, dan rekening;
2. detail produk tetap lengkap;
3. cart dan wishlist tetap bekerja;
4. checkout dengan dan tanpa promosi berhasil;
5. quote RajaOngkir dan fallback admin berhasil;
6. upload serta verifikasi pembayaran berhasil;
7. pembatalan tidak mengembalikan stok dua kali;
8. penyelesaian manual dan otomatis berhasil;
9. laporan GM dan Direktur tetap konsisten;
10. jumlah produk, stok, gambar, order item, dan payment sama dengan baseline;
11. fresh migration dan rollback migration cleanup lulus;
12. ERD diregenerasi dan tidak memuat model/relasi palsu.

## Guard Migration yang Wajib

Migration harus berhenti dengan exception jika:

- `product_brands.logo` masih memiliki data;
- export `payment_methods.icon` belum tersedia;
- ada model/controller/view/seeder yang masih menyebut kolom yang akan dihapus;
- jumlah produk, stok, gambar, order item, atau payment berubah di luar ekspektasi;
- ditemukan lebih dari satu payment per order sebelum unique constraint ditambahkan.

## Data yang Tidak Boleh Hilang

- seluruh produk, stok, deskripsi, spesifikasi, dan gambar produk;
- kategori serta merek yang masih direferensikan;
- alamat dan snapshot wilayah RajaOngkir;
- order, order item, payment, bukti pembayaran, dan bukti penerimaan;
- snapshot harga, promosi, alamat, dan ongkir;
- audit pembatalan, pengembalian stok, pengiriman, dan penyelesaian;
- user, role, dan assignment role;
- cache RajaOngkir yang masih valid tidak wajib dibackup, tetapi tabel cache harus
  tetap tersedia selama driver database digunakan.

## Kriteria Selesai

- tidak ada model atau relasi yang mengacu ke tabel yang tidak ada;
- tidak ada `$fillable` yang mengacu ke kolom yang tidak ada;
- kolom yang dihapus memiliki bukti tidak digunakan dan backup;
- data spesifikasi produk tidak hilang;
- catatan pesanan yang dikumpulkan dapat dilihat kembali;
- tabel framework/package tetap konsisten dengan konfigurasi;
- index redundan dibersihkan tanpa menurunkan performa;
- seluruh migration, rollback, test, dan ERD lulus;
- jumlah data bisnis sama dengan baseline.

## Prioritas Rekomendasi

1. Kerjakan cleanup model/relasi palsu terlebih dahulu.
2. Tampilkan `orders.notes` dan putuskan UI untuk `products.specifications`.
3. Hapus `product_brands.logo`, `payment_methods.icon`, dan duplicate index order dalam
   satu migration kecil yang teruji.
4. Tunda penghapusan tabel session, queue, token, dan permission sampai keputusan
   arsitektur deployment dibuat.
5. Jangan menggabungkan seluruh perubahan dalam satu migration besar.
