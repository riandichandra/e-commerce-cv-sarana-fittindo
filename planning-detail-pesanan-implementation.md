# Planning Implementasi Bukti Pembayaran dan Bukti Barang Diterima pada Detail Pesanan Admin

## Ringkasan Permintaan

Pada halaman detail pesanan admin, tampilkan:

1. Bukti pembayaran.
2. Bukti barang telah pelanggan terima.

Target halaman:

- `resources/views/admin/orders/show.blade.php`

## Ringkasan Analisis Codebase

Halaman detail pesanan admin dirender oleh:

- Controller: `app/Http/Controllers/Admin/OrderController.php`
- Method: `show(Order $order)`
- View: `resources/views/admin/orders/show.blade.php`
- Route: `admin.orders.show`

Controller saat ini:

```php
$order->load(['user', 'paymentMethod', 'payment', 'delivery']);
$items = $order->items()
    ->with('product')
    ->paginate(10, ['*'], 'items_page')
    ->withQueryString();
```

Relasi yang dibutuhkan sudah ada:

- `$order->payment`
- `$order->paymentMethod`
- `$order->received_image`

Artinya, untuk kebutuhan menampilkan bukti pembayaran dan bukti barang diterima, kemungkinan besar tidak perlu mengubah query controller.

## Struktur Database Terkait

### `payments`

Migration:

- `database/migrations/2026_04_30_141324_create_payments_table.php`

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

Model:

- `app/Models/Payment.php`

Relasi:

- `Payment belongsTo Order`
- `Payment belongsTo PaymentMethod`
- `Payment belongsTo verifiedBy/User`

Catatan:

- Bukti pembayaran disimpan pada `payments.proof_image`.
- File disimpan oleh pelanggan melalui `Pelanggan\OrderController::uploadPaymentProof()`.
- Path storage disimpan relatif terhadap disk public, misalnya `payment-proofs/xxx.png`.
- URL tampilan memakai:

```blade
asset('storage/' . $order->payment->proof_image)
```

### `orders`

Migration utama:

- `database/migrations/2026_04_30_141321_create_orders_table.php`

Migration bukti barang diterima:

- `database/migrations/2026_05_23_000000_add_received_image_to_orders_table.php`

Kolom penting:

- `order_number`
- `status`
- `received_image`
- `updated_at`

Model:

- `app/Models/Order.php`

Catatan:

- Bukti barang diterima disimpan pada `orders.received_image`.
- File diunggah oleh pelanggan saat pesanan berstatus `dikirim` dan pelanggan menandai selesai.
- Path disimpan relatif terhadap disk public, misalnya `received-product-photos/xxx.png`.
- URL tampilan memakai:

```blade
asset('storage/' . $order->received_image)
```

## Flow Data Saat Ini

### Bukti Pembayaran

File controller:

- `app/Http/Controllers/Pelanggan/OrderController.php`

Method:

- `uploadPaymentProof(Request $request, Order $order)`

Validasi:

- `sender_name`
- `transfer_date`
- `proof_image`
- `notes`

Storage:

```php
$proofPath = $request->file('proof_image')->store('payment-proofs', 'public');
```

Update:

```php
Payment::updateOrCreate(
    ['order_id' => $order->id],
    [
        'proof_image' => $proofPath,
        ...
    ]
);
```

### Bukti Barang Diterima

File controller:

- `app/Http/Controllers/Pelanggan/OrderController.php`

Method:

- `complete(Request $request, Order $order)`

Validasi:

- `received_image`

Storage:

```php
$receivedImagePath = $request->file('received_image')->store('received-product-photos', 'public');
```

Update:

```php
$order->update([
    'status' => 'selesai',
    'received_image' => $receivedImagePath,
]);
```

## Kondisi View Admin Saat Ini

File:

- `resources/views/admin/orders/show.blade.php`

Yang sudah ada:

1. Ringkasan order.
2. Data customer.
3. Status order.
4. Payment summary singkat:
   - metode pembayaran
   - status pembayaran
   - amount
5. Alamat pengiriman.
6. Ringkasan ongkir/total.
7. Section bukti penerimaan barang hanya muncul jika `$order->received_image` ada.
8. Tabel item pesanan.

Yang belum ada atau perlu dirapikan:

