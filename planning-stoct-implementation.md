# Planning Implementasi Perubahan Stock Product

## Ringkasan Analisis Codebase

Project ini adalah aplikasi Laravel ecommerce dengan pemisahan fitur utama:

- Admin: kelola produk, kategori, brand, order, payment, dashboard.
- Pelanggan: katalog produk, detail produk, wishlist, cart, checkout, order.
- GM/Direktur: dashboard dan laporan penjualan.
- Database: tabel produk, cart, order, payment, user address, dan tabel master lain.

Alur pembelian saat ini berada terutama di:

- `app/Http/Controllers/Pelanggan/CartController.php`
- `app/Models/Product.php`
- `app/Models/Cart.php`
- `app/Models/CartItem.php`
- `app/Models/Order.php`
- `app/Models/OrderItem.php`
- view cart/checkout/product di `resources/views/pelanggan`

Struktur database product saat ini dibuat oleh:

- `database/migrations/2026_04_30_141259_create_products_table.php`

Saat ini field `products.stock` bertipe `boolean` dengan komentar `1 tersedia, 0 tidak tersedia`. Artinya `stock` masih dipakai sebagai status ketersediaan, bukan jumlah stok. Banyak fitur mengecek `stock > 0` atau `(int) stock === 1` untuk menentukan produk tersedia.

## Kondisi Database Saat Ini

Tabel `products` saat ini memiliki field penting:

- `id`
- `category_id`
- `brand_id`
- `name`
- `slug`
- `description`
- `price`
- `stock` boolean, default true
- `weight`
- `thickness`
- `dimensions`
- `specifications`
- `is_featured`
- `is_active`
- timestamps
- soft deletes

Tabel pembelian yang relevan:

- `cart_items.quantity`: jumlah item di keranjang.
- `order_items.quantity`: jumlah item yang dibeli.
- `orders.status`: status order.
- `payments.status`: status pembayaran.

## Target Perubahan

Perubahan yang diminta:

1. Field `stock` di tabel `products` diubah fungsi menjadi data kuantitas atau jumlah produk yang tersedia.
2. Field `stock` akan otomatis berkurang saat pelanggan melakukan pembelian, berdasarkan `cart_items.quantity` atau `order_items.quantity`.
3. Tambah field `status` di tabel `products` dengan nilai `tersedia` dan `tidak tersedia`.
4. Field `status` otomatis menjadi `tidak tersedia` ketika `stock` produk `0`.
5. Semua fitur terdampak harus disesuaikan.

Catatan: user menulis `stoct`, tetapi field existing di database adalah `stock`. File planning tetap mengikuti nama file yang diminta: `planning-stoct-implementation.md`.

## Strategi Database

### 1. Migration baru untuk products

Buat migration baru, misalnya:

`database/migrations/YYYY_MM_DD_HHMMSS_update_stock_and_status_on_products_table.php`

Isi perubahan:

- Ubah `products.stock` dari boolean menjadi unsigned integer.
- Tambahkan `products.status` enum/string dengan nilai:
  - `tersedia`
  - `tidak tersedia`
- Default `stock` sebaiknya `0`.
- Default `status` sebaiknya `tidak tersedia`.
- Tambahkan index pada `status` bila sering dipakai untuk filter/listing.

Rencana transformasi data lama:

- Data lama `stock = 1` berarti tersedia. Karena tidak ada angka stok asli, set menjadi `1`.
- Data lama `stock = 0` berarti tidak tersedia. Set menjadi `0`.
- Set `status = tersedia` jika `stock > 0`.
- Set `status = tidak tersedia` jika `stock = 0`.

Perhatian teknis:

- Laravel migration untuk mengubah tipe kolom mungkin butuh `doctrine/dbal`, tergantung versi Laravel dan database driver.
- Alternatif aman: gunakan SQL raw sesuai database yang dipakai.
- Perlu cek `.env`/driver database sebelum implementasi final.

### 2. Konsistensi status otomatis

Ada dua opsi:

- Opsi A, business logic di model/controller: setiap perubahan `stock` harus mengatur `status`.
- Opsi B, database generated column/check constraint/trigger.

