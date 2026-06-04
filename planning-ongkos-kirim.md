# Planning Implementasi Ongkos Kirim Palembang dan Konfirmasi Admin

## Tujuan
Menambahkan alur ongkos kirim pada checkout:
- Jika alamat pengiriman berada di Kota Palembang, ongkos kirim otomatis `Rp 20.000`.
- Jika alamat pengiriman bukan Kota Palembang, pesanan tetap dibuat tetapi pelanggan harus menunggu admin menginput ongkos kirim manual.
- Pelanggan mendapat informasi yang jelas ketika ongkos kirim belum tersedia dan belum boleh mengunggah bukti pembayaran sebelum total pembayaran final.

## Kondisi Saat Ini
- Checkout diproses di `app/Http/Controllers/Pelanggan/CartController.php`.
- Saat checkout berhasil, aplikasi langsung membuat:
  - record `orders`
  - record `order_items`
  - record `payments`
  - mengurangi stok produk
  - menghapus item keranjang
- Nilai ongkos kirim saat ini selalu:
  - `orders.shipping_cost = 0`
  - `orders.total_amount = subtotal`
  - `payments.amount = subtotal`
- Data alamat pengiriman disimpan denormalized di tabel `orders`:
  - `shipping_province`
  - `shipping_city`
  - `shipping_district`
  - `shipping_village`
  - dan field alamat lain.
- Jika pelanggan memilih alamat tersimpan, data kota diambil dari relasi `UserAddress -> regency`.
- Halaman upload bukti pembayaran memakai `order->total_amount`, sehingga flow ongkir manual harus mencegah pelanggan upload bukti pembayaran sebelum admin mengisi ongkir.

## Masalah yang Harus Diselesaikan
- Order non-Palembang perlu status khusus agar tidak dianggap siap dibayar.
- Admin perlu tempat untuk menginput ongkos kirim manual.
- Total order dan amount pembayaran harus diperbarui setelah ongkir manual diinput.
- Pelanggan perlu melihat pesan bahwa ongkir sedang menunggu konfirmasi admin.
- Tombol dan route upload bukti pembayaran harus diblokir ketika ongkir belum dikonfirmasi.

## Keputusan Desain yang Direkomendasikan

### 1. Tambah status pesanan baru
Tambahkan status baru pada `orders.status`:
- `menunggu_konfirmasi_ongkir`

Label:
- `Menunggu Konfirmasi Ongkir`

Status ini dipakai untuk order non-Palembang sampai admin mengisi ongkos kirim.

Alasan:
- Pola aplikasi saat ini sudah memakai status order untuk membedakan tahap proses.
- Lebih mudah difilter admin dan lebih jelas di sisi pelanggan.

### 2. Tambah kolom pendukung ongkir
Tambahkan migration untuk tabel `orders`:
- `shipping_cost_status` enum/string dengan nilai:
  - `fixed`
  - `waiting_admin`
  - `confirmed`
- `shipping_cost_confirmed_at` timestamp nullable
- `shipping_cost_confirmed_by` foreign id nullable ke `users`

Rekomendasi nilai:
- Order Palembang:
  - `shipping_cost = 20000`
  - `shipping_cost_status = fixed`
  - `shipping_cost_confirmed_at = now()`
  - `status = belum_dibayar`
- Order non-Palembang:
  - `shipping_cost = 0`
  - `shipping_cost_status = waiting_admin`
  - `shipping_cost_confirmed_at = null`
  - `status = menunggu_konfirmasi_ongkir`

Catatan:
- Bisa saja hanya memakai `status`, tetapi kolom `shipping_cost_status` membuat validasi dan tampilan lebih eksplisit.
- Jika ingin scope lebih kecil, kolom minimal yang wajib adalah status baru dan `shipping_cost`, tetapi rekomendasi di atas lebih aman untuk audit.

### 3. Deteksi Kota Palembang
Buat helper method di `Order` atau service kecil, misalnya:
- `Order::isPalembangShippingCity(string $city): bool`

Aturan normalisasi:
- Ubah ke lowercase.
- Trim spasi.
- Anggap Palembang jika nama kota sama dengan `palembang` atau mengandung `kota palembang`.

Contoh:
- `Palembang` => Palembang
- `Kota Palembang` => Palembang
- `KOTA PALEMBANG` => Palembang
- `Kabupaten Banyuasin` => bukan Palembang

Catatan penting:
- Data `regencies.name` kemungkinan berisi nama formal dari seed wilayah. Karena itu pengecekan harus toleran terhadap variasi huruf besar/kecil dan prefix `Kota`.