1. Bukti pembayaran belum ditampilkan di detail pesanan admin.
2. Bukti penerimaan barang hanya tampil saat ada file, belum ada empty state.
3. Bukti pembayaran sebaiknya tampil dalam panel khusus berisi:
   - status pembayaran
   - nama pengirim
   - tanggal transfer
   - nominal
   - tombol lihat bukti
   - preview gambar jika ada
4. Bukti barang diterima sebaiknya tampil berdampingan atau satu area dengan bukti pembayaran.

## Tujuan Perubahan

1. Admin dapat melihat bukti pembayaran langsung dari detail pesanan.
2. Admin dapat melihat bukti barang diterima langsung dari detail pesanan.
3. Jika bukti belum ada, tampilkan informasi kosong yang jelas, bukan section hilang total.
4. Tampilan tetap konsisten dengan card admin yang baru:
   - border halus
   - background putih
   - header section `bg-[#fff7f8]`
   - tombol/link jelas
5. Tidak mengubah flow verifikasi pembayaran.
6. Tidak mengubah flow pelanggan upload bukti pembayaran atau bukti barang diterima.

## Scope Implementasi

File utama:

- `resources/views/admin/orders/show.blade.php`

File opsional:

- `app/Http/Controllers/Admin/OrderController.php`
- `tests/Feature/Admin/OrderDetailEvidenceTest.php`

Controller kemungkinan tidak perlu diubah karena:

- `$order->payment` sudah di-load.
- `$order->received_image` berada langsung di model order.

Tidak termasuk scope:

- Menambah upload bukti dari sisi admin.
- Mengubah verifikasi pembayaran.
- Mengubah status order.
- Mengubah database.
- Menghapus atau memindahkan bukti di halaman pelanggan.

## Rencana Tampilan

Tambahkan section baru setelah ringkasan alamat/ongkir, sebelum tabel item pesanan.

Nama section:

- `Bukti Pesanan`

Layout:

- Grid 1 kolom pada mobile.
- Grid 2 kolom pada desktop.

Panel kiri:

- `Bukti Pembayaran`

Panel kanan:

- `Bukti Barang Diterima`

### Panel Bukti Pembayaran

Jika `$order->payment?->proof_image` ada:

Tampilkan:

- Preview gambar bukti pembayaran.
- Tombol `Lihat Bukti` membuka file di tab baru.
- Nama pengirim: `$order->payment?->sender_name ?? '-'`
- Tanggal transfer: `$order->payment?->transfer_date?->format('d M Y') ?? '-'`
- Nominal: `Rp {{ number_format($order->payment?->amount ?? 0, 0, ',', '.') }}`
- Status payment badge: `$order->payment?->status_label`
- Verifikator dan waktu verifikasi jika ada:
  - `$order->payment?->verifiedBy?->name`
  - `$order->payment?->verified_at`
- Catatan/rejection reason jika ada.

Jika bukti pembayaran belum ada:

- Empty state:
  - Icon `mdi:receipt-text-outline`
  - Judul `Bukti pembayaran belum diunggah.`
  - Subtext `Pelanggan belum mengirimkan bukti transfer untuk pesanan ini.`

### Panel Bukti Barang Diterima

Jika `$order->received_image` ada:

Tampilkan:

- Preview gambar bukti barang diterima.
- Tombol `Lihat Bukti` membuka file di tab baru.
- Tanggal upload:
  - Saat ini tidak ada kolom khusus waktu upload received image.
  - Bisa gunakan `$order->updated_at->format('d M Y H:i')` sebagai informasi sementara.
- Status order badge.

Jika bukti barang diterima belum ada:

- Empty state:
  - Icon `mdi:package-variant-closed-check`
  - Judul `Bukti barang diterima belum tersedia.`
  - Subtext disesuaikan:
    - Jika status belum `selesai`: `Bukti akan tersedia setelah pelanggan menandai pesanan selesai.`
    - Jika status `selesai` tetapi gambar kosong: `Pesanan selesai, tetapi foto bukti belum tersimpan.`

## Rencana Struktur Blade

Konsep markup:

