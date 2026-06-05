# Planning Implementasi Fitur Diskon Promosi

## Ringkasan Kondisi Saat Ini

Project adalah aplikasi Laravel ecommerce dengan fitur cart, checkout, order, payment, dan modul marketing untuk promosi.

Fitur promosi sudah tersedia di sisi marketing:

- Model: `app/Models/Promotion.php`
- Migration: `database/migrations/2026_04_30_141343_create_promotions_table.php`
- CRUD: `app/Http/Controllers/Marketing/PromotionController.php`
- View form/list promosi: `resources/views/marketing/promotions/*`
- Banner promosi aktif tampil di dashboard pelanggan lewat `App\Http\Controllers\Pelanggan\DashboardController`

Namun promosi belum berdampak ke transaksi pelanggan. Pada checkout, `app/Http/Controllers/Pelanggan/CartController.php` masih selalu menyimpan:

- `discount_amount` = `0`
- `total_amount` = `subtotal + shipping_cost`
- `Payment.amount` = total tanpa potongan diskon

Tabel `orders` sebenarnya sudah punya kolom `discount_amount`, dan beberapa laporan marketing/GM/direktur sudah membaca total diskon dari kolom tersebut. Artinya struktur awal sudah disiapkan, tetapi kalkulasi dan penyimpanan diskon belum diimplementasikan.

## Temuan Struktur Database

### Tabel `promotions`

Kolom yang sudah ada:

- `id`
- `name`
- `code` nullable unique
- `description`
- `type`: `nominal` atau `percent`
- `value`
- `min_purchase`
- `max_discount`
- `start_date`
- `end_date`
- `is_active`
- `banner_image`
- `banner_url`
- `created_by`
- timestamps

Catatan:

- `Promotion` punya relasi `products()` melalui tabel pivot `promotion_product`, tetapi migration untuk tabel pivot tersebut belum ditemukan di codebase.
- Form promosi saat ini belum menyediakan pilihan produk. Jadi secara UI, promosi saat ini lebih cocok dianggap sebagai promosi global/cart-level.
- Kolom `code` sudah ada, tetapi checkout belum punya input kode voucher.

### Tabel `orders`

Kolom terkait transaksi yang sudah ada:

- `subtotal`
- `discount_amount`
- `shipping_cost`
- `shipping_cost_status`
- `total_amount`
- `payment_method_id`

Belum ada kolom untuk menyimpan promosi mana yang dipakai. Kalau ingin audit lebih baik, perlu ditambah:

- `promotion_id` nullable foreign key ke `promotions`
- Opsional snapshot: `promotion_code`, `promotion_name`, `promotion_type`, `promotion_value`

Snapshot penting karena data promosi bisa diedit setelah order dibuat. Tanpa snapshot, detail order historis bisa berubah makna ketika promosi lama diubah.

## Scope yang Disarankan

Implementasi tahap pertama sebaiknya membuat promosi aktif otomatis memotong total belanja saat checkout.

Aturan yang disarankan:

1. Promosi berlaku otomatis jika:
   - `is_active = true`
   - `start_date <= today`
   - `end_date >= today`
   - subtotal memenuhi `min_purchase` jika diisi
2. Promosi dihitung dari subtotal produk, bukan dari ongkos kirim.
3. Promosi tipe `percent`:
   - diskon = `subtotal * value / 100`
   - jika `max_discount` diisi, diskon dibatasi maksimal sebesar `max_discount`
4. Promosi tipe `nominal`:
   - diskon = `value`
5. Diskon tidak boleh melebihi subtotal.
6. Jika ada lebih dari satu promosi aktif yang memenuhi syarat, pilih promosi dengan nominal diskon terbesar.
7. Total akhir:
   - `total_amount = max(subtotal - discount_amount, 0) + shipping_cost`
8. Untuk pesanan luar Palembang yang ongkirnya menunggu admin:
   - saat checkout, `total_amount` sementara = `subtotal - discount_amount`
   - saat admin mengisi ongkir, total dihitung ulang dengan formula yang sama. Controller admin sudah memakai `subtotal - discount_amount + shipping_cost`, jadi cukup pastikan `discount_amount` benar sejak awal.

