# Planning Implementasi Perbaikan Tampilan Beranda Pelanggan

## Ringkasan Analisis Codebase

Project ini adalah aplikasi Laravel ecommerce dengan pemisahan area berdasarkan role:

- Pelanggan: `app/Http/Controllers/Pelanggan/*` dan `resources/views/pelanggan/*`
- Admin: `app/Http/Controllers/Admin/*` dan `resources/views/admin/*`
- Marketing: `app/Http/Controllers/Marketing/*` dan `resources/views/marketing/*`
- GM: `app/Http/Controllers/GM/*` dan `resources/views/gm/*`
- Direktur: `app/Http/Controllers/Direktur/*` dan `resources/views/direktur/*`

Beranda pelanggan saat ini dirender oleh:

- Route utama: `routes/web.php`
- Controller: `app/Http/Controllers/Pelanggan/DashboardController.php`
- View utama: `resources/views/pelanggan/dashboard.blade.php`
- Layout pelanggan: `resources/views/layouts/pelanggan/layout.blade.php`
- Navigasi pelanggan: `resources/views/layouts/pelanggan/navigation.blade.php`

Stack frontend yang dipakai:

- Blade template
- Tailwind CSS
- Alpine.js untuk slider hero
- Iconify untuk icon
- Vite build pipeline

## Ringkasan Struktur Database Terkait Beranda

Data utama yang sudah tersedia untuk membangun beranda pelanggan:

### `product_categories`

Migration:

- `database/migrations/2026_04_30_141255_create_product_categories_table.php`

Kolom penting:

- `id`
- `name`
- `slug`
- `description`
- `is_active`
- `timestamps`

Model:

- `app/Models/ProductCategory.php`

Relasi dan scope:

- `products()`
- `scopeActive()`

Catatan:

- Controller dashboard sudah mengambil `$categories = ProductCategory::active()->limit(4)->get();`
- Namun view dashboard sekarang belum memanfaatkan `$categories` secara optimal karena kategori card masih hardcoded.

### `products`

Migration awal:

- `database/migrations/2026_04_30_141259_create_products_table.php`

Migration update stok/status:

- `database/migrations/2026_06_02_000000_update_products_stock_and_status.php`

Kolom penting:

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
- `deleted_at`

Model:

- `app/Models/Product.php`

Relasi dan scope:

- `category()`
- `brand()`
- `images()`
- `scopeActive()`
- `scopeFeatured()`
- `scopeAvailable()`
- `primary_image`

Catatan penting:

- `Product::active()` hanya memastikan produk aktif, belum memastikan stok tersedia.
- Produk memiliki `is_featured`, tetapi dashboard sekarang belum memakai produk unggulan sebagai section khusus.
- View beberapa kali mengambil gambar lewat `$product->images->first()`, padahal model sudah punya accessor `primary_image`.

### `product_images`

Migration:

- `database/migrations/2026_04_30_141300_create_product_images_table.php`

Kolom penting:

- `product_id`
- `image_path`
- `is_primary`
- `sort_order`

Catatan:

- Relasi gambar sudah diurutkan berdasarkan `sort_order`.
- Tampilan beranda perlu memakai gambar utama jika ada, lalu fallback placeholder yang rapi jika gambar kosong.

### `promotions`

Migration:

- `database/migrations/2026_04_30_141343_create_promotions_table.php`

Kolom penting:

- `name`
- `code`
- `description`
- `type`
- `value`
- `min_purchase`
- `max_discount`
- `start_date`
- `end_date`
- `is_active`
- `banner_image`
- `banner_url`
- `created_by`

Model:

- `app/Models/Promotion.php`

Scope tersedia:

- `scopeActiveNow()`

Catatan:

- `DashboardController` masih menulis query promosi aktif secara manual.
- Bisa dirapikan memakai `Promotion::activeNow()` agar konsisten dengan model.

## Kondisi Tampilan Beranda Saat Ini

File utama:

- `resources/views/pelanggan/dashboard.blade.php`

Masalah yang terlihat:

1. Hero terlalu tinggi (`h-[650px]`) sehingga terasa berat untuk pelanggan yang baru login.
2. Banyak teks masih berbahasa Inggris, misalnya `View Promotion`, `Our Latest Arrivals`, `Our Products`, dan footer copy.
3. Banyak data fallback hardcoded dengan kategori dan nama produk statis.
4. Kartu kategori memakai kategori statis seperti `hpl`, `mdf`, `plywood`, sementara data kategori aktif sudah tersedia dari database.
5. Layout section terlalu besar (`py-[100px]`, card kategori `h-[530px]`) dan membuat halaman terasa longgar tetapi tidak efisien.
6. Palet visual terlalu kontras antara biru gelap, pink, merah, dan putih sehingga kesan halaman kurang rapi.
7. Footer terlalu mirip landing page publik, padahal pelanggan sudah login dan lebih membutuhkan akses cepat ke produk, kategori, keranjang, dan pesanan.
8. Section `Technical Standards & Certification` terlihat informatif, tetapi kurang relevan sebagai prioritas utama pelanggan setelah login.
9. Responsif mobile berpotensi kurang nyaman karena ukuran typography dan tinggi card cukup besar.
10. Navigation sudah fungsional, tetapi tinggi 50px dan search 270px bisa terasa padat di desktop sempit.

## Tujuan Perubahan

Membuat beranda pelanggan yang lebih rapi, ringan, dan nyaman dilihat setelah login.

Target pengalaman:

1. Pelanggan langsung melihat promo aktif atau ajakan belanja yang jelas.
2. Pelanggan mudah masuk ke kategori produk.
3. Produk terbaru dan produk unggulan tampil ringkas, sejajar, dan mudah dipindai.
4. Status stok, harga, kategori, dan tombol aksi terlihat jelas.
5. Tampilan desktop dan mobile tetap stabil tanpa elemen terlalu tinggi atau saling bertabrakan.
6. Copywriting memakai bahasa Indonesia yang konsisten.
7. Layout terasa seperti dashboard belanja pelanggan, bukan landing page editorial yang terlalu besar.

## Scope Implementasi

Perubahan utama disarankan pada:

- `app/Http/Controllers/Pelanggan/DashboardController.php`
- `resources/views/pelanggan/dashboard.blade.php`
- `resources/views/layouts/pelanggan/navigation.blade.php`

Perubahan opsional jika dibutuhkan:

- `resources/css/app.css`
- Component Blade kecil untuk kartu produk jika ingin mengurangi duplikasi

Tidak termasuk scope utama:

- Mengubah struktur database.
- Mengubah flow checkout.
- Mengubah halaman katalog produk secara besar-besaran.
- Mengubah dashboard role admin/marketing/GM/direktur.

## Rencana Data Controller

File:

- `app/Http/Controllers/Pelanggan/DashboardController.php`

Rencana query:

1. Ambil kategori aktif dengan jumlah produk aktif:

```php
$categories = ProductCategory::active()
    ->withCount(['products' => fn ($query) => $query->active()])
    ->limit(6)
    ->get();
```

2. Ambil produk unggulan:

```php
$featuredProducts = Product::active()
    ->featured()
    ->with(['category', 'brand', 'images'])
    ->latest()
    ->limit(4)
    ->get();
```

3. Ambil produk terbaru:

```php
$latestProducts = Product::active()
    ->with(['category', 'brand', 'images'])
    ->latest()
    ->limit(8)
    ->get();
```

4. Ambil promosi aktif memakai scope model:

```php
$heroPromotions = Promotion::activeNow()
    ->whereNotNull('banner_image')
    ->latest()
    ->limit(3)
    ->get();
```

5. Jika user login, siapkan ringkasan ringan:

```php
$cartItemsCount = $request->user()?->cart?->items()->count() ?? 0;
```

Catatan:

- Perlu cek relasi cart di model `User` sebelum menambahkan summary keranjang.
- Jika relasi belum siap atau berisiko, ringkasan keranjang bisa ditunda dan cukup arahkan tombol ke halaman keranjang.

## Rencana Tampilan Beranda

File:

- `resources/views/pelanggan/dashboard.blade.php`

Struktur halaman yang disarankan:

1. Hero ringkas
2. Shortcut pelanggan
3. Kategori produk
4. Produk unggulan
5. Produk terbaru
6. Banner bantuan atau CTA belanja

### 1. Hero Ringkas

Ganti hero besar menjadi section lebih pendek:

- Desktop: sekitar `min-h-[360px]` sampai `min-h-[420px]`
- Mobile: tinggi otomatis dengan padding
- Max width konten: `max-w-7xl`
- Gunakan promo aktif jika ada
- Jika tidak ada promo, tampilkan pesan umum CV Sarana Fittindo