```blade
<div class="mt-4 overflow-hidden border border-[#f2c8d0] bg-white shadow-sm">
    <div class="border-b border-[#f2c8d0] bg-[#fff7f8] p-5">
        <h3 class="text-lg font-black tracking-wide text-texthighlight">Bukti Pesanan</h3>
        <p class="mt-1 text-sm text-gray-600">Bukti pembayaran dan bukti barang diterima pelanggan.</p>
    </div>

    <div class="grid grid-cols-1 gap-5 p-5 xl:grid-cols-2">
        <div>Panel bukti pembayaran</div>
        <div>Panel bukti barang diterima</div>
    </div>
</div>
```

Preview gambar:

```blade
<img
    src="{{ asset('storage/' . $order->payment->proof_image) }}"
    alt="Bukti pembayaran"
    class="mt-4 aspect-video w-full border border-gray-200 object-cover"
>
```

Tombol:

```blade
<a href="{{ asset('storage/' . $path) }}" target="_blank"
   class="inline-flex h-10 items-center justify-center gap-2 bg-primary px-4 text-xs font-bold text-white hover:bg-red-700">
    <iconify-icon icon="mdi:open-in-new"></iconify-icon>
    Lihat Bukti
</a>
```

## Rencana Test

Tambahkan test baru:

- `tests/Feature/Admin/OrderDetailEvidenceTest.php`

Skenario:

1. Admin detail pesanan menampilkan bukti pembayaran jika `payment.proof_image` ada.
2. Admin detail pesanan menampilkan bukti barang diterima jika `orders.received_image` ada.
3. Admin detail pesanan menampilkan empty state jika bukti pembayaran belum ada.
4. Admin detail pesanan menampilkan empty state jika bukti barang diterima belum ada.

Data test:

- Buat admin role.
- Buat customer.
- Buat payment method.
- Buat order.
- Buat payment dengan `proof_image = payment-proofs/test-proof.jpg`.
- Set `received_image = received-product-photos/test-received.jpg`.

Assert:

```php
$response->assertSee('Bukti Pembayaran');
$response->assertSee('storage/payment-proofs/test-proof.jpg');
$response->assertSee('Bukti Barang Diterima');
$response->assertSee('storage/received-product-photos/test-received.jpg');
```

Command:

```bash
php artisan test --filter=OrderDetailEvidenceTest
php artisan test --filter=Admin
```

## Rencana Validasi Manual

1. Login sebagai admin.
2. Buka `/admin/orders`.
3. Klik detail pesanan yang sudah punya bukti pembayaran.
4. Pastikan panel `Bukti Pembayaran` tampil.
5. Klik tombol `Lihat Bukti`, pastikan file terbuka.
6. Buka pesanan yang sudah selesai dengan `received_image`.
7. Pastikan panel `Bukti Barang Diterima` tampil.
8. Buka pesanan tanpa bukti pembayaran/penerimaan.
9. Pastikan empty state tampil jelas.
10. Pastikan tidak ada error console dan layout tetap rapi.

## Urutan Implementasi

1. Update `resources/views/admin/orders/show.blade.php`.
2. Tambahkan section `Bukti Pesanan`.
3. Tambahkan panel bukti pembayaran dengan preview dan metadata.
4. Tambahkan panel bukti barang diterima dengan preview dan metadata.
5. Tambahkan empty state untuk masing-masing bukti.
6. Rapikan/hapus section lama `BUKTI PENERIMAAN BARANG` agar tidak duplikat.
7. Tambahkan feature test bukti detail pesanan.
8. Jalankan test.
9. Jalankan build.
10. Validasi browser.

## Catatan Risiko

1. `received_image` tidak punya timestamp khusus; memakai `updated_at` bisa kurang presisi jika order di-update setelah upload. Untuk scope ini cukup sebagai informasi sementara.
2. Jika file storage hilang tetapi path ada di database, browser akan menampilkan broken image. Scope ini hanya menampilkan path yang ada.
3. Jangan mengubah flow verify/reject payment dari halaman list pembayaran.
4. Jangan mengubah upload bukti pelanggan.
5. Pastikan tidak ada duplikasi bukti barang diterima: section lama perlu diganti atau dipindah ke panel baru.

## Rekomendasi Implementasi

Gunakan satu section `Bukti Pesanan` berisi dua panel berdampingan:

- Bukti Pembayaran
- Bukti Barang Diterima

Ini membuat admin tidak perlu berpindah halaman untuk memeriksa bukti penting, dan tetap menjaga detail pesanan rapi.
