# Planning Cek dan Penghapusan Tabel Delivery

> Status implementasi: selesai pada 11 Juni 2026. Model dan referensi runtime Delivery dihapus, test penghapusan alamat diperbarui, ERD diregenerasi, dan migration penghapusan sudah dijalankan pada MySQL `sarana_fittindo` sebagai batch 5.

Backup sebelum migration tersimpan di `storage/app/backups/sarana_fittindo_before_drop_deliveries_20260611.sql`.

Baseline sebelum dan sesudah migration tetap sama:

- `products`: 22 record.
- Total stok produk: 1.024.
- `product_images`: 22 record.
- `orders`: 7 record.
- `order_items`: 7 record.
- `deliveries`: dari 0 record menjadi tabel tidak ada.

Verifikasi akhir:

- Migration `2026_06_11_080000_drop_deliveries_table` berstatus `Ran` pada batch 5.
- 50 test terkait delivery, profil, checkout, pesanan, stok, dan auto-complete lulus dengan 409 assertion.
- Full test suite sebelumnya menghasilkan 132 test lulus dan 1 test marketing gagal karena kode promo `WEEKEND` tidak ditampilkan. Kegagalan tersebut dapat direproduksi secara terpisah dan tidak terkait perubahan delivery.
- Pencarian referensi runtime tidak menemukan model, relasi, controller, route, query, atau view yang masih menggunakan tabel `deliveries`.

## Tujuan

Memastikan apakah fitur `delivery` masih digunakan, lalu menghapus komponen yang tidak terpakai tanpa menghapus atau mengubah data produk, stok, gambar produk, maupun histori item pesanan.

## Ringkasan Hasil Analisis

### Kesimpulan

Tabel dan model `Delivery` adalah sisa rancangan lama dan saat ini tidak dipakai oleh alur bisnis aplikasi.

- Tidak ada `DeliveryController`.
- Tidak ada route khusus delivery.
- Tidak ada form atau halaman yang membuat, memperbarui, atau menampilkan record dari tabel `deliveries`.
- Tidak ada pemanggilan `Delivery::create()`, `delivery()->create()`, atau operasi tulis lain ke tabel `deliveries` di aplikasi.
- Checkout menyimpan alamat, kurir, layanan, ongkir, estimasi, dan snapshot tarif langsung pada tabel `orders`.
- Perubahan status kirim menggunakan `orders.status`, `orders.shipped_at`, dan kolom penyelesaian pada `orders`.
- View admin dan pelanggan menampilkan informasi pengiriman dari `orders`, bukan dari relasi `delivery`.
- Eager-load `delivery` di beberapa controller tidak digunakan oleh view.
- Satu-satunya penulisan record delivery ditemukan di test yang memasukkan data secara manual untuk menguji larangan penghapusan alamat.

Dengan kondisi ini, `deliveries` aman dijadikan kandidat penghapusan setelah audit data pada database aktif dilakukan.

### Catatan Database Aktif

Konfigurasi aplikasi menunjuk ke MySQL:

- Database: `sarana_fittindo`
- Host: `127.0.0.1`
- Port: `3306`

MySQL Laragon telah diaktifkan saat implementasi dan database aktif berhasil diverifikasi. Migration
`2026_06_11_080000_drop_deliveries_table.php` sudah tercatat sebagai batch 5.

File SQLite `database/seed-rerun-check.sqlite` hanya dapat dijadikan bukti tambahan, bukan sumber data aktif. Isinya:

- `products`: 8 record
- `orders`: 315 record
- `order_items`: 625 record
- `deliveries`: 0 record

## Checklist Implementasi

- [x] Backup penuh database MySQL dibuat sebelum migration.
- [x] Baseline produk, stok, gambar, pesanan, dan item pesanan dicatat.
- [x] Model `Delivery` dihapus.
- [x] Relasi `delivery()` dan `deliveries()` dihapus.
- [x] Eager-load delivery pada controller admin dan pelanggan dihapus.
- [x] Proteksi alamat berbasis tabel delivery dihapus.
- [x] UI `address-delete-blocked` dihapus.
- [x] Test alamat diganti dengan verifikasi snapshot pengiriman pada `orders`.
- [x] Test memastikan tabel `deliveries` tidak ada setelah seluruh migration dijalankan.
- [x] Migration maju dan rollback diuji.
- [x] Migration dijalankan pada MySQL `sarana_fittindo`.
- [x] ERD teks dan gambar diregenerasi tanpa entitas Delivery.
- [x] Data produk sebelum dan sesudah migration dibandingkan dan tidak berubah.

## Struktur Database yang Relevan

### Data Produk

Data input produk berada pada jalur berikut:

- `products`: data utama produk, harga, stok, berat, dan status.
- `product_categories`: kategori produk.
- `product_brands`: merek produk.
- `product_images`: gambar produk, terhubung ke `products`.
- `cart_items`: produk yang sedang berada di keranjang.
- `wishlists`: produk yang disimpan pelanggan.
- `order_items`: produk yang sudah dipesan, termasuk snapshot nama dan harga produk.

### Data Pengiriman Aktif

Alur pengiriman yang benar-benar digunakan berada pada:

- `user_addresses`: daftar alamat tersimpan pelanggan.
- `orders.shipping_*`: snapshot alamat dan layanan pengiriman saat checkout.
- `orders.shipping_cost`, `shipping_cost_status`, dan `shipping_rate_snapshot`: data ongkir.
- `orders.status`, `shipped_at`, `completed_at`, dan kolom audit penyelesaian: status proses kirim.

### Tabel Delivery Lama

`deliveries` hanya memiliki foreign key ke:

- `orders.id` melalui `order_id`.
- `user_addresses.id` melalui `address_id`.

Tabel ini tidak memiliki foreign key langsung ke `products`, `product_images`, atau `order_items`. Menghapus tabel `deliveries` tidak akan melakukan cascade delete terhadap data produk maupun item pesanan.

## Referensi Delivery yang Harus Dibersihkan

1. `app/Models/Delivery.php`
   - Hapus model karena tidak mempunyai jalur penggunaan aktif.
   - Terdapat field `address` pada `$fillable` yang bahkan tidak ada pada migration tabel, memperkuat indikasi bahwa model sudah tertinggal.

2. `app/Models/Order.php`
   - Hapus method relasi `delivery()`.
   - Hapus import `HasOne` jika tidak lagi digunakan relasi lain.

3. `app/Models/UserAddress.php`
   - Hapus method relasi `deliveries()`.
   - Hapus import `HasMany` jika tidak lagi digunakan.

4. `app/Http/Controllers/Admin/OrderController.php`
   - Hapus `delivery` dari eager-load pada halaman index dan detail.

5. `app/Http/Controllers/Pelanggan/OrderController.php`
   - Hapus `delivery` dari eager-load histori dan detail pesanan.

6. `app/Http/Controllers/ProfileController.php`
   - Hapus pemeriksaan `$address->deliveries()->exists()`.
   - Alamat lama boleh dihapus karena histori alamat sudah tersimpan sebagai snapshot pada tabel `orders`.

7. `resources/views/profile/partials/user-addresses-form.blade.php`
   - Hapus pesan `address-delete-blocked` yang khusus bergantung pada delivery.
   - Pertahankan penjelasan bahwa penghapusan alamat tersimpan tidak mengubah riwayat pesanan.

8. `tests/Feature/ProfileTest.php`
   - Hapus atau ganti test `test_user_cannot_delete_address_linked_to_delivery`.
   - Ganti dengan test bahwa alamat yang pernah dipakai boleh dihapus dan snapshot alamat pada order tetap tidak berubah.

9. Generated ERD
   - Regenerasi `output.txt` dan `graph.png` agar entitas Delivery tidak lagi tercantum.

Catatan: ikon `mdi:truck-delivery-outline` pada navigasi hanyalah ikon menu pesanan/pengiriman dan bukan penggunaan model atau tabel `deliveries`. Ikon tersebut tidak perlu dihapus.

## Rencana Implementasi

### Fase 1 - Backup dan Audit Sebelum Perubahan

1. Aktifkan koneksi MySQL dan pastikan aplikasi mengarah ke database yang benar.
2. Buat backup database penuh sebelum migration.
3. Catat baseline berikut:
   - Jumlah record `products`.
   - Jumlah record `product_images`.
   - Jumlah record `order_items`.
   - Total stok seluruh produk.
   - Jumlah record `deliveries`.
   - Daftar `order_id` dan `address_id` yang ada pada `deliveries`, jika tabel tidak kosong.
4. Periksa apakah ada sistem eksternal, job, atau proses manual di luar repository yang menulis ke `deliveries`.
5. Jika `deliveries` berisi data penting yang tidak tersedia pada `orders`, ekspor tabel tersebut ke arsip sebelum dihapus.

Contoh query audit:

```sql
SELECT COUNT(*) AS product_count, COALESCE(SUM(stock), 0) AS total_stock
FROM products;

SELECT COUNT(*) AS product_image_count FROM product_images;
SELECT COUNT(*) AS order_item_count FROM order_items;
SELECT COUNT(*) AS delivery_count FROM deliveries;

SELECT order_id, address_id, courier, tracking_number, status,
       estimated_arrival, shipped_at, delivered_at
FROM deliveries
ORDER BY id;
```

