# Analisis Kolom `slug` pada Codebase dan Struktur Database

## Ringkasan

Berdasarkan penelusuran migration, model, controller, route, view, seeder, dan test, tabel yang memiliki kolom `slug` hanya ada 3:

1. `product_categories`
2. `product_brands`
3. `products`

Kesimpulan umum:

- `product_categories.slug` **difungsikan aktif** untuk filter kategori di halaman pelanggan.
- `product_brands.slug` **belum difungsikan untuk logika utama aplikasi**; saat ini hanya dibuat otomatis dan ditampilkan di halaman admin.
- `products.slug` **belum difungsikan untuk routing atau pencarian produk**; saat ini hanya dibuat otomatis, disimpan, ditampilkan di admin, dan dipakai di seeder/test sebagai data pendukung.

Catatan: ada pemakaian `Str::slug()` di `Admin\PaymentMethodController`, tetapi itu untuk membentuk `payment_methods.code`, bukan kolom bernama `slug`, sehingga tidak termasuk tabel slug yang dianalisis.

## Sumber Struktur Database

### `product_categories`

Migration: `database/migrations/2026_04_30_141255_create_product_categories_table.php`

Kolom:

- `id`
- `name` varchar 100
- `slug` varchar 100 unique
- `description` text nullable
- `is_active` boolean default true
- `created_at`, `updated_at`

Status kolom `slug`: **aktif digunakan**.

### `product_brands`

Migration: `database/migrations/2026_04_30_141257_create_product_brands_table.php`

Kolom:

- `id`
- `name` varchar 100
- `slug` varchar 100 unique
- `description` text nullable
- `logo` varchar 255 nullable pada migration awal, tetapi ada migration cleanup yang menghapus kolom ini jika kosong
- `is_active` boolean default true
- `created_at`, `updated_at`

Status kolom `slug`: **belum digunakan untuk alur bisnis utama**.

### `products`

Migration: `database/migrations/2026_04_30_141259_create_products_table.php`

Kolom terkait:

- `id`
- `category_id`
- `brand_id`
- `name` varchar 200
- `slug` varchar 200 unique
- `description`
- `price`
- `stock`
- `weight`
- `thickness`
- `dimensions`
- `specifications`
- `is_featured`
- `is_active`
- `created_at`, `updated_at`, `deleted_at`

Status kolom `slug`: **belum digunakan untuk routing atau query pelanggan**.

## Analisis per Tabel

## 1. `product_categories.slug`

### Cara Slug Dibuat

Model: `app/Models/ProductCategory.php`

Slug dibuat otomatis pada event `saving`:

```php
$category->slug = Str::slug($category->name);
```

Artinya:

- Admin tidak mengisi slug secara manual.
- Saat kategori dibuat atau nama kategori diubah, slug ikut berubah mengikuti nama terbaru.
- Kolom `slug` tetap masuk `$fillable`, tetapi controller admin tidak menerima input `slug` dari request.

### Pemakaian di Codebase

Kolom ini digunakan aktif di beberapa tempat:

1. Filter produk pelanggan berdasarkan kategori.

   File: `app/Http/Controllers/Pelanggan/ProductController.php`

   Query memakai request `category` lalu mencocokkan ke `product_categories.slug`:

   ```php
   ->where('slug', $request->string('category')->toString())
   ```

2. Link kategori pada dashboard pelanggan.

   File: `resources/views/pelanggan/dashboard.blade.php`

   Link memakai query string:

   ```php
   route('pelanggan.products.index', ['category' => $category->slug])
   ```

3. Checkbox/filter kategori pada daftar produk pelanggan.

   File: `resources/views/pelanggan/products/index.blade.php`

   Slug dipakai untuk menentukan pilihan kategori aktif dan URL filter.

4. Link kategori pada halaman detail produk.

   File: `resources/views/pelanggan/products/show.blade.php`

   Slug kategori dipakai untuk link kembali ke daftar produk terfilter kategori.

5. Seeder kategori.

   File: `database/seeders/CategorySeeder.php`

   Slug dipakai sebagai key stabil untuk `updateOrInsert`, termasuk migrasi slug lama ke slug baru.

6. Seeder dummy produk.

   File: `database/seeders/DummyDataSeeder.php`

   Slug kategori dipakai untuk mapping produk dummy ke kategori:

   ```php
   ProductCategory::pluck('id', 'slug')
   ```