## Alur Checkout Baru

### A. Alamat Palembang
1. Pelanggan checkout dengan alamat kota Palembang.
2. Sistem menghitung:
   - `subtotal = total produk`
   - `shipping_cost = 20000`
   - `total_amount = subtotal + 20000`
3. Sistem membuat order dengan status `belum_dibayar`.
4. Sistem membuat payment dengan:
   - `amount = total_amount`
   - `status = menunggu`
5. Pelanggan diarahkan ke daftar/detail pesanan dan dapat langsung upload bukti pembayaran.

### B. Alamat Bukan Palembang
1. Pelanggan checkout dengan alamat luar Palembang.
2. Sistem membuat order dengan:
   - `shipping_cost = 0`
   - `total_amount = subtotal`
   - `shipping_cost_status = waiting_admin`
   - `status = menunggu_konfirmasi_ongkir`
3. Sistem tetap membuat payment awal atau menunda payment.

Rekomendasi:
- Tetap buat payment awal dengan `amount = subtotal`, `status = menunggu`, dan notes `Menunggu konfirmasi ongkos kirim admin.`
- Setelah admin mengisi ongkir, update `payments.amount = orders.total_amount`.

4. Pelanggan melihat informasi:
   - Ongkos kirim sedang menunggu konfirmasi admin.
   - Total pembayaran belum final.
   - Tombol upload bukti pembayaran disembunyikan/dinonaktifkan.

## Alur Admin Baru

### 1. Daftar Pesanan Admin
File target:
- `resources/views/admin/orders/index.blade.php`
- `app/Http/Controllers/Admin/OrderController.php`

Perubahan:
- Tambahkan status `menunggu_konfirmasi_ongkir` ke list filter status.
- Tampilkan badge khusus untuk pesanan yang menunggu ongkir.
- Tampilkan aksi menuju detail order agar admin mengisi ongkir.

### 2. Detail Pesanan Admin
File target:
- `resources/views/admin/orders/show.blade.php`
- `app/Http/Controllers/Admin/OrderController.php`
- `routes/web.php`

Perubahan:
- Jika `shipping_cost_status = waiting_admin`, tampilkan form input ongkos kirim:
  - `shipping_cost` required, numeric, min 0
  - optional notes jika ingin menyimpan catatan admin di `notes` atau kolom baru
- Submit ke route baru, misalnya:
  - `PATCH /admin/orders/{order}/shipping-cost`
  - name: `admin.orders.shipping-cost.update`
- Controller method baru:
  - `updateShippingCost(Request $request, Order $order)`

Logika `updateShippingCost`:
1. Validasi order masih `menunggu_konfirmasi_ongkir` atau `shipping_cost_status = waiting_admin`.
2. Validasi input ongkir.
3. Hitung:
   - `total_amount = subtotal - discount_amount + shipping_cost`
4. Update order:
   - `shipping_cost`
   - `total_amount`
   - `shipping_cost_status = confirmed`
   - `shipping_cost_confirmed_at = now()`
   - `shipping_cost_confirmed_by = auth()->id()`
   - `status = belum_dibayar`
5. Update payment terkait:
   - `amount = order.total_amount`
   - `notes = Menunggu pembayaran pelanggan setelah ongkos kirim dikonfirmasi.`
6. Redirect dengan pesan sukses.

## Alur Pelanggan Baru

### 1. Halaman Checkout
File target:
- `resources/views/pelanggan/cart/checkout.blade.php`

Perubahan:
- Tambahkan informasi ongkos kirim di ringkasan:
  - Palembang: tampilkan `Ongkos kirim Rp 20.000`.
  - Bukan Palembang/manual: tampilkan `Ongkos kirim akan dikonfirmasi admin`.
- Karena form manual saat ini memakai input teks kota, estimasi di frontend dapat memakai Alpine.js dari `shippingCity`.
- Info frontend hanya bersifat bantuan. Keputusan final tetap dihitung di backend.

### 2. Setelah Checkout
File target:
- `app/Http/Controllers/Pelanggan/CartController.php`

Pesan sukses:
- Palembang:
  - `Checkout berhasil. Silakan lakukan pembayaran sesuai total pesanan.`
- Non-Palembang:
  - `Checkout berhasil. Ongkos kirim untuk alamat Anda akan dikonfirmasi admin sebelum pembayaran.`

Rekomendasi redirect:
- Lebih baik redirect ke detail order baru:
  - `route('pelanggan.orders.show', $order)`