### Fase 2 - Bersihkan Pemakaian di Aplikasi

1. Hapus eager-load `delivery` dari controller admin dan pelanggan.
2. Hapus relasi `delivery()` dari model `Order`.
3. Hapus relasi `deliveries()` dari model `UserAddress`.
4. Ubah proses hapus alamat agar tidak lagi memeriksa tabel delivery.
5. Hapus status UI `address-delete-blocked`.
6. Hapus model `Delivery`.
7. Jangan mengubah `Product`, `ProductController`, checkout item, pengurangan stok, atau relasi `order_items`.

### Fase 3 - Hapus Tabel dengan Migration Maju

1. Buat migration baru, misalnya:

```text
YYYY_MM_DD_HHMMSS_drop_deliveries_table.php
```

2. Method `up()` hanya menjalankan `Schema::dropIfExists('deliveries')`.
3. Method `down()` harus membuat kembali struktur `deliveries` agar rollback tetap terdefinisi.
4. Pertahankan migration historis `2026_04_30_141342_create_deliveries_table.php`.
5. Jangan memakai:
   - `php artisan migrate:fresh`
   - `php artisan migrate:refresh`
   - rollback massal
   - penghapusan manual seluruh database

Migration maju adalah pilihan paling aman karena hanya menghapus tabel anak `deliveries` dan tidak menyentuh `products`, `product_images`, `orders`, atau `order_items`.

Migration lama perlu dipertahankan supaya urutan migration tetap konsisten. Pada instalasi baru, tabel delivery akan dibuat oleh migration historis lalu dihapus oleh migration terbaru. Ini juga menjaga migration translasi status lama tetap dapat berjalan sesuai urutannya.

### Fase 4 - Perbarui Test

1. Tambahkan test bahwa checkout tidak membuat record delivery.
2. Tambahkan test bahwa penghapusan alamat tidak mengubah snapshot alamat pada order lama.
3. Pertahankan test checkout, stok produk, dan order item.
4. Jalankan minimal:

```bash
php artisan test tests/Feature/ProfileTest.php
php artisan test tests/Feature/Pelanggan/CheckoutTest.php
php artisan test tests/Feature/Pelanggan/OrderListTest.php
php artisan test tests/Feature/Admin/OrderFilterTest.php
php artisan test tests/Feature/Admin/AutoCompleteShippedOrdersTest.php
```

5. Jalankan seluruh test suite setelah test terkait lulus:

```bash
php artisan test
```

### Fase 5 - Verifikasi Migration dan Data

1. Uji `php artisan migrate` pada salinan database, bukan langsung pada satu-satunya database aktif.
2. Uji instalasi kosong untuk memastikan semua migration tetap dapat berjalan dari awal.
3. Pastikan tabel `deliveries` sudah tidak ada.
4. Bandingkan baseline sebelum dan sesudah:
   - Jumlah `products` harus sama.
   - Jumlah `product_images` harus sama.
   - Jumlah `order_items` harus sama.
   - Total stok produk harus sama.
   - Isi penting produk seperti nama, harga, stok, dan status tidak berubah.
5. Uji manual:
   - Daftar dan detail produk admin.
   - Daftar dan detail produk pelanggan.
   - Keranjang dan checkout.
   - Daftar dan detail pesanan admin.
   - Histori dan detail pesanan pelanggan.
   - Perubahan status pesanan menjadi `dikirim`.
   - Penghapusan alamat tersimpan.
6. Regenerasi ERD setelah seluruh verifikasi selesai.

## Kriteria Selesai

- Model `Delivery` sudah tidak ada.
- Tidak ada `DeliveryController`; tidak ada controller yang perlu dihapus.
- Tidak ada route, query, relasi, eager-load, test, atau UI yang bergantung pada `deliveries`.
- Tabel `deliveries` terhapus melalui migration baru.
- Snapshot pengiriman pada `orders` tetap utuh.
- Data `products`, `product_images`, dan `order_items` tidak berubah.
- Checkout, stok produk, pesanan, dan penghapusan alamat lulus test.
- ERD tidak lagi menampilkan Delivery.

## Strategi Rollback

Jika ditemukan masalah setelah deployment:

1. Hentikan perubahan lanjutan dan jangan menjalankan reset database.
2. Jalankan rollback hanya untuk migration penghapusan delivery.
3. Pastikan method `down()` membuat kembali tabel beserta foreign key yang diperlukan.
4. Pulihkan isi `deliveries` dari backup jika sebelumnya tabel berisi record.
5. Kembalikan referensi model dan relasi hanya jika aplikasi memang harus memakai data delivery lama.

Rollback tabel delivery tidak boleh memulihkan atau menimpa tabel produk karena kedua area tersebut tidak memiliki ketergantungan langsung.
