# Planning Keterangan Pembayaran Ditolak dan Restore Stok

## Ringkasan Temuan Codebase

Project menggunakan Laravel dengan alur checkout dan pembayaran terpisah antara pelanggan dan admin.

File utama yang relevan:

- `app/Http/Controllers/Pelanggan/CartController.php`
  - Method `checkout()` membuat `orders`, `order_items`, `payments`, lalu mengurangi stok produk dengan `Product::reduceStock()`.
  - Operasi checkout sudah dibungkus `DB::transaction()` dan produk dikunci memakai `lockForUpdate()`.

- `app/Http/Controllers/Admin/PaymentController.php`
  - Method `verify()` mengubah `payments.status` menjadi `terverifikasi` dan `orders.status` menjadi `pembayaran_dikonfirmasi`.
  - Method `reject()` mengubah `payments.status` menjadi `ditolak`, mengisi `rejection_reason` dari request, lalu mengubah `orders.status` menjadi `dibatalkan`.
  - Belum ada logic untuk mengembalikan stok saat pembayaran ditolak.

- `app/Models/Product.php`
  - Kolom `stock` dicast sebagai integer.
  - Method `reduceStock(int $quantity)` mengurangi stok dan memanggil `syncStatusFromStock()`.
  - Belum ada method kebalikan seperti `restoreStock()` atau `increaseStock()`.

- `app/Models/Payment.php`
  - Kolom `rejection_reason` sudah ada di `$fillable`.
  - Status payment yang dipakai: `menunggu`, `terverifikasi`, `ditolak`.

- `resources/views/admin/payments/index.blade.php`
  - Tombol `Tolak` sudah ada.
  - Belum ada input keterangan/alasan pada form penolakan.
  - Belum menampilkan keterangan penolakan di tabel pembayaran.

- `resources/views/admin/orders/show.blade.php`
  - Sudah menampilkan `rejection_reason` atau `notes` di area bukti pembayaran.

- `resources/views/pelanggan/orders/show.blade.php` dan `resources/views/pelanggan/orders/index.blade.php`
  - Masih menampilkan tombol `Bayar` selama payment belum `terverifikasi`.
  - Perlu dicegah agar order yang sudah `dibatalkan` karena payment ditolak tidak bisa upload ulang bukti tanpa reservasi stok baru.

## Struktur Database Relevan

### `products`

Migration awal `2026_04_30_141259_create_products_table.php` membuat `stock` sebagai boolean, lalu migration `2026_06_02_000000_update_products_stock_and_status.php` mengubah konsep stok menjadi:

- `stock` integer unsigned.
- `status` enum: `tersedia`, `tidak tersedia`.

Model `Product` otomatis menyinkronkan `status` berdasarkan `stock` saat saving.

### `orders`

Kolom penting:

- `id`
- `user_id`
- `order_number`
- `status`
- `subtotal`
- `discount_amount`
- `shipping_cost`
- `total_amount`
- `payment_method_id`
- `cancelled_by`
- `cancellation_reason`
- `cancelled_at`

Status order saat ini:

- `menunggu_konfirmasi_ongkir`
- `belum_dibayar`
- `menunggu_verifikasi_pembayaran`
- `pembayaran_dikonfirmasi`
- `diproses`
- `dikirim`
- `selesai`
- `dibatalkan`

### `order_items`

Kolom penting:

- `order_id`
- `product_id`
- `product_name`
- `product_price`
- `quantity`
- `subtotal`

Data ini menjadi sumber jumlah stok yang harus dikembalikan saat payment ditolak.

### `payments`

Kolom penting:

- `order_id`
- `payment_method_id`
- `amount`
- `proof_image`
- `transfer_date`
- `sender_name`
- `status`
- `verified_by`
- `verified_at`
- `rejection_reason`
- `notes`

Catatan: kolom `rejection_reason` sudah tersedia dan paling cocok dipakai sebagai kolom keterangan penolakan. Jika secara UI harus bernama "Keterangan", label form dapat memakai "Keterangan Penolakan" tanpa perlu menambah kolom database baru. Jika benar-benar wajib nama kolom database `keterangan`, perlu migration baru, tetapi itu akan menduplikasi fungsi `rejection_reason`.

## Tujuan Perubahan

