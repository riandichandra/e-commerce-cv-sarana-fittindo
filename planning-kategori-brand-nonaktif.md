# Planning Perbaikan Filter Produk Saat Kategori atau Brand Dinonaktifkan

## Ringkasan Masalah

Saat ini produk di akun pelanggan masih dapat tampil meskipun kategori produk atau brand produk sudah dinonaktifkan. Penyebab utamanya adalah beberapa query pelanggan hanya memfilter `products.is_active`, tetapi belum memastikan relasi `product_categories.is_active` dan `product_brands.is_active` juga aktif.

Target perubahan: setiap pengambilan data produk untuk katalog dan aksi pelanggan hanya menganggap produk valid jika:

- `products.is_active = true`
- `products.status = tersedia` dan stok masih tersedia untuk proses beli/checkout
- kategori produk aktif: `product_categories.is_active = true`
- brand produk aktif jika produk memiliki brand: `product_brands.is_active = true`
- produk tanpa brand tetap boleh tampil selama produk dan kategorinya aktif, karena `products.brand_id` nullable

## Struktur Database Terkait

### `product_categories`

Kolom penting:

- `id`
- `name`
- `slug`
- `description`
- `is_active`
- `created_at`, `updated_at`

Relasi:

- Satu kategori memiliki banyak produk melalui `products.category_id`.
- `products.category_id` wajib ada dan memakai `onDelete('restrict')`.

### `product_brands`

Kolom penting:

- `id`
- `name`
- `slug`
- `description`
- `is_active`
- `created_at`, `updated_at`

Relasi:

- Satu brand memiliki banyak produk melalui `products.brand_id`.
- `products.brand_id` nullable dan memakai `onDelete('set null')`.

### `products`

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
- `is_featured`
- `is_active`
- soft delete melalui `deleted_at`

Relasi:

- Produk wajib memiliki kategori.
- Produk boleh tidak memiliki brand.
- Produk memiliki banyak gambar melalui `product_images.product_id`.

## Analisis Codebase Saat Ini

### Model `Product`

File: `app/Models/Product.php`

Kondisi saat ini:

- `scopeActive()` hanya memfilter `products.is_active`.
- `scopeAvailable()` hanya memfilter produk aktif, status tersedia, dan stok lebih dari 0.
- `isAvailable()` hanya memeriksa produk aktif, stok, dan status.
- Belum ada pemeriksaan kategori aktif dan brand aktif.

Dampak:

- Produk dengan kategori nonaktif masih muncul di query `Product::active()`.
- Produk dengan brand nonaktif masih muncul di query `Product::active()`.
- Produk tersebut masih bisa dibuka dari URL detail produk karena route model binding langsung menerima `Product $product`.
- Produk tersebut masih bisa masuk cart atau checkout jika produknya sendiri aktif dan stok tersedia.

### Controller Pelanggan

File: `app/Http/Controllers/Pelanggan/ProductController.php`

Kondisi saat ini:

- `index()` memakai `Product::active()` untuk daftar produk.
- Filter kategori memakai `whereHas('category', slug)`, tetapi belum menambahkan `is_active` pada kategori.
- `show(Product $product)` tidak melakukan guard terhadap produk, kategori, atau brand aktif.
- Produk terkait memakai `Product::active()` dan kategori yang sama, tetapi belum memastikan kategori/brand aktif.

File: `app/Http/Controllers/Pelanggan/DashboardController.php`

Kondisi saat ini:

- Kategori sudah memakai `ProductCategory::active()`.
- `withCount(['products' => fn ($query) => $query->active()])` belum memastikan produk yang dihitung punya brand aktif.
- Produk unggulan, terbaru, dan daftar produk dashboard memakai `Product::active()` tanpa filter kategori/brand aktif.

File: `app/Http/Controllers/Pelanggan/CartController.php`

Kondisi saat ini:

- `store()` dan `update()` bergantung pada `Product::isAvailable()`.
- `checkout()` juga mengecek `isAvailable()` pada produk yang dikunci.
- Karena `isAvailable()` belum mengecek kategori/brand, produk dari kategori atau brand nonaktif masih dapat dibeli.

File: `app/Http/Controllers/Pelanggan/WishlistController.php`

Kondisi saat ini:

- `toggle()` menerima `Product $product` tanpa guard aktif relasi.
- Produk dari kategori/brand nonaktif masih dapat ditambahkan ke wishlist jika URL/form masih tersedia.

### Area yang Perlu Diperhatikan

- Histori pesanan pelanggan tidak boleh difilter berdasarkan status aktif produk/kategori/brand saat ini, karena order item adalah data transaksi historis dan memakai snapshot nama/harga produk.
- Admin product index sebaiknya tetap bisa melihat seluruh produk, termasuk produk dengan kategori/brand nonaktif, agar admin dapat mengelola dan memperbaiki data.
- Form admin create/edit saat ini sudah mengambil kategori dan brand aktif saja. Perlu dipertahankan.

## Strategi Perubahan

### 1. Tambahkan Scope Produk yang Memastikan Relasi Aktif

Tambahkan scope baru di `Product`, misalnya `scopeVisibleToCustomers()` atau `scopeActiveCatalog()`.

Isi scope:

- Mulai dari `active()` atau `where('is_active', true)`.
- Tambahkan `whereHas('category', fn ($query) => $query->active())`.
- Tambahkan filter brand dengan logika:
  - produk tanpa brand tetap lolos: `whereNull('brand_id')`
  - produk dengan brand hanya lolos jika brand aktif: `orWhereHas('brand', fn ($query) => $query->active())`

Alasan membuat scope baru:

- Menghindari duplikasi filter di banyak controller.
- Memisahkan makna `active()` yang saat ini hanya status produk dari makna produk yang boleh tampil di katalog pelanggan.
- Mengurangi risiko memengaruhi area admin atau laporan yang masih perlu melihat produk nonaktif.

### 2. Perbarui Scope dan Method Ketersediaan untuk Transaksi

Perbarui `scopeAvailable()` agar juga memakai filter relasi aktif untuk kebutuhan pembelian.

Perbarui `isAvailable()` agar memuat atau mengecek relasi kategori dan brand aktif:

- Produk harus aktif.
- Stok lebih dari 0.
- Status harus `Product::STATUS_AVAILABLE`.
- Kategori harus ada dan aktif.
- Jika brand ada, brand harus aktif.
- Jika brand null, tetap valid.

Catatan implementasi:

- Untuk menghindari N+1, query daftar produk tetap harus memakai scope query, bukan hanya `isAvailable()` di loop.
- Untuk route model binding atau checkout item tunggal, `isAvailable()` dapat menjadi lapisan pengaman terakhir.

### 3. Perbarui Query Katalog Pelanggan

Perbarui `Pelanggan/ProductController`:

- `index()` memakai scope katalog pelanggan baru.
- Filter kategori juga memastikan kategori aktif.
- `show(Product $product)` melakukan abort 404 jika produk tidak boleh tampil di pelanggan.
- `relatedProducts` memakai scope katalog pelanggan baru.

Perbarui `Pelanggan/DashboardController`:

- Produk unggulan memakai scope katalog pelanggan baru.
- Produk terbaru memakai scope katalog pelanggan baru.
- Produk dashboard memakai scope katalog pelanggan baru.
- `withCount` kategori memakai scope katalog pelanggan baru agar jumlah produk aktif sesuai daftar yang bisa dilihat pelanggan.

Periksa `HomeController`:

- Jika controller ini masih dipakai, query produk perlu memakai scope katalog pelanggan baru.
- Jika tidak dipakai, minimal jangan sampai query lama menjadi sumber tampilan produk pelanggan di masa depan.

### 4. Perbarui Aksi Pelanggan yang Menerima Produk dari Route

Perbarui `Pelanggan/CartController`:

- `store()` tetap bisa memakai `isAvailable()`, setelah method tersebut diperkuat.
- `update()` dan `checkout()` otomatis ikut aman karena memakai `isAvailable()`.

Perbarui `Pelanggan/WishlistController`:

- Tambahkan guard sebelum menambah wishlist.
- Jika produk tidak boleh tampil di pelanggan, kembalikan error atau 404.

### 5. Perbarui Loading Cart Bila Diperlukan

Pertimbangan untuk cart:

- Jika produk sudah ada di cart lalu kategori/brand dinonaktifkan, item masih mungkin tampil di halaman cart karena relasi `items.product` diload langsung.
- Opsi paling aman untuk UX: tetap tampilkan item di cart, tetapi tidak bisa checkout dan tampilkan pesan produk tidak tersedia.
- Opsi lebih ketat: sembunyikan atau hapus otomatis item yang produknya tidak lagi valid.

Rekomendasi awal:

- Jangan hapus otomatis data cart tanpa aksi pengguna.
- Checkout harus gagal dengan pesan jelas melalui `isAvailable()`.
- Jika dibutuhkan, tambahkan indikator di view cart pada tahap lanjutan.

### 6. Pengujian yang Perlu Dilakukan

Minimal skenario manual atau automated test:

- Produk aktif, kategori aktif, brand aktif muncul di daftar produk pelanggan.
- Produk aktif, kategori nonaktif tidak muncul di daftar produk pelanggan.
- Produk aktif, kategori aktif, brand nonaktif tidak muncul di daftar produk pelanggan.
- Produk aktif tanpa brand dan kategori aktif tetap muncul.
- URL detail produk dengan kategori nonaktif menghasilkan 404 atau tidak dapat diakses pelanggan.
- URL detail produk dengan brand nonaktif menghasilkan 404 atau tidak dapat diakses pelanggan.
- Produk dengan kategori/brand nonaktif tidak bisa ditambahkan ke cart.
- Produk yang sudah ada di cart sebelum kategori/brand dinonaktifkan tidak bisa checkout.
- Histori pesanan tetap menampilkan order item lama walaupun produk/kategori/brand sudah nonaktif.
- Admin tetap dapat melihat semua produk di halaman admin produk.

## File yang Direncanakan Berubah

- `app/Models/Product.php`
- `app/Http/Controllers/Pelanggan/ProductController.php`
- `app/Http/Controllers/Pelanggan/DashboardController.php`
- `app/Http/Controllers/Pelanggan/CartController.php` jika perlu pesan/guard tambahan
- `app/Http/Controllers/Pelanggan/WishlistController.php`
- `app/Http/Controllers/HomeController.php` jika controller masih dipakai atau ingin menjaga konsistensi query

## Risiko dan Batasan

- Mengubah `Product::active()` secara langsung berisiko memengaruhi area admin, laporan, atau seeder yang maknanya hanya membutuhkan produk aktif secara internal. Karena itu lebih aman membuat scope baru khusus katalog pelanggan.
- Produk yang sudah ada di cart masih akan terlihat jika relasi cart diload langsung. Ini tidak otomatis salah, tetapi checkout harus tetap diblokir.
- Histori pesanan harus tetap berbasis data order item, bukan status aktif produk saat ini.

## Urutan Implementasi yang Disarankan

1. Tambahkan scope katalog pelanggan di `Product`.
2. Perkuat `scopeAvailable()` dan `isAvailable()` agar kategori/brand nonaktif dianggap tidak tersedia untuk pelanggan.
3. Ganti query katalog pelanggan di `ProductController` dan `DashboardController` agar memakai scope baru.
4. Tambahkan guard di `show()` produk pelanggan.
5. Tambahkan guard di wishlist toggle.
6. Periksa `HomeController` untuk konsistensi query.
7. Jalankan syntax check PHP pada file yang berubah.
8. Jalankan test Laravel yang tersedia atau minimal smoke test route katalog/produk/cart.