Konten:

- Sapaan pelanggan jika login: `Halo, {{ Auth::user()->name }}`
- Judul: `Temukan Material Interior untuk Kebutuhan Proyek Anda`
- Deskripsi singkat
- Tombol utama: `Belanja Sekarang`
- Tombol kedua: `Lihat Pesanan`

Visual:

- Pakai gambar promo jika tersedia.
- Jika tidak ada gambar, gunakan fallback produk terbaru yang punya gambar.
- Jika tetap kosong, tampilkan placeholder gradient sederhana berbasis brand.

### 2. Shortcut Pelanggan

Tambahkan bar kecil berisi akses cepat:

- Semua Produk
- Keranjang
- Pesanan Saya
- Riwayat Pesanan

Style:

- Grid 2 kolom di mobile.
- 4 kolom di desktop.
- Icon + label ringkas.
- Tidak memakai card besar.

### 3. Kategori Produk

Gunakan data `$categories` dari database, bukan array hardcoded.

Tampilan:

- Header: `Belanja Berdasarkan Kategori`
- Grid 2 kolom mobile, 3 atau 6 kolom desktop.
- Setiap item menampilkan:
  - Nama kategori
  - Jumlah produk aktif
  - Link ke katalog dengan query `category={{ $category->slug }}`

Fallback:

- Jika kategori kosong, tampilkan pesan singkat dan tombol ke semua produk.

### 4. Produk Unggulan

Gunakan `$featuredProducts`.

Jika kosong:

- Fallback ke 4 produk terbaru.

Tampilan kartu:

- Gambar produk aspect ratio stabil, misalnya `aspect-[4/3]`
- Badge kategori
- Nama produk maksimal 2 baris
- Harga
- Stok tersedia/tidak tersedia
- Tombol `Detail` atau icon keranjang

### 5. Produk Terbaru

Gunakan `$latestProducts` dengan limit 8.

Tampilan:

- Grid 2 kolom mobile, 4 kolom desktop.
- Kartu lebih kecil dan konsisten.
- Tombol `Lihat Semua Produk` di kanan header.

### 6. CTA Bantuan

Ganti section `Technical Standards & Certification` dengan CTA yang lebih relevan:

- Judul: `Butuh Bantuan Memilih Material?`
- Isi: arahkan pelanggan untuk melihat kategori, detail produk, atau hubungi toko.
- Tombol: `Lihat Katalog`

Catatan:

- Jika belum ada fitur kontak, tombol cukup ke katalog produk.
- Jangan tambahkan fitur baru yang belum ada route/backend-nya.

## Rencana Perbaikan Navigasi

File:

- `resources/views/layouts/pelanggan/navigation.blade.php`

Rencana:

1. Jadikan brand lebih ringkas, misalnya `Sarana Fittindo`.
2. Tambahkan active state untuk route `pelanggan.dashboard`.
3. Search tetap ada, tetapi lebar dibuat responsif:

```html
class="h-9 w-[220px] lg:w-[280px]"
```

4. Pada mobile, pastikan menu memiliki link ke:
   - Beranda
   - Semua Produk
   - Pesanan Saya
   - Riwayat Pesanan
   - Keranjang

Catatan:

- Perubahan navigasi harus tetap menjaga route login untuk user guest.
- Beranda pelanggan ada route publik dan route auth dengan nama yang sama di prefix pelanggan, jadi penggunaan route perlu dicek agar tidak salah highlight.

## Rencana Visual Design

Arahan visual:

1. Gunakan warna dasar putih dan abu muda agar produk lebih mudah dilihat.
2. Gunakan merah brand `#C8102E` sebagai aksen tombol dan highlight.
3. Gunakan biru gelap hanya sebagai teks utama, bukan dominan di seluruh halaman.
4. Hindari section terlalu tinggi.
5. Gunakan border halus dan shadow ringan.
6. Gunakan radius konsisten, maksimal `rounded-lg`.
7. Semua kartu produk harus punya tinggi gambar stabil agar grid tidak berantakan.
8. Text heading lebih natural, tidak semua uppercase.
9. Pakai bahasa Indonesia konsisten.
10. Pastikan tidak ada karakter encoding rusak seperti `Â©`.

## Rencana Empty State dan Fallback

Fallback yang perlu disiapkan:

1. Promo kosong:
   - Tampilkan hero default tanpa mengandalkan file gambar hardcoded.