- Saat ini redirect ke `pelanggan.products.index`. Mengubah ke detail order akan membantu pelanggan langsung melihat instruksi status ongkir.

### 3. Daftar dan Detail Pesanan Pelanggan
File target:
- `resources/views/pelanggan/orders/index.blade.php`
- `resources/views/pelanggan/orders/show.blade.php`

Perubahan:
- Jika status `menunggu_konfirmasi_ongkir` atau `shipping_cost_status = waiting_admin`:
  - tampilkan alert bahwa ongkir masih menunggu input admin.
  - tampilkan subtotal dan label `Ongkos kirim: Menunggu konfirmasi`.
  - jangan tampilkan tombol `Unggah Bukti`.
- Jika ongkir sudah final:
  - tampilkan rincian:
    - Subtotal
    - Ongkos kirim
    - Total pembayaran
  - tampilkan tombol upload bukti pembayaran jika pembayaran belum terverifikasi.

### 4. Guard Upload Bukti Pembayaran
File target:
- `app/Http/Controllers/Pelanggan/OrderController.php`
- `resources/views/pelanggan/orders/payment-proof.blade.php`

Perubahan controller:
- Di `paymentProofForm`, jika order masih menunggu konfirmasi ongkir:
  - redirect ke detail/order index dengan error:
    - `Ongkos kirim belum dikonfirmasi admin. Silakan tunggu total pembayaran final.`
- Di `uploadPaymentProof`, ulangi guard yang sama agar tidak bisa bypass lewat POST langsung.

Perubahan view:
- Tambahkan breakdown subtotal, ongkir, total pembayaran.
- Pastikan halaman ini hanya bisa dipakai ketika ongkir final.

## File yang Perlu Diubah
- `database/migrations/*_add_shipping_cost_confirmation_to_orders_table.php`
- `database/migrations/*_add_waiting_shipping_cost_status_to_orders_table.php`
- `app/Models/Order.php`
- `app/Http/Controllers/Pelanggan/CartController.php`
- `app/Http/Controllers/Pelanggan/OrderController.php`
- `app/Http/Controllers/Admin/OrderController.php`
- `routes/web.php`
- `resources/views/pelanggan/cart/checkout.blade.php`
- `resources/views/pelanggan/orders/index.blade.php`
- `resources/views/pelanggan/orders/show.blade.php`
- `resources/views/pelanggan/orders/payment-proof.blade.php`
- `resources/views/admin/orders/index.blade.php`
- `resources/views/admin/orders/show.blade.php`
- `tests/Feature/Pelanggan/CheckoutTest.php`
- Tambahan test admin, misalnya `tests/Feature/Admin/ShippingCostConfirmationTest.php`

## Detail Database

### Migration status order
Karena `orders.status` dibuat sebagai enum dan migration status sebelumnya hanya menjalankan `ALTER TABLE` pada MySQL, tambahkan nilai baru:
- `menunggu_konfirmasi_ongkir`

Untuk MySQL:
```php
DB::statement("ALTER TABLE orders MODIFY status ENUM('menunggu_konfirmasi_ongkir', 'belum_dibayar', 'menunggu_verifikasi_pembayaran', 'pembayaran_dikonfirmasi', 'diproses', 'dikirim', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'belum_dibayar'");
```

Untuk SQLite/testing:
- Karena migration enum Laravel di SQLite cenderung menjadi string/check behavior berbeda, pastikan test berjalan dengan migration baru tanpa raw SQL khusus SQLite.

### Migration kolom ongkir
Contoh kolom:
```php
Schema::table('orders', function (Blueprint $table) {
    $table->string('shipping_cost_status', 30)->default('fixed')->after('shipping_cost');
    $table->timestamp('shipping_cost_confirmed_at')->nullable()->after('shipping_cost_status');
    $table->foreignId('shipping_cost_confirmed_by')->nullable()->after('shipping_cost_confirmed_at')->constrained('users')->nullOnDelete();
});
```

## Perubahan Model Order
Tambahkan fillable:
- `shipping_cost_status`
- `shipping_cost_confirmed_at`
- `shipping_cost_confirmed_by`

Tambahkan casts:
- `shipping_cost_confirmed_at => datetime`

Tambahkan label status:
- `menunggu_konfirmasi_ongkir => Menunggu Konfirmasi Ongkir`

Tambahkan badge:
- warna orange/amber untuk status menunggu ongkir.

Tambahkan helper:
- `isWaitingForShippingCost()`
- `hasFinalShippingCost()`
- `isPalembangShippingCity($city)`

## Testing