1. Saat admin menolak pembayaran, stok semua produk pada order tersebut kembali ke sistem.
2. Admin wajib mengisi keterangan/alasan saat menolak pembayaran.
3. Keterangan penolakan tersimpan dan dapat dilihat admin serta pelanggan.
4. Restore stok harus idempotent, artinya tidak boleh menambah stok berkali-kali untuk payment/order yang sama.
5. Order yang payment-nya ditolak tidak boleh bisa upload ulang bukti pembayaran tanpa proses reservasi stok ulang.

## Rencana Implementasi

### 1. Tambahkan helper restore stok pada model `Product`

Tambahkan method baru di `app/Models/Product.php`, misalnya:

```php
public function restoreStock(int $quantity): void
{
    if ($quantity <= 0) {
        return;
    }

    $this->stock = (int) $this->stock + $quantity;
    $this->syncStatusFromStock();
}
```

Alasan:

- Mengikuti pola `reduceStock()`.
- Status produk otomatis berubah ke `tersedia` jika stok kembali lebih dari nol.
- Logic stok tetap berada di model produk.

### 2. Buat penanda agar stok tidak direstore dua kali

Pilihan yang disarankan: tambahkan kolom pada `orders`.

Migration baru:

- `stock_restored_at` timestamp nullable.
- Opsional `stock_restored_by` foreign key nullable ke `users`.

Contoh rancangan:

```php
Schema::table('orders', function (Blueprint $table) {
    $table->timestamp('stock_restored_at')->nullable()->after('cancelled_at');
    $table->foreignId('stock_restored_by')->nullable()->after('stock_restored_at')->constrained('users')->nullOnDelete();
});
```

Tambahkan ke `$fillable` dan `$casts` di `Order`.

Alasan:

- Payment hanya bisa ditolak ketika status `menunggu`, tetapi penanda eksplisit membuat restore stok aman dari perubahan flow di masa depan.
- Bisa diaudit kapan dan oleh siapa stok dikembalikan.

### 3. Ubah `Admin\PaymentController::reject()`

Perubahan utama:

- Validasi request:

```php
$validated = $request->validate([
    'rejection_reason' => ['required', 'string', 'max:1000'],
]);
```

- Bungkus proses dalam `DB::transaction()`.
- Lock payment, order, dan produk terkait.
- Pastikan payment masih `menunggu`.
- Update payment:
  - `status` = `ditolak`
  - `verified_by` = admin login
  - `verified_at` = now
  - `rejection_reason` = input keterangan
- Jika order belum pernah restore stok:
  - Load `order.items`.
  - Lock produk berdasarkan `product_id`.
  - Untuk setiap item, panggil `restoreStock($item->quantity)` lalu `save()`.
  - Isi `orders.stock_restored_at` dan `orders.stock_restored_by`.
- Update order:
  - `status` = `dibatalkan`
  - `cancelled_by` = admin login
  - `cancellation_reason` = `Pembayaran ditolak: {keterangan}`
  - `cancelled_at` = now

Catatan teknis:

- Gunakan `Product::whereIn(...)->lockForUpdate()` agar update stok aman dari checkout bersamaan.
- Restore stok sebaiknya berdasarkan `order_items.quantity`, bukan input user.

### 4. Tambahkan input keterangan pada form tolak admin

File: `resources/views/admin/payments/index.blade.php`.

Saat payment status `menunggu`, form `Tolak` perlu ditambah input:

- Label: `Keterangan Penolakan`
- Field: `textarea name="rejection_reason"`
- Required.
- Maksimal 1000 karakter mengikuti validasi.

Karena tabel pembayaran cukup padat, opsi UI:

- Sederhana: textarea kecil langsung di bawah tombol `Tolak`.
- Lebih rapi: modal konfirmasi tolak dengan textarea.

Untuk implementasi cepat dan minim perubahan, gunakan textarea langsung di form penolakan.

### 5. Tambahkan kolom/tampilan keterangan pada halaman admin pembayaran

File: `resources/views/admin/payments/index.blade.php`.

Tambahkan kolom atau baris kecil untuk menampilkan keterangan jika payment ditolak:

- Header: `Keterangan`
- Isi:
  - Jika `rejection_reason` ada, tampilkan teks.
  - Jika kosong, tampilkan `-`.