7. Tampilan admin kategori.

   File: `resources/views/admin/categories/index.blade.php`

   Slug ditampilkan sebagai informasi tambahan di list kategori.

### Apakah Difungsikan?

Ya. `product_categories.slug` adalah kolom slug yang paling jelas difungsikan.

Fungsi aktual:

- Identifier kategori di URL filter pelanggan: `?category=hpl`.
- Key data seeder agar kategori bisa di-update tanpa bergantung pada `id`.
- Informasi admin.

### Catatan Risiko

Karena slug dibuat ulang saat nama kategori berubah, URL filter lama bisa berubah. Contoh:

- Nama lama: `HPL`
- Slug: `hpl`
- Nama diubah: `HPL Premium`
- Slug berubah menjadi `hpl-premium`

Dampaknya:

- Link lama `?category=hpl` tidak lagi memfilter kategori tersebut.
- Tidak ada mekanisme redirect dari slug lama ke slug baru.

Untuk aplikasi saat ini, ini masih aman jika slug hanya digunakan internal untuk link filter. Namun jika slug dipakai sebagai URL publik yang dibagikan ke pelanggan, perubahan nama kategori dapat memutus link lama.

### Rekomendasi

Pertahankan kolom ini karena sudah dipakai.

Opsional perbaikan:

- Jangan ubah slug otomatis setelah data kategori pertama kali dibuat, kecuali admin memang ingin mengubah slug.
- Atau tambahkan field slug manual di admin jika URL kategori ingin stabil.

## 2. `product_brands.slug`

### Cara Slug Dibuat

Model: `app/Models/ProductBrand.php`

Slug dibuat otomatis pada event `saving`:

```php
$brand->slug = Str::slug($brand->name);
```

Artinya:

- Admin tidak mengisi slug secara manual.
- Saat nama brand dibuat atau diubah, slug ikut berubah.
- Kolom `slug` ada di `$fillable`, tetapi controller admin brand tidak menerima input `slug` dari form.

### Pemakaian di Codebase

Pemakaian yang ditemukan:

1. Tampilan admin brand.

   File: `resources/views/admin/brands/index.blade.php`

   Slug hanya ditampilkan sebagai teks di bawah nama brand.

2. Dibuat otomatis oleh model.

   File: `app/Models/ProductBrand.php`

3. Constraint database.

   Migration mendefinisikan `slug` sebagai unique.

### Apakah Difungsikan?

Sebagian besar belum.

Saat ini `product_brands.slug` **tidak dipakai** untuk:

- Routing brand.
- Filter produk berdasarkan brand di halaman pelanggan.
- Query controller pelanggan.
- Seeder mapping brand. `DummyDataSeeder` memakai nama brand sebagai key, bukan slug.
- Route model binding.

Fungsi aktualnya saat ini hanya:

- Disimpan otomatis.
- Dijaga unik di database.
- Ditampilkan di halaman admin.

### Catatan Risiko

Walaupun belum dipakai untuk fitur utama, slug tetap wajib unik. Jika ada dua brand dengan nama yang menghasilkan slug sama, proses simpan bisa gagal karena constraint unique.

Contoh potensi konflik:

- `Brand A+`
- `Brand A`

Keduanya bisa menghasilkan slug mirip tergantung normalisasi.

Selain itu, karena slug diubah otomatis saat nama brand berubah, jika suatu saat slug brand dipakai sebagai URL publik, link lama bisa berubah.

### Rekomendasi

Ada dua pilihan:

1. Pertahankan jika ada rencana fitur filter brand atau halaman brand publik.
2. Jika tidak ada rencana menggunakan brand slug, kolom ini bisa dianggap belum perlu secara fungsional, tetapi penghapusannya harus hati-hati karena sudah ada migration, model, view admin, dan constraint database.

Jika dipertahankan, rekomendasi fitur lanjutan:

- Tambahkan filter produk berdasarkan brand di halaman pelanggan memakai `brand` query string.
- Gunakan `product_brands.slug` sebagai parameter filter, mirip kategori.

## 3. `products.slug`

### Cara Slug Dibuat

Model: `app/Models/Product.php`

Slug dibuat otomatis pada event `saving`:

```php
$product->slug = Str::slug($product->name);
```

Artinya:

- Admin tidak mengisi slug produk secara manual.
- Saat produk dibuat atau nama produk diubah, slug ikut berubah.
- Kolom `slug` ada di `$fillable`, tetapi controller admin produk tidak menerima input `slug` dari request.

