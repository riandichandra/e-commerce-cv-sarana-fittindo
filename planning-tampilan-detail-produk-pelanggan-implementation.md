# Planning Implementasi Tampilan Detail Produk Pelanggan

## Tujuan
Merapikan dan mempercantik halaman detail produk pelanggan agar lebih menarik, mudah dipindai, dan terasa lebih nyaman untuk pelanggan saat melihat produk sebelum memasukkan ke keranjang.

Fokus utama:
- Layout detail produk lebih modern dan rapi.
- Galeri gambar lebih enak dilihat.
- Informasi harga, stok, spesifikasi, dan aksi beli lebih jelas.
- Related product lebih konsisten dan benar-benar bisa digunakan.
- Memperbaiki teks/encoding yang rusak pada tombol quantity.

## Analisis Codebase

### Route dan Controller
File: `routes/web.php`

Detail produk pelanggan memakai route resource:
- `pelanggan.products.show`

Controller:
- `app/Http/Controllers/Pelanggan/ProductController.php`

Method terkait:
- `show(Product $product)`

Data yang dikirim ke view:
- `$product`
  - Relasi yang sudah diload: `category`, `brand`, `images`
- `$relatedProducts`
  - Produk aktif pada kategori yang sama.
  - Saat ini query belum meload `category`, `brand`, dan `images`.

Catatan implementasi:
- Untuk related product, sebaiknya controller meload relasi `category`, `brand`, dan `images` agar view tidak memicu N+1 query.

### View Detail Produk
File:
- `resources/views/pelanggan/products/show.blade.php`

Kondisi saat ini:
- Breadcrumb masih sederhana dengan background abu-abu.
- Layout utama menggunakan grid 2 kolom tanpa batas max-width yang halus.
- Gambar utama sudah ada, tapi belum terasa premium dan belum responsive optimal.
- Thumbnail mengganti gambar dengan inline onclick.
- Tombol minus quantity tampil rusak sebagai `âˆ’`.
- Informasi produk masih terpisah dan cukup polos.
- Detail tambahan hanya kategori, merek, dan stok.
- Field penting seperti berat, ketebalan, dimensi, dan spesifikasi belum ditampilkan.
- Tombol share belum punya fungsi nyata.
- Related product memiliki tombol `TAMBAH KE KERANJANG`, tetapi saat ini hanya `<button>` tanpa form/action, sehingga tidak menjalankan tambah ke keranjang.

### View Pendukung
File:
- `resources/views/pelanggan/products/index.blade.php`
- `resources/views/layouts/pelanggan/navigation.blade.php`
- `resources/views/pelanggan/dashboard.blade.php`

Catatan:
- Tampilan pelanggan memakai kombinasi warna merah brand `#c8102e`, biru/navy, putih, dan abu-abu.
- Detail produk perlu mengikuti arah visual halaman pelanggan yang sudah ada, bukan memakai gaya admin.

## Analisis Struktur Database

### Tabel `products`
Migration:
- `database/migrations/2026_04_30_141259_create_products_table.php`

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

Model:
- `app/Models/Product.php`

Relasi:
- `category()`
- `brand()`
- `images()`

Accessor/helper:
- `primary_image`
- `isAvailable()`

Field yang akan dimanfaatkan pada detail produk:
- `price`
- `stock`
- `status`
- `weight`
- `thickness`
- `dimensions`
- `specifications`
- `category`
- `brand`
- `images`

### Tabel `product_images`
Migration:
- `database/migrations/2026_04_30_141300_create_product_images_table.php`

Kolom penting:
- `product_id`
- `image_path`
- `is_primary`
- `sort_order`

Catatan:
- Relasi `Product::images()` sudah urut berdasarkan `sort_order`.
- View bisa memakai gambar pertama atau primary image sebagai gambar utama.

### Tabel Pendukung Keranjang dan Wishlist
Fitur yang dipakai view:
- Cart route: `pelanggan.cart.store`
- Wishlist route: `pelanggan.wishlist.toggle`

Catatan:
- Form tambah keranjang utama sudah ada.
- Related product perlu diperbaiki agar memakai form seperti list produk.

## Scope Implementasi

### 1. Rapikan Header dan Breadcrumb
File:
- `resources/views/pelanggan/products/show.blade.php`

Rencana:
- Ubah breadcrumb menjadi area ringan dengan max-width, link yang jelas, dan tidak terlalu tinggi.
- Gunakan bahasa Indonesia yang konsisten:
  - `Beranda`
  - `Produk`
  - Nama produk
- Pastikan breadcrumb tetap responsive di mobile.