## Perubahan Database

Buat migration baru untuk menghubungkan order dengan promosi:

Nama contoh:

`database/migrations/YYYY_MM_DD_HHMMSS_add_promotion_snapshot_to_orders_table.php`

Isi kolom:

- `promotion_id` nullable constrained ke `promotions`, `nullOnDelete`
- `promotion_code` nullable string 50
- `promotion_name` nullable string 150
- `promotion_type` nullable string 20
- `promotion_value` nullable decimal 15,2

Alasan:

- `promotion_id` berguna untuk relasi dan laporan.
- Snapshot menjaga data order tetap akurat walaupun promosi diubah/dinonaktifkan setelah checkout.

Update model `App\Models\Order`:

- Tambahkan field baru ke `$fillable`
- Tambahkan cast `promotion_value`
- Tambahkan relasi `promotion(): BelongsTo`

Update model `App\Models\Promotion`:

- Tambahkan scope `activeNow()`
- Tambahkan method kalkulasi diskon, misalnya `calculateDiscount(float $subtotal): float`
- Tambahkan method eligibility, misalnya `isEligibleForSubtotal(float $subtotal): bool`

## Perubahan Backend

### 1. Buat service kalkulasi promosi

Disarankan membuat service kecil agar logika tidak menumpuk di controller:

`app/Services/PromotionDiscountService.php`

Tanggung jawab:

- Menerima subtotal cart.
- Mengambil promosi aktif pada tanggal checkout.
- Memfilter berdasarkan `min_purchase`.
- Menghitung diskon sesuai tipe.
- Memilih promosi terbaik.
- Mengembalikan data terstruktur:
  - `promotion`
  - `discount_amount`
  - `subtotal_after_discount`

Contoh output konsep:

```php
[
    'promotion' => $promotion,
    'discount_amount' => 25000,
    'subtotal_after_discount' => 275000,
]
```

### 2. Update `CartController::checkoutForm`

File:

`app/Http/Controllers/Pelanggan/CartController.php`

Tambahkan:

- Load cart items seperti sekarang.
- Hitung subtotal.
- Panggil `PromotionDiscountService`.
- Kirim hasil promosi ke view checkout, misalnya `$discountSummary`.

Tujuan:

- Pelanggan melihat diskon sebelum menekan checkout.
- Ringkasan checkout menampilkan subtotal, promosi, diskon, ongkir, dan estimasi total.

### 3. Update `CartController::checkout`

Pada transaksi checkout:

- Setelah subtotal dihitung dari produk yang di-lock, panggil service kalkulasi promosi memakai subtotal final.
- Simpan:
  - `promotion_id`
  - `promotion_code`
  - `promotion_name`
  - `promotion_type`
  - `promotion_value`
  - `discount_amount`
- Hitung:
  - `totalAmount = max($subtotal - $discountAmount, 0) + $shippingCost`
- Buat `Payment` dengan `amount = $totalAmount`

Penting:

- Kalkulasi harus tetap dilakukan ulang di dalam `DB::transaction`, bukan hanya memakai angka dari halaman checkout, supaya aman dari perubahan cart/stok/harga/promosi.
- Jangan percaya input hidden untuk jumlah diskon.

### 4. Update admin konfirmasi ongkir

File:

`app/Http/Controllers/Admin/OrderController.php`

Method `updateShippingCost()` sudah menghitung:

`subtotal - discount_amount + shippingCost`

Yang perlu dilakukan:

- Pastikan hasil total tidak negatif pada subtotal setelah diskon.
- Formula ideal:

```php
$totalAmount = max((float) $order->subtotal - (float) $order->discount_amount, 0) + $shippingCost;
```

## Perubahan UI

### Checkout pelanggan

File:

`resources/views/pelanggan/cart/checkout.blade.php`

Tambahkan di ringkasan kanan:

- Baris `Promosi`
- Nama/kode promosi yang digunakan jika ada
- Baris `Diskon`
- Total sementara setelah diskon

Contoh tampilan:

- Subtotal: Rp 300.000
- Promosi: Promo Lebaran
- Diskon: -Rp 30.000
- Ongkos kirim: Rp 20.000 / Menunggu konfirmasi admin
- Total pembayaran: Rp 290.000

Untuk luar Palembang, label total tetap bisa menjadi `Total sementara` karena ongkir menunggu admin.

### Detail order pelanggan

File:

`resources/views/pelanggan/orders/show.blade.php`

Tambahkan baris diskon di breakdown:

- Subtotal
- Diskon
- Ongkos kirim
- Total pembayaran

Jika `promotion_name` tersedia, tampilkan nama promosi kecil di bawah label diskon.

### Detail order admin

File:

`resources/views/admin/orders/show.blade.php`

Tambahkan baris diskon pada kartu shipping cost/total:

- Subtotal
- Diskon
- Ongkos kirim
- Total pembayaran

Ini membantu admin memahami kenapa amount payment lebih kecil dari subtotal + ongkir.

### List order/laporan

Opsional tahap pertama:

- Jika list order hanya menampilkan total, tidak wajib diubah.
- Laporan GM/direktur sudah menjumlahkan `discount_amount`, jadi akan otomatis lebih bermakna setelah checkout mengisi nilai diskon.

## Test yang Perlu Ditambah

Tambahkan ke:

`tests/Feature/Pelanggan/CheckoutTest.php`

Skenario minimal:

1. Checkout dengan promosi nominal aktif:
   - subtotal 300.000
   - diskon nominal 50.000
   - Palembang ongkir 20.000
   - total order/payment 270.000
2. Checkout dengan promosi persen aktif:
   - value 10%
   - subtotal 300.000
   - diskon 30.000
   - total sesuai formula
3. Promosi persen dengan `max_discount`:
   - value 50%
   - subtotal 300.000
   - max_discount 75.000
   - diskon hanya 75.000
4. Promosi tidak berlaku jika subtotal di bawah `min_purchase`.
5. Promosi tidak berlaku jika:
   - `is_active = false`
   - tanggal belum mulai
   - tanggal sudah selesai
6. Jika beberapa promosi aktif, sistem memilih diskon terbesar.
7. Checkout luar Palembang:
   - total sementara = subtotal - diskon
   - setelah admin input ongkir, total/payment menjadi subtotal - diskon + ongkir

Update test admin shipping cost jika perlu:

`tests/Feature/Admin/ShippingCostConfirmationTest.php`

Tambahkan case bahwa order dengan `discount_amount` tetap dihitung benar saat ongkir dikonfirmasi.

## Urutan Implementasi

1. Buat migration penambahan kolom snapshot promosi di `orders`.
2. Update model `Order` dengan fillable, casts, dan relasi `promotion`.
3. Update model `Promotion` dengan scope dan method kalkulasi.
4. Buat `PromotionDiscountService`.
5. Integrasikan service di `CartController::checkoutForm`.
6. Integrasikan service di `CartController::checkout` di dalam transaction.
7. Update formula admin `updateShippingCost()` agar memakai `max(subtotal - discount, 0) + shipping`.
8. Update view checkout pelanggan untuk menampilkan promosi dan diskon.
9. Update view detail order pelanggan.
10. Update view detail order admin.
11. Tambah feature test checkout diskon dan admin konfirmasi ongkir.
12. Jalankan test terkait:
    - `php artisan test --filter=CheckoutTest`
    - `php artisan test --filter=ShippingCostConfirmationTest`

## Catatan Lanjutan

Setelah tahap pertama stabil, fitur bisa dikembangkan menjadi:

- Input kode voucher di checkout memakai kolom `promotions.code`.
- Pembatasan promosi per produk atau kategori.
- Migration tabel `promotion_product` jika memang ingin memakai relasi produk yang sudah ada di model.
- Kuota penggunaan promosi.
- Batas penggunaan per pelanggan.
- Statistik performa promosi per order, bukan hanya total `discount_amount`.

Untuk kebutuhan sekarang, implementasi otomatis promosi global adalah jalur paling sesuai dengan struktur UI yang sudah ada, karena marketing sudah bisa membuat promosi tetapi pelanggan belum punya mekanisme memilih atau memasukkan kode promosi.