Jika tabel terlalu lebar, keterangan bisa diletakkan di bawah badge status agar tidak menambah lebar tabel.

### 6. Tampilkan keterangan penolakan pada pelanggan

File yang perlu diperbarui:

- `resources/views/pelanggan/orders/show.blade.php`
- `resources/views/pelanggan/orders/index.blade.php`
- Opsional `resources/views/pelanggan/orders/history.blade.php`

Tampilan yang disarankan:

- Jika `payment.status === 'ditolak'`, tampilkan alert merah:
  - Judul: `Pembayaran Ditolak`
  - Isi: `payment.rejection_reason`
- Jangan tampilkan tombol `Bayar` untuk order yang statusnya `dibatalkan`.

### 7. Cegah upload ulang bukti pembayaran pada order dibatalkan

File: `app/Http/Controllers/Pelanggan/OrderController.php`.

Tambahkan guard pada:

- `paymentProofForm()`
- `uploadPaymentProof()`

Contoh aturan:

```php
if ($order->status === 'dibatalkan') {
    return redirect()
        ->route('pelanggan.orders.show', $order)
        ->with('error', 'Pesanan sudah dibatalkan. Silakan buat pesanan baru.');
}
```

Alasan:

- Setelah payment ditolak, stok sudah kembali tersedia untuk pelanggan lain.
- Upload ulang bukti pada order lama tanpa mengurangi stok lagi dapat menyebabkan oversell.

### 8. Tambahkan test otomatis

Test utama sebaiknya dibuat di `tests/Feature/Admin`, misalnya `PaymentRejectionTest.php`.

Skenario test:

1. Admin menolak payment dengan keterangan.
   - `payments.status` menjadi `ditolak`.
   - `payments.rejection_reason` tersimpan.
   - `orders.status` menjadi `dibatalkan`.
   - `orders.cancelled_by`, `cancellation_reason`, dan `cancelled_at` terisi.
   - `products.stock` bertambah sesuai quantity `order_items`.
   - `products.status` kembali `tersedia` jika stock > 0.

2. Admin tidak bisa menolak tanpa keterangan.
   - Request gagal validasi.
   - Payment tetap `menunggu`.
   - Stok tidak berubah.

3. Restore stok tidak terjadi dua kali.
   - Jika endpoint reject dipanggil lagi atau payment sudah bukan `menunggu`, stok tetap sama.

4. Pelanggan tidak bisa upload ulang bukti pembayaran untuk order `dibatalkan`.
   - GET form payment proof redirect dengan error.
   - POST upload payment proof redirect dengan error.

5. UI admin menampilkan keterangan penolakan.
   - Halaman pembayaran atau detail order memuat teks `rejection_reason`.

## Urutan Eksekusi yang Disarankan

1. Tambah migration `stock_restored_at` dan `stock_restored_by` pada `orders`.
2. Update model `Order` untuk fillable/casts/relasi opsional `stockRestoredBy`.
3. Tambah method `restoreStock()` pada model `Product`.
4. Refactor `Admin\PaymentController::reject()` memakai validasi dan transaction.
5. Update form admin pembayaran agar ada input keterangan penolakan.
6. Update tampilan admin untuk menampilkan keterangan.
7. Update guard pelanggan agar order `dibatalkan` tidak bisa dibayar ulang.
8. Update tampilan pelanggan agar keterangan penolakan terlihat.
9. Tambah test feature untuk restore stok, keterangan, dan guard upload ulang.
10. Jalankan test terkait:

```bash
php artisan test --filter=PaymentRejectionTest
php artisan test --filter=CheckoutTest
php artisan test --filter=OrderListTest
```

## Catatan Keputusan

- Kolom database `payments.rejection_reason` sudah ada, jadi rencana ini memakai kolom tersebut sebagai "keterangan penolakan".
- Jika stakeholder tetap meminta nama fisik kolom `keterangan`, perlu keputusan tambahan karena akan overlap dengan `rejection_reason`.
- Status order setelah payment ditolak tetap `dibatalkan`, sesuai behavior existing controller.
- Pelanggan diarahkan membuat pesanan baru setelah penolakan, karena stok sudah dilepas kembali ke sistem.