### Pemakaian di Codebase

Pemakaian yang ditemukan:

1. Dibuat otomatis oleh model.

   File: `app/Models/Product.php`

2. Ditampilkan di halaman admin produk.

   File: `resources/views/admin/products/index.blade.php`

3. Seeder dummy produk.

   File: `database/seeders/DummyDataSeeder.php`

   Data dummy mendefinisikan slug produk, tetapi saat `Product::create()` berjalan, model tetap akan membuat ulang slug dari `name` pada event `saving`.

4. Test pagination.

   File: `tests/Feature/PaginationTablesTest.php`

   Test mengisi slug produk untuk data pengujian.

### Apakah Difungsikan?

Belum untuk fitur utama pelanggan.

Saat ini `products.slug` **tidak dipakai** untuk:

- URL detail produk.
- Route model binding.
- Pencarian produk.
- Filter produk.
- Checkout, cart, wishlist, atau order.

Detail produk pelanggan masih memakai route resource Laravel dengan model binding default berdasarkan `id`:

```php
Route::resource('products', Pelanggan\ProductController::class)->only(['index', 'show']);
```

Controller menerima:

```php
public function show(Product $product)
```

Karena model `Product` tidak override `getRouteKeyName()`, Laravel memakai `id`, bukan `slug`.

Contoh URL yang dipakai saat ini:

- `/pelanggan/products/1`
- bukan `/pelanggan/products/hpl-wood-grain-walnut`

### Catatan Risiko

Kolom `products.slug` saat ini memberi constraint unik tambahan, tetapi belum memberi manfaat ke UX pelanggan.

Risiko yang ada:

- Nama produk yang berbeda tetapi menghasilkan slug sama dapat gagal disimpan karena `products.slug` unique.
- Slug berubah ketika nama produk diubah, sehingga jika nanti slug dipakai sebagai URL publik tanpa strategi redirect, link lama akan rusak.
- Karena route masih berbasis `id`, slug yang tersimpan belum membantu SEO atau URL yang ramah pengguna.

### Rekomendasi

Jika ingin slug produk benar-benar difungsikan:

- Tambahkan di model `Product`:

  ```php
  public function getRouteKeyName(): string
  {
      return 'slug';
  }
  ```

- Pastikan semua link `route('pelanggan.products.show', $product)` tetap bekerja, karena Laravel akan memakai slug jika route key diubah.
- Evaluasi admin route juga, karena resource admin produk juga menerima `Product $product`; jika global route key diubah ke slug, route admin edit/update ikut memakai slug.
- Alternatif lebih aman: gunakan route khusus pelanggan berbasis slug, sementara admin tetap berbasis id.

Jika tidak ada kebutuhan URL produk berbasis slug, kolom ini dapat dipertahankan sebagai metadata, tetapi secara fungsi saat ini belum diperlukan.

## Tabel yang Tidak Memiliki Kolom `slug`

Tabel lain yang terlihat di migration tidak memiliki kolom bernama `slug`, antara lain:

- `users`
- `payment_methods`
- `orders`
- `order_items`
- `payments`
- `product_images`
- `promotions`
- `carts`
- `cart_items`
- `wishlists`
- `user_addresses`
- tabel permission/role dari package Spatie
- tabel cache, jobs, sessions, personal access tokens
- tabel wilayah legacy seperti `provinces`, `regencies`, `districts`, `villages`

Catatan khusus:

- `payment_methods` memiliki kolom `code`, bukan `slug`.
- `promotions` memiliki kolom `code`, bukan `slug`.

## Kesimpulan Akhir

| Tabel | Kolom | Status Fungsi | Dipakai Untuk |
| --- | --- | --- | --- |
| `product_categories` | `slug` | Difungsikan aktif | Filter kategori pelanggan, link kategori, seeder |
| `product_brands` | `slug` | Belum difungsikan untuk alur utama | Dibuat otomatis dan ditampilkan di admin |
| `products` | `slug` | Belum difungsikan untuk routing/query utama | Dibuat otomatis, ditampilkan di admin, data seeder/test |

Secara praktis, hanya `product_categories.slug` yang saat ini benar-benar menjadi bagian dari fitur pelanggan. `product_brands.slug` dan `products.slug` masih lebih berperan sebagai metadata yang disiapkan untuk kemungkinan fitur lanjutan.