### Checkout pelanggan
Tambahkan/update test:
- Pelanggan checkout dengan kota `Palembang`:
  - order `shipping_cost = 20000`
  - order `total_amount = subtotal + 20000`
  - order `status = belum_dibayar`
  - payment `amount = total_amount`
- Pelanggan checkout dengan kota `Kota Palembang`:
  - dianggap Palembang.
- Pelanggan checkout dengan kota `Bandung`:
  - order `status = menunggu_konfirmasi_ongkir`
  - order `shipping_cost_status = waiting_admin`
  - tombol/instruksi upload bukti tidak tersedia.
- Checkout memakai alamat tersimpan Palembang:
  - kota dari relasi regency dipakai untuk kalkulasi ongkir.

### Guard pembayaran
Tambahkan test:
- Pelanggan tidak bisa membuka form upload bukti saat order menunggu ongkir.
- Pelanggan tidak bisa POST upload bukti saat order menunggu ongkir.
- Setelah admin input ongkir, pelanggan bisa membuka form upload bukti.

### Admin
Tambahkan test:
- Admin melihat order `menunggu_konfirmasi_ongkir` di daftar/filter.
- Admin bisa input ongkir manual.
- Setelah admin input ongkir:
  - `shipping_cost` tersimpan.
  - `total_amount` berubah.
  - `payment.amount` berubah.
  - `status` menjadi `belum_dibayar`.
  - `shipping_cost_confirmed_by` terisi admin.
- Admin tidak bisa input ongkir ulang pada order yang sudah final, kecuali nanti dibuat fitur revisi khusus.

## Urutan Implementasi
1. Buat migration untuk status baru `menunggu_konfirmasi_ongkir`.
2. Buat migration untuk kolom status/konfirmasi ongkir di `orders`.
3. Update `Order` model: fillable, casts, status label, badge, helper method.
4. Update checkout backend di `Pelanggan\CartController`:
   - tentukan Palembang/non-Palembang setelah data alamat final tersedia.
   - hitung `shipping_cost`, `total_amount`, status order, dan payment amount.
5. Update view checkout pelanggan untuk memberi informasi estimasi ongkir.
6. Update halaman order pelanggan untuk menampilkan alert menunggu ongkir dan rincian subtotal/ongkir/total.
7. Tambahkan guard di `Pelanggan\OrderController` untuk form dan upload bukti pembayaran.
8. Tambahkan route admin `admin.orders.shipping-cost.update`.
9. Update `Admin\OrderController` untuk method input ongkir manual.
10. Update halaman daftar/detail admin untuk status baru dan form ongkir.
11. Tambahkan/update feature tests.
12. Jalankan:
    - `php artisan test --filter=CheckoutTest`
    - test admin shipping cost baru
    - test order/payment terkait jika ada.

## Acceptance Criteria
- Order Palembang otomatis memiliki ongkir `Rp 20.000` dan total pembayaran sudah final.
- Order non-Palembang masuk ke database dengan status menunggu konfirmasi ongkir.
- Pelanggan non-Palembang melihat pesan bahwa ongkir akan diinput admin.
- Pelanggan non-Palembang tidak bisa upload bukti pembayaran sebelum admin mengisi ongkir.
- Admin bisa menginput ongkir manual dari detail pesanan.
- Setelah admin input ongkir, total order dan amount payment sinkron.
- Setelah ongkir dikonfirmasi admin, pelanggan bisa upload bukti pembayaran dengan total final.

## Risiko dan Catatan
- Saat ini checkout langsung mengurangi stok. Dengan flow menunggu ongkir, stok tetap ter-reserve sejak checkout. Ini konsisten dengan behavior sekarang, tetapi perlu dipahami jika banyak order luar Palembang tidak lanjut bayar.
- Jika admin mengisi ongkir sangat lama, pelanggan mungkin bingung. Alert di halaman order harus jelas.
- Jika ingin notifikasi otomatis, bisa ditambahkan nanti melalui tabel `notifications` yang sudah ada di model, tetapi tidak wajib untuk implementasi awal.
- Pastikan semua tampilan memakai istilah yang konsisten: `Ongkos kirim`, `Menunggu Konfirmasi Ongkir`, dan `Total pembayaran`.

## Scope Lanjutan Opsional
- Kirim notifikasi ke pelanggan setelah admin mengisi ongkir.
- Tambahkan filter cepat admin `Menunggu Ongkir`.
- Tambahkan batas waktu pembatalan otomatis jika pelanggan tidak membayar setelah ongkir dikonfirmasi.
- Buat konfigurasi ongkir Palembang `20000` di settings/config agar tidak hardcoded.

