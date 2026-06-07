# Planning Implementasi Perbaikan Tampilan List Produk Admin

## Ringkasan Permintaan

Halaman list produk pada admin perlu dibuat lebih rapi, enak dilihat, dan lebih nyaman dipakai untuk memantau produk.

Permintaan khusus:

- Rapikan tampilan list produk admin.
- Ganti semua simbol `#` yang relevan pada list produk menjadi `No.`

Fokus utama file:

- `resources/views/admin/products/index.blade.php`

## Ringkasan Analisis Codebase

Halaman produk admin menggunakan struktur Laravel Blade dengan layout admin.

File yang terkait langsung:

- Controller: `app/Http/Controllers/Admin/ProductController.php`
- View list: `resources/views/admin/products/index.blade.php`
- View create: `resources/views/admin/products/create.blade.php`
- View edit: `resources/views/admin/products/edit.blade.php`
- Partial form: `resources/views/admin/products/partials/form.blade.php`
- Layout admin: `resources/views/layouts/admin/layout.blade.php`
- Navigasi admin: `resources/views/layouts/admin/navigation.blade.php`

Route produk admin berada di:

- `routes/web.php`

Dengan resource route:

```php
Route::resource('products', Admin\ProductController::class)->except(['show', 'destroy']);
```

Halaman list produk saat ini memakai:

```php
$products = Product::with(['category', 'brand'])
    ->latest()
    ->paginate(10)
    ->withQueryString();
```

Catatan:

- Relasi `images` belum ikut di-load pada list produk.
- Padahal tampilan list produk akan lebih jelas jika ada thumbnail gambar produk.

## Ringkasan Struktur Database Terkait Produk

### `products`

Migration awal:

- `database/migrations/2026_04_30_141259_create_products_table.php`

Migration update stok/status:

- `database/migrations/2026_06_02_000000_update_products_stock_and_status.php`

Kolom penting:

- `id`
- `category_id`
- `brand_id`
- `name`
- `slug`
- `description`
- `price`
- `stock`
- `status`
- `weight`
- `thickness`
- `dimensions`
- `specifications`
- `is_featured`
- `is_active`
- `created_at`
- `updated_at`
- `deleted_at`

Catatan:

- Kolom `stock` sekarang berupa unsigned integer, bukan boolean.
- Kolom `status` berisi enum:
  - `tersedia`
  - `tidak tersedia`
- Model `Product` otomatis sinkronkan `status` dari `stock` saat saving.

### `product_categories`

Migration:

- `database/migrations/2026_04_30_141255_create_product_categories_table.php`

Kolom penting:

- `id`
- `name`
- `slug`
- `description`
- `is_active`

Relasi:

- `Product belongsTo ProductCategory`
- `ProductCategory hasMany Product`

### `product_brands`

Migration:

- `database/migrations/2026_04_30_141257_create_product_brands_table.php`

Kolom penting:

- `id`
- `name`
- `slug`
- `description`
- `logo`
- `is_active`

Relasi:

- `Product belongsTo ProductBrand`
- `ProductBrand hasMany Product`

### `product_images`

Migration:

- `database/migrations/2026_04_30_141300_create_product_images_table.php`

Kolom penting:

- `id`
- `product_id`
- `image_path`
- `is_primary`
- `sort_order`

Relasi:

- `Product hasMany ProductImage`
- `ProductImage belongsTo Product`

Catatan:

- Model `Product` punya relasi `images()` yang diurutkan berdasarkan `sort_order`.
- Model `Product` punya accessor `primary_image`, tetapi accessor ini melakukan query baru.
- Untuk list admin, lebih baik controller eager-load `images`, lalu view mengambil primary image dari collection:

```php
$image = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
```

## Kondisi Tampilan List Produk Saat Ini

File:

- `resources/views/admin/products/index.blade.php`

Masalah saat ini:

1. Header tabel masih memakai simbol `#`.
2. Tabel terlihat polos dengan background pink penuh `#FFF1F3`.
3. Produk hanya tampil sebagai teks nama, belum ada thumbnail gambar.
4. Status produk hanya menampilkan status stok (`tersedia`/`tidak tersedia`), belum memperlihatkan active/nonaktif.
5. Informasi stok masih berupa angka polos tanpa konteks.
6. Tombol aksi hanya `EDIT` berupa tombol teks, belum dibuat lebih ringkas dan scan-friendly.
7. Tidak ada ringkasan jumlah produk pada halaman.
8. Empty state masih sederhana.
9. Layout action header `ADD PRODUCT`, `CATEGORIES`, `BRANDS` cukup fungsional, tetapi bisa dibuat lebih rapi dan responsif.
10. Table belum punya visual hierarchy yang kuat antara produk, metadata, harga, stok, dan aksi.

## Temuan Simbol `#`

Pencarian simbol `#` di area admin menemukan beberapa penggunaan.

Untuk halaman produk admin:

- `resources/views/admin/products/index.blade.php`
  - Header kolom nomor saat ini: `<th>#</th>`

Di halaman admin lain juga ada simbol `#` sebagai header nomor:

- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/payment-methods/index.blade.php`
- `resources/views/admin/pending-shipping-costs/index.blade.php`
- `resources/views/admin/orders/show.blade.php`
- `resources/views/admin/orders/index.blade.php`
- `resources/views/admin/categories/index.blade.php`
- `resources/views/admin/payments/index.blade.php`
- `resources/views/admin/brands/index.blade.php`

Namun karena permintaan saat ini spesifik pada tampilan list produk admin, scope utama adalah mengganti `#` menjadi `No.` di `resources/views/admin/products/index.blade.php`.

Catatan lanjutan:

- Jika ingin konsistensi seluruh admin, penggantian `#` menjadi `No.` bisa dibuat sebagai pekerjaan lanjutan lintas halaman tabel admin.
- Jangan mengganti `#` yang merupakan bagian dari kode warna Tailwind/CSS seperti `bg-[#FFF1F3]`.

## Tujuan Perubahan

Target halaman list produk admin:

1. List produk lebih rapi dan profesional.
2. Admin lebih cepat mengenali produk dari thumbnail.
3. Kolom nomor memakai label `No.`, bukan `#`.
4. Status stok dan status aktif lebih mudah dibaca.
5. Harga, stok, kategori, dan merek punya hierarchy visual yang jelas.
6. Tabel tetap nyaman dipakai pada data banyak dan layar admin.
7. Pagination tetap berfungsi.
8. Tidak mengubah flow create/edit produk.

## Scope Implementasi

File yang disarankan diubah:

- `app/Http/Controllers/Admin/ProductController.php`
- `resources/views/admin/products/index.blade.php`

File opsional:

- `tests/Feature/Admin/ProductListTest.php`

Tidak termasuk scope:

- Mengubah struktur database.
- Mengubah form tambah/edit produk.
- Menambah fitur delete produk.
- Menambah fitur pencarian/filter baru, kecuali nanti diminta.
- Mengubah seluruh tabel admin lain yang juga punya simbol `#`.

## Rencana Perubahan Controller

File:

- `app/Http/Controllers/Admin/ProductController.php`

Rencana:

1. Tambahkan eager load `images` pada query produk:

```php
$products = Product::with(['category', 'brand', 'images'])
    ->latest()
    ->paginate(10)
    ->withQueryString();
```

Alasan:

- View bisa menampilkan thumbnail produk tanpa N+1 query.
- Gambar utama bisa dipilih dari collection yang sudah dimuat.

2. Jika ingin ringkasan kecil pada halaman, tambahkan query count:

```php
$totalProducts = Product::count();
$activeProducts = Product::where('is_active', true)->count();
$availableProducts = Product::where('status', Product::STATUS_AVAILABLE)->count();
$outOfStockProducts = Product::where('stock', 0)->count();
```

Catatan:

- Ringkasan count opsional.
- Jika ingin scope kecil, cukup ubah eager load `images` dan view.

## Rencana Tampilan View

File:

- `resources/views/admin/products/index.blade.php`

### Header Halaman

Pertahankan breadcrumb, tetapi bisa dirapikan:

- `ADMIN > PRODUCTS`
- Judul halaman `Produk`
- Tombol:
  - Tambah Produk
  - Kategori
  - Merek

Rencana style:

- Header responsif.
- Tombol tetap memakai component `x-button` yang sudah ada agar konsisten.
- Jika ruang sempit, tombol turun ke baris berikutnya.

### Container List

Ganti container pink penuh menjadi card putih atau pink sangat lembut dengan border.

Contoh arah:

```blade
<div class="rounded-lg border border-[#f2c8d0] bg-white shadow-sm">
```

Header card:

- Judul: `Daftar Produk`
- Subtext: `Kelola produk, stok, harga, dan status tampil di katalog.`
- Bisa tambahkan total produk pada sisi kanan jika data count tersedia.

### Tabel

Kolom yang disarankan:

1. `No.`
2. `Produk`
3. `Kategori / Merek`
4. `Harga`
5. `Stok`
6. `Status`
7. `Aksi`

Perubahan penting:

- Kolom `#` diganti menjadi `No.`
- Kolom `Nama`, `Kategori`, dan `Merek` bisa digabung agar tabel lebih compact.
- Kolom produk menampilkan:
  - thumbnail gambar
  - nama produk
  - slug atau dimensi/ketebalan jika tersedia
- Kolom kategori/merek menampilkan dua baris:
  - kategori
  - merek
- Kolom stok tampil dengan badge atau progress ringan:
  - `0 stok`
  - `12 stok`
- Kolom status menampilkan dua badge:
  - status stok: `Tersedia` / `Tidak tersedia`
  - status aktif: `Aktif` / `Nonaktif`
- Kolom aksi memakai tombol icon edit yang tetap jelas.

### Thumbnail Produk

Rencana:

```blade
@php
    $image = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
@endphp
```

Jika gambar ada:

```blade
<img src="{{ asset('storage/' . $image->image_path) }}" alt="{{ $product->name }}">
```

Jika gambar kosong:

- Tampilkan placeholder kotak kecil dengan icon `mdi:image-off-outline`.

Style:

- Ukuran `h-14 w-14`
- `rounded-md`
- `object-cover`
- background abu muda

### Badge Status

Status stok:

- `tersedia`: hijau lembut
- `tidak tersedia`: merah/abu lembut

Status aktif katalog:

- `is_active = true`: biru/hijau lembut dengan label `Aktif`
- `is_active = false`: abu dengan label `Nonaktif`

Produk unggulan:

- Jika `is_featured = true`, tampilkan badge kecil `Unggulan`.

### Empty State

Empty state saat produk kosong:

- Gunakan icon `mdi:package-variant-closed`
- Teks: `Belum ada produk.`
- Subtext: `Tambahkan produk pertama untuk mulai mengisi katalog.`
- Tombol: `Tambah Produk`

## Rencana Penggantian Simbol `#`

Scope utama:

- Ubah header kolom di `resources/views/admin/products/index.blade.php`:

```blade
<th>No.</th>
```

Bukan:

```blade
<th>#</th>
```

Catatan:

- Jangan mengganti simbol `#` pada kode warna seperti `bg-[#FFF1F3]`.
- Jika user menginginkan semua tabel admin diganti, lakukan batch terpisah pada halaman admin lain.

## Rencana Responsive

Karena layout admin memakai sidebar `w-1/5` dan main `w-4/5`, tabel tetap perlu `overflow-x-auto`.

Rencana:

- Pertahankan wrapper `overflow-x-auto`.
- Gunakan `min-w-[900px]` pada table agar kolom tidak remuk.
- Pada layar kecil, admin bisa scroll horizontal.
- Hindari teks tombol terlalu panjang di kolom aksi.

## Rencana Test

Tambahkan atau update feature test admin produk, misalnya:

- `tests/Feature/Admin/ProductListTest.php`

Skenario minimal:

1. Admin bisa membuka halaman produk.
2. Halaman menampilkan label `No.`.
3. Halaman tidak menampilkan header tabel `#`.
4. Produk menampilkan nama, kategori, merek, harga, stok, dan status.
5. Produk dengan gambar menampilkan path image.
6. Produk tanpa gambar tetap render placeholder tanpa error.

Jika ingin test ringan tanpa file upload:

- Buat `ProductImage` secara manual dengan `image_path`.
- Assert response berisi `storage/products/test.jpg`.

Command test:

```bash
php artisan test --filter=ProductListTest
php artisan test --filter=Admin
```

## Rencana Validasi Manual

Setelah implementasi:

```bash
npm run build
php artisan test --filter=Admin
```

Validasi browser:

1. Login sebagai admin.
2. Buka `/admin/products`.
3. Pastikan header kolom nomor menjadi `No.`
4. Pastikan tidak ada simbol `#` pada header nomor list produk.
5. Pastikan thumbnail produk tampil jika ada gambar.
6. Pastikan produk tanpa gambar punya placeholder rapi.
7. Pastikan status `Tersedia`, `Tidak tersedia`, `Aktif`, `Nonaktif`, dan `Unggulan` tampil rapi.
8. Pastikan pagination tetap muncul jika data lebih dari 10.
9. Pastikan tombol edit tetap mengarah ke halaman edit produk.
10. Pastikan tidak ada horizontal overflow halaman yang tidak terkendali, selain scroll table yang memang wajar.

## Urutan Implementasi

1. Update query `Admin\ProductController@index` agar eager-load `images`.
2. Refactor `resources/views/admin/products/index.blade.php`.
3. Ganti label kolom `#` menjadi `No.`
4. Tambahkan thumbnail produk dan placeholder gambar.
5. Rapikan kolom produk, kategori/merek, harga, stok, status, dan aksi.
6. Tambahkan empty state yang lebih informatif.
7. Jalankan `npm run build`.
8. Jalankan test admin terkait.
9. Cek tampilan admin produk lewat browser.

## Catatan Risiko

1. Jika produk punya banyak gambar, eager-load `images` aman untuk pagination 10 item, tetapi tetap perlu memakai limit pagination.
2. Jika file gambar di storage tidak ada, browser akan menampilkan broken image; placeholder hanya berlaku jika record gambar tidak ada.
3. Jika tabel terlalu padat, gunakan `min-w-[900px]` dan scroll horizontal.
4. Jika ingin mengganti seluruh `#` di admin, perlu hati-hati karena `#` juga dipakai pada kode warna Tailwind arbitrary value.
5. File `resources/views/admin/products/partials/form.blade.php` saat ini terdeteksi modified di git status; jangan ubah atau revert file itu kecuali memang dibutuhkan oleh task berikutnya.

## Rekomendasi Implementasi

Untuk tahap pertama, lakukan perubahan terbatas:

1. Perbaiki list produk admin saja.
2. Ganti `#` menjadi `No.` hanya pada header nomor list produk.
3. Tambahkan thumbnail dan badge status agar halaman lebih mudah dipindai.
4. Jangan ubah database.
5. Jangan ubah form tambah/edit produk.

Pendekatan ini langsung menjawab kebutuhan tampilan admin tanpa memperbesar risiko ke flow produk lainnya.