2. Kategori kosong:
   - Tampilkan pesan `Kategori belum tersedia`.
3. Produk unggulan kosong:
   - Fallback ke produk terbaru.
4. Produk kosong:
   - Tampilkan pesan `Produk belum tersedia` dan tombol ke katalog.
5. Gambar produk kosong:
   - Tampilkan placeholder rapi dengan icon produk/material.

## Rencana Testing

Feature test yang bisa ditambahkan atau diperbarui:

- `tests/Feature/Pelanggan/DashboardTest.php`

Skenario minimal:

1. Guest bisa membuka beranda pelanggan.
2. Pelanggan login bisa membuka beranda pelanggan.
3. Produk aktif muncul di beranda.
4. Produk nonaktif tidak muncul di beranda.
5. Kategori aktif muncul sebagai link kategori.
6. Promosi aktif dengan banner muncul sebagai hero.
7. Promosi expired tidak muncul sebagai hero.

Jika waktu terbatas, test minimal:

```bash
php artisan test --filter=Pelanggan
php artisan test --filter=Dashboard
```

## Rencana Validasi Manual

Setelah implementasi, jalankan:

```bash
npm run build
php artisan test --filter=Pelanggan
```

Validasi browser:

1. Buka `/pelanggan` sebagai guest.
2. Login sebagai pelanggan.
3. Pastikan beranda tampil rapi di desktop.
4. Pastikan beranda tampil rapi di mobile.
5. Klik kategori dari beranda, pastikan masuk ke katalog dengan filter kategori.
6. Klik produk unggulan/terbaru, pastikan masuk ke detail produk.
7. Klik `Belanja Sekarang`, pastikan masuk ke katalog produk.
8. Klik `Pesanan Saya`, pastikan pelanggan login masuk ke halaman pesanan.
9. Pastikan promo aktif tampil jika ada data promosi aktif.
10. Pastikan halaman tetap bagus jika tidak ada promo atau produk.

## Urutan Implementasi

1. Rapikan `DashboardController` agar query lebih jelas dan menggunakan data database yang relevan.
2. Refactor `dashboard.blade.php`:
   - hapus array kategori hardcoded,
   - kurangi fallback statis,
   - ubah hero menjadi lebih ringkas,
   - tambah shortcut pelanggan,
   - tampilkan kategori dari database,
   - tampilkan produk unggulan dan terbaru.
3. Rapikan copywriting ke bahasa Indonesia.
4. Sesuaikan ukuran section, grid, dan kartu agar mobile/desktop stabil.
5. Rapikan navigasi pelanggan jika diperlukan.
6. Tambahkan empty state dan fallback gambar.
7. Jalankan build frontend.
8. Jalankan test terkait.
9. Cek tampilan via browser pada desktop dan mobile.

## Catatan Risiko

1. Beberapa route dashboard pelanggan didefinisikan lebih dari sekali di `routes/web.php`; perlu hati-hati agar link dan active state tetap mengarah benar.
2. View saat ini memakai file gambar hardcoded dari `storage/products/...`; jika file tidak ada di environment lain, gambar bisa rusak.
3. `Product::scopeAvailable()` mengecek `status` dan `stock`, tetapi dashboard sekarang memakai `active()` saja. Perlu diputuskan apakah beranda menampilkan semua produk aktif atau hanya yang tersedia.
4. Model `Product` punya accessor `primary_image`, tetapi accessor memanggil query baru. Untuk list besar, lebih baik tetap memanfaatkan eager loaded `images` lalu ambil dari collection.
5. Jika menambah ringkasan keranjang, perlu memastikan relasi `User` ke cart sudah tersedia agar tidak membuat error.

## Rekomendasi Keputusan Implementasi

Untuk tahap pertama, fokus ke perbaikan tampilan tanpa mengubah database:

1. Gunakan produk aktif sebagai sumber utama.
2. Gunakan produk unggulan jika ada, fallback ke produk terbaru jika kosong.
3. Gunakan kategori aktif dari database.
4. Gunakan promosi aktif dari database untuk hero.
5. Jangan tambahkan fitur baru seperti newsletter atau kontak jika route/backend belum tersedia.
6. Hilangkan footer panjang dari beranda login dan ganti dengan CTA pendek yang relevan.

Dengan pendekatan ini, perubahan tetap aman, rapi, dan langsung memperbaiki pengalaman pelanggan saat masuk ke beranda.