Rekomendasi untuk codebase ini: Opsi A terlebih dahulu karena pola aplikasi masih sederhana dan berbasis Eloquent.

Implementasi:

- Di model `Product`, tambah helper/mutator atau method:
  - `syncStatusFromStock()`
  - `isAvailable()`
  - `decreaseStock(int $quantity)`
- Saat create/update produk oleh admin, status ditentukan dari nilai stock.
- Saat checkout, setelah stock dikurangi, status disinkronkan.

## Perubahan Model

File: `app/Models/Product.php`

Rencana perubahan:

- Ubah cast `stock` dari `boolean` menjadi `integer`.
- Tambahkan `status` ke `$fillable`.
- Tambahkan cast/status handling bila perlu.
- Tambahkan konstanta status agar tidak tersebar string literal:
  - `STATUS_AVAILABLE = 'tersedia'`
  - `STATUS_UNAVAILABLE = 'tidak tersedia'`
- Tambahkan scope:
  - `scopeAvailable($query)` untuk `status = tersedia` dan `stock > 0`.
- Tambahkan method:
  - `public function isAvailable(): bool`
  - `public function syncStatusFromStock(): void`
  - `public function reduceStock(int $quantity): void`

Aturan utama:

- Produk tersedia jika `is_active = true`, `stock > 0`, dan `status = tersedia`.
- Jika `stock <= 0`, paksa `stock = 0` dan `status = tidak tersedia`.
- Jika admin mengisi `stock > 0`, status otomatis `tersedia`.

## Perubahan Admin Product

File terdampak:

- `app/Http/Controllers/Admin/ProductController.php`
- `resources/views/admin/products/partials/form.blade.php`
- `resources/views/admin/products/index.blade.php`

Rencana:

- Validasi `stock` di store/update:
  - dari `required|in:0,1`
  - menjadi `required|integer|min:0`
- `status` tidak perlu input manual jika status selalu mengikuti stock.
- Jika tetap ingin admin bisa melihat status, tampilkan sebagai readonly/badge.
- Form product:
  - ubah select stock tersedia/tidak tersedia menjadi input number jumlah stok.
  - label menjadi `Stock Quantity` atau `Jumlah Stok`.
  - `min="0"`, `step="1"`.
- Listing admin:
  - kolom `Stock` menampilkan angka stok.
  - kolom `Status` menampilkan `status` produk.
  - `is_active` bisa tetap ditampilkan sebagai kolom terpisah, misalnya `Active`.
- Dashboard admin:
  - `availableProducts = Product::where('status', 'tersedia')->where('stock', '>', 0)->count()`
  - Label bisa disesuaikan menjadi jumlah produk tersedia.

## Perubahan Katalog Pelanggan

File terdampak:

- `app/Http/Controllers/Pelanggan/ProductController.php`
- `app/Http/Controllers/Pelanggan/DashboardController.php`
- `app/Http/Controllers/HomeController.php`
- `resources/views/pelanggan/products/index.blade.php`
- `resources/views/pelanggan/products/show.blade.php`
- `resources/views/pelanggan/dashboard.blade.php`

Rencana:

- Semua pengecekan ketersediaan dari `$product->stock > 0` tetap bisa dipakai, tetapi sebaiknya diganti ke `$product->isAvailable()` atau kombinasi `status` dan `stock`.
- Badge produk:
  - `IN STOCK` jika `status = tersedia` dan `stock > 0`.
  - `OUT OF STOCK` jika `stock = 0` atau `status = tidak tersedia`.
- Detail produk:
  - tampilkan angka stok, misalnya `Stock: 12`.
  - tombol quantity tidak boleh melewati jumlah stock.
  - input quantity diberi `max="{{ $product->stock }}"`.
  - tombol add to cart disabled ketika tidak tersedia.
- Related products:
  - tombol add to cart perlu menjadi form seperti list produk, bukan button statis.
  - jika stok habis, tombol disabled.

Opsional:

- Query katalog bisa tetap menampilkan produk habis dengan badge `OUT OF STOCK`.
- Jika ingin hanya menampilkan produk yang tersedia, gunakan scope `available()`. Ini perlu keputusan bisnis.

## Perubahan Cart

File terdampak:

- `app/Http/Controllers/Pelanggan/CartController.php`
- `resources/views/pelanggan/cart/index.blade.php`
- `resources/views/pelanggan/cart/checkout.blade.php`

Rencana store cart:

- Validasi quantity tetap `integer|min:1`.
- Tambahkan validasi jumlah:
  - quantity yang ditambahkan + quantity existing di cart tidak boleh melebihi `product.stock`.
- Produk boleh masuk cart hanya jika:
  - `is_active = true`
  - `status = tersedia`
  - `stock > 0`

Rencana update cart:

- Quantity baru tidak boleh melebihi `cartItem.product.stock`.
- Input quantity di view cart diberi `max="{{ $item->product->stock }}"`.
- Tampilkan info stok tersedia di baris item.
- Jika stok produk berubah dan quantity cart sudah melebihi stok, tampilkan error saat update/checkout.

Rencana checkout form:

- Sebelum menampilkan checkout, validasi semua item masih tersedia dan quantity tidak melebihi stok.
- Jika ada item tidak valid, redirect ke cart dengan pesan error.

## Perubahan Checkout dan Pengurangan Stock

File utama:

- `app/Http/Controllers/Pelanggan/CartController.php`

Saat ini checkout sudah memakai transaction dan `lockForUpdate()` pada produk. Ini harus dipertahankan.

Rencana perubahan di dalam `DB::transaction`:

1. Ambil semua produk dari item cart dengan `lockForUpdate()`.
2. Untuk setiap item:
   - pastikan produk ada.
   - pastikan `is_active = true`.
   - pastikan `status = tersedia`.
   - pastikan `stock >= item.quantity`.
3. Hitung subtotal.
4. Buat order dan order items.
5. Kurangi stock setiap produk:
   - `newStock = product.stock - item.quantity`
   - jika `newStock <= 0`, set `stock = 0` dan `status = tidak tersedia`
   - jika `newStock > 0`, set `stock = newStock` dan `status = tersedia`
6. Buat payment.
7. Hapus cart items.

Catatan keputusan bisnis:

- Stock dikurangi saat order dibuat/checkout berhasil, bukan saat pembayaran diverifikasi.
- Jika nanti ada fitur cancel/reject order, perlu keputusan apakah stock dikembalikan. Saat ini request hanya menyebut pengurangan saat pembelian.

## Perubahan GM/Direktur/Laporan

File terdampak minimal:

- `app/Http/Controllers/GM/DashboardController.php`
- `app/Http/Controllers/Direktur/DashboardController.php`
- `app/Http/Controllers/GM/ReportController.php`
- `app/Http/Controllers/Direktur/ReportController.php`

Analisis:

- Modul GM/Direktur saat ini lebih fokus produk aktif dan top products dari `order_items.quantity`.
- Tidak banyak bergantung ke `products.stock`.
- Jika ada label `produk aktif`, tetap bisa memakai `is_active`.
- Jika ingin statistik stok, bisa tambah metrik stok rendah/habis, tetapi ini di luar request utama.

## Perubahan Test

File test yang sudah terdampak:

- `tests/Feature/Pelanggan/CheckoutTest.php`
- `tests/Feature/Pelanggan/OrderListTest.php`
- `tests/Feature/Admin/OrderFilterTest.php`

Rencana update test existing:

- Data produk test dari `stock => 1` menjadi stock kuantitas realistis, misalnya `stock => 5`.
- Tambahkan `status => tersedia` jika migration/model belum otomatis mengisi.
- Assertion checkout:
  - jika cart quantity `2` dan stock awal `5`, stock akhir harus `3`.
  - jika stock awal `2` dan quantity `2`, stock akhir `0` dan status `tidak tersedia`.

Test baru yang disarankan:

- Pelanggan tidak bisa add to cart jika quantity melebihi stock.
- Pelanggan tidak bisa update cart melebihi stock.
- Checkout gagal jika stok kurang dari quantity cart.
- Checkout mengurangi stock sesuai quantity.
- Checkout mengubah status menjadi `tidak tersedia` ketika stock menjadi `0`.
- Admin create/update product dengan stock `0` otomatis status `tidak tersedia`.
- Admin create/update product dengan stock lebih dari `0` otomatis status `tersedia`.

## Urutan Implementasi yang Disarankan

1. Buat migration update `products.stock` dan tambah `products.status`.
2. Update model `Product`:
   - fillable
   - casts
   - konstanta status
   - helper availability/status sync.
3. Update admin product:
   - validasi stock integer
   - form input stock quantity
   - listing stock dan status.
4. Update cart:
   - add to cart validasi stok.
   - update cart validasi stok.
   - checkout form validasi stok.
5. Update checkout:
   - validasi stok di transaction.
   - kurangi stok per item.
   - sync status setelah pengurangan.
6. Update katalog/detail produk:
   - badge status.
   - quantity max sesuai stock.
   - tombol disabled ketika stok habis.
7. Update dashboard/admin count yang masih menganggap `stock` boolean.
8. Update test fixture dan tambah test untuk stok/status.
9. Jalankan test:
   - `php artisan test`
   - minimal: `php artisan test --filter=CheckoutTest`
10. Cek UI manual untuk:
   - admin create/edit product.
   - product listing pelanggan.
   - product detail.
   - cart update.
   - checkout.

## Risiko dan Hal yang Perlu Diputuskan

- Data lama hanya punya boolean stock, sehingga nilai quantity asli tidak bisa diketahui. Default migrasi paling aman: tersedia menjadi `1`, tidak tersedia menjadi `0`.
- Perlu keputusan apakah produk `stock = 0` tapi `is_active = true` tetap tampil di katalog sebagai `OUT OF STOCK`.
- Perlu keputusan apakah stock dikembalikan saat order dibatalkan atau pembayaran ditolak.
- Perlu keputusan apakah admin boleh mengatur `status` manual. Rekomendasi: tidak, karena status harus konsisten dengan stock.
- Perlu pastikan database driver sebelum menulis migration perubahan tipe kolom.

## Estimasi File Terdampak

Database:

- `database/migrations/2026_04_30_141259_create_products_table.php`
- migration baru untuk alter products.

Model:

- `app/Models/Product.php`

Controller:

- `app/Http/Controllers/Admin/ProductController.php`
- `app/Http/Controllers/Admin/DashboardController.php`
- `app/Http/Controllers/Pelanggan/CartController.php`
- `app/Http/Controllers/Pelanggan/ProductController.php`
- `app/Http/Controllers/Pelanggan/DashboardController.php`
- kemungkinan `app/Http/Controllers/HomeController.php`

View:

- `resources/views/admin/products/partials/form.blade.php`
- `resources/views/admin/products/index.blade.php`
- `resources/views/admin/dashboard.blade.php`
- `resources/views/pelanggan/products/index.blade.php`
- `resources/views/pelanggan/products/show.blade.php`
- `resources/views/pelanggan/cart/index.blade.php`
- `resources/views/pelanggan/cart/checkout.blade.php`
- kemungkinan `resources/views/pelanggan/dashboard.blade.php`

Test:

- `tests/Feature/Pelanggan/CheckoutTest.php`
- `tests/Feature/Pelanggan/OrderListTest.php`
- `tests/Feature/Admin/OrderFilterTest.php`
- test baru untuk validasi stock/status.

## Kriteria Selesai

Implementasi dianggap selesai jika:

- `products.stock` menyimpan angka quantity.
- `products.status` tersedia dan selalu sinkron dengan stock.
- Checkout mengurangi stock sesuai jumlah pembelian.
- Stock tidak bisa minus.
- Produk dengan stock `0` otomatis berstatus `tidak tersedia`.
- Pelanggan tidak bisa membeli quantity melebihi stock.
- Admin bisa input jumlah stock produk.
- UI admin/pelanggan menampilkan stock dan status dengan benar.
- Test checkout dan test validasi stock lulus.