### 2. Rapikan Layout Utama Detail Produk
Rencana:
- Gunakan wrapper `max-w-7xl mx-auto` dengan padding responsive.
- Layout desktop: dua kolom, galeri produk di kiri dan informasi/aksi di kanan.
- Layout mobile: satu kolom.
- Hindari card bersarang berlebihan; gunakan panel utama yang bersih.
- Buat area informasi produk lebih mudah dipindai:
  - badge kategori
  - brand
  - nama produk
  - harga
  - status stok
  - CTA tambah ke keranjang
  - wishlist

### 3. Perbaiki Galeri Gambar
Rencana:
- Gambar utama menggunakan aspek rasio stabil agar tidak membuat layout lompat.
- Tambahkan fallback visual yang lebih rapi saat tidak ada gambar.
- Thumbnail dibuat horizontal/grid kecil dengan state hover/focus.
- Tetap bisa mengganti gambar utama tanpa library tambahan.
- Jika memungkinkan, gunakan data attribute dan script kecil yang lebih bersih daripada inline onclick panjang.

### 4. Perbaiki Quantity dan CTA
Rencana:
- Perbaiki karakter minus rusak `âˆ’` menjadi simbol yang valid atau teks/simbol ASCII `-`.
- Tombol quantity dibuat lebih stabil dengan ukuran tetap.
- Batas maksimum quantity tetap mengikuti stok.
- Saat stok habis, CTA disabled tetap jelas.
- Tambahkan microcopy singkat seperti `Siap dikirim` atau `Stok tersedia` tanpa membuat halaman ramai.

### 5. Tampilkan Spesifikasi Produk
Rencana:
- Tambahkan section `Spesifikasi Produk`.
- Tampilkan:
  - Kategori
  - Merek
  - Berat
  - Ketebalan
  - Dimensi
  - Stok
  - Status
  - Spesifikasi JSON jika ada
- Jika field kosong, tampilkan `-` atau sembunyikan baris sesuai konteks.
- Untuk `specifications` yang berupa array, render sebagai list key-value yang rapi.

### 6. Rapikan Deskripsi
Rencana:
- Pindahkan deskripsi ke area yang lebih elegan, bisa dalam section terpisah setelah CTA atau di bawah layout utama.
- Gunakan line-height yang nyaman.
- Jika tidak ada deskripsi, tampilkan empty state singkat yang tidak mencolok.

### 7. Perbaiki Wishlist dan Share
Rencana:
- Wishlist tetap memakai form yang sudah ada.
- Tombol share dibuat berfungsi menggunakan Web Share API jika browser mendukung, fallback copy URL ke clipboard.
- Tambahkan feedback sederhana pada tombol share lewat teks kecil atau perubahan label sementara jika perlu.

### 8. Rapikan Related Product
Rencana:
- Ubah section `PRODUK TERKAIT` menjadi grid responsive yang konsisten dengan card produk pelanggan.
- Gunakan card lebih rapi:
  - gambar
  - kategori
  - nama produk
  - brand jika ada
  - harga
  - status stok
  - tombol lihat detail / tambah ke keranjang
- Perbaiki tombol `TAMBAH KE KERANJANG` agar memakai form `pelanggan.cart.store` saat produk tersedia.
- Jika related product kosong, tidak perlu tampil.

### 9. Responsiveness dan Aksesibilitas
Rencana:
- Pastikan tampilan mobile tidak overflow.
- Tombol dan input punya ukuran klik yang nyaman.
- Gambar memiliki alt text.
- Button quantity punya `type="button"`.
- Script tidak error saat element tertentu tidak ada.

## File yang Akan Diubah
- `app/Http/Controllers/Pelanggan/ProductController.php`
- `resources/views/pelanggan/products/show.blade.php`

## File Test yang Akan Ditambahkan
- `tests/Feature/Pelanggan/ProductDetailPageTest.php`

Rencana test:
- Halaman detail produk pelanggan dapat dirender.
- Menampilkan nama produk, harga, kategori, brand, stok, dan spesifikasi penting.
- Form tambah ke keranjang utama memiliki input quantity.
- Produk terkait tampil saat ada produk kategori sama.
- Tombol related product yang tersedia memiliki form tambah ke keranjang.
- Produk tanpa gambar tetap menampilkan fallback tanpa error.

## Batasan Implementasi
- Tidak mengubah struktur database.
- Tidak mengubah route pelanggan.
- Tidak mengubah logic cart/wishlist di controller lain.
- Tidak membuat landing page baru.
- Fokus pada detail produk pelanggan dan query relasi yang dibutuhkan untuk tampilan.

## Validasi Setelah Implementasi
Perintah yang akan dijalankan:
- `php artisan test --filter=ProductDetailPageTest`
- `php artisan test --filter=Pelanggan`
- `npm run build`

Jika server lokal tersedia, lakukan pengecekan manual pada:
- `/pelanggan/products/{product}`
- Detail produk dengan gambar.
- Detail produk tanpa gambar.
- Detail produk stok habis.
- Detail produk dengan related product.
