# Planning Status Pesanan Selesai Otomatis Setelah 3 Hari Dikirim

## Ringkasan Temuan Codebase

Project menggunakan Laravel. Status pesanan saat ini disimpan di tabel `orders` lewat kolom `status`.

File utama yang relevan:

- `app/Http/Controllers/Admin/OrderController.php`
  - Method `update()` dipakai admin untuk mengubah status order.
  - Status yang bisa diubah admin:
    - `pembayaran_dikonfirmasi` ke `diproses` atau `dikirim`.
    - `diproses` ke `dikirim`.
  - Saat status menjadi `dikirim`, kode hanya menjalankan `$order->update(['status' => $validated['status']])`.
  - Belum ada pencatatan tanggal/waktu order mulai dikirim.

- `app/Http/Controllers/Pelanggan/OrderController.php`
  - Method `complete()` dipakai pelanggan untuk menekan tombol `Selesai`.
  - Syarat saat ini:
    - `orders.status` harus `dikirim`.
    - Pelanggan wajib upload `received_image`.
  - Setelah valid, order diubah menjadi `selesai` dan `received_image` disimpan.

- `resources/views/pelanggan/orders/show.blade.php`
  - Tombol `Selesai` hanya tampil saat `orders.status === 'dikirim'`.
  - Tombol ini masih menjadi satu-satunya cara order selesai dari sisi pelanggan.

- `app/Models/Order.php`
  - Status order yang dikenali:
    - `menunggu_konfirmasi_ongkir`
    - `belum_dibayar`
    - `menunggu_verifikasi_pembayaran`
    - `pembayaran_dikonfirmasi`
    - `diproses`
    - `dikirim`
    - `selesai`
    - `dibatalkan`
  - Sudah ada relasi `delivery()` dan `statusHistory()`.
  - Belum ada kolom/cast untuk waktu dikirim atau waktu selesai.

- `app/Models/Delivery.php`
  - Tabel `deliveries` punya kolom `shipped_at` dan `delivered_at`.
  - Namun berdasarkan pencarian kode, belum ada flow yang membuat atau mengupdate record `deliveries` saat admin mengubah order ke `dikirim`.
  - Karena itu, `deliveries.shipped_at` belum bisa dijadikan acuan utama untuk aturan otomatis 3 hari.

- `routes/console.php`
  - Saat ini hanya berisi command `inspire`.
  - Belum ada scheduled task untuk update status order otomatis.

## Struktur Database Relevan

### `orders`

Kolom penting saat ini:

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
- `received_image`
- `stock_restored_at`
- `stock_restored_by`
- `created_at`
- `updated_at`

Catatan:

- Tidak ada kolom `shipped_at`.
- Tidak ada kolom `completed_at`.
- `updated_at` tidak aman dipakai sebagai acuan 3 hari sejak dikirim, karena bisa berubah ketika data order diedit untuk alasan lain.
- `received_image` saat ini menjadi bukti manual dari pelanggan, tetapi untuk selesai otomatis kolom ini akan tetap `null`.

### `deliveries`

Kolom penting:

- `order_id`
- `address_id`
- `courier`
- `tracking_number`
- `status`
- `estimated_arrival`
- `shipped_at`
- `delivered_at`
- `received_by`
- `shipping_notes`

Catatan:

- Secara struktur, `deliveries.shipped_at` cocok untuk logistik.
- Secara implementasi saat ini, tabel ini belum terhubung ke update status admin, sehingga tidak cukup andal untuk kebutuhan otomatisasi sekarang.

### `order_status_histories`

Model `OrderStatusHistory` tersedia dengan field:

- `order_id`
- `status`
- `notes`
- `changed_at`

Catatan:

- Model punya relasi `changedBy()`, tetapi `changed_by` tidak terlihat pada `$fillable`.
- Perlu cek migration tabel ini saat implementasi karena file migration tidak muncul dari daftar `rg --files` yang terbaca, sementara modelnya ada.
- Jika tabel tersedia, ini bisa dipakai untuk audit perubahan otomatis.

## Masalah Utama

Aturan bisnis yang diminta: order otomatis menjadi `selesai` setelah 3 hari sejak status `dikirim`, karena pelanggan bisa lupa menekan tombol `Selesai`.

Masalah teknis saat ini:

1. Tidak ada timestamp khusus kapan order mulai `dikirim`.
2. Status `selesai` manual mewajibkan `received_image`, sedangkan otomatis tidak bisa mewajibkan foto.
3. Belum ada console command/scheduler untuk menjalankan proses berkala.
4. Perlu membedakan order selesai manual oleh pelanggan dan selesai otomatis oleh sistem untuk audit dan laporan.

## Keputusan Desain yang Disarankan

Gunakan kolom baru di `orders` sebagai sumber utama:

- `shipped_at`: waktu order pertama kali berubah menjadi `dikirim`.
- `completed_at`: waktu order berubah menjadi `selesai`.
- `auto_completed_at`: waktu order diselesaikan otomatis oleh sistem, nullable.
- `completion_source`: string nullable, contoh nilai `customer` atau `system`.
- `completion_notes`: text nullable, untuk keterangan seperti `Otomatis selesai setelah 3 hari sejak dikirim.`

Alasan:

- `orders.status` adalah pusat status pesanan saat ini.
- Tidak bergantung pada `deliveries`, karena delivery belum dipakai konsisten.
- Bisa membedakan selesai manual dan selesai otomatis.
- Bisa dipakai untuk tampilan admin, laporan GM/Direktur, dan audit.

## Rencana Implementasi

### 1. Tambahkan migration kolom penyelesaian order

Buat migration baru, misalnya:

`database/migrations/2026_06_10_130000_add_shipping_completion_audit_to_orders_table.php`

Kolom:

```php
Schema::table('orders', function (Blueprint $table) {
    $table->timestamp('shipped_at')->nullable()->after('received_image');
    $table->timestamp('completed_at')->nullable()->after('shipped_at');
    $table->timestamp('auto_completed_at')->nullable()->after('completed_at');
    $table->string('completion_source', 30)->nullable()->after('auto_completed_at');
    $table->text('completion_notes')->nullable()->after('completion_source');
    $table->index(['status', 'shipped_at']);
});
```

Down migration:

```php
Schema::table('orders', function (Blueprint $table) {
    $table->dropIndex(['status', 'shipped_at']);
    $table->dropColumn([
        'shipped_at',
        'completed_at',
        'auto_completed_at',
        'completion_source',
        'completion_notes',
    ]);
});
```

Catatan:

- Jika MySQL memberi nama index otomatis yang berbeda, pakai nama index eksplisit agar rollback aman.
- Untuk order lama yang statusnya sudah `dikirim`, bisa dipertimbangkan backfill `shipped_at = updated_at` dalam migration atau command terpisah. Karena `updated_at` tidak selalu akurat, backfill sebaiknya opsional dan dicatat.

### 2. Update model `Order`

File: `app/Models/Order.php`

Tambahkan ke `$fillable`:

- `shipped_at`
- `completed_at`
- `auto_completed_at`
- `completion_source`
- `completion_notes`

Tambahkan ke `$casts`:

```php
'shipped_at' => 'datetime',
'completed_at' => 'datetime',
'auto_completed_at' => 'datetime',
```

Tambahkan helper opsional:

```php
public function isEligibleForAutoCompletion(): bool
{
    return $this->status === 'dikirim'
        && $this->shipped_at
        && $this->shipped_at->lte(now()->subDays(3));
}
```

### 3. Catat `shipped_at` saat admin mengubah status menjadi `dikirim`

File: `app/Http/Controllers/Admin/OrderController.php`

Pada method `update()`, ubah logic update agar saat status baru `dikirim`, sistem mengisi `shipped_at`.

Contoh rencana:

```php
$updates = ['status' => $validated['status']];

if ($validated['status'] === 'dikirim' && ! $order->shipped_at) {
    $updates['shipped_at'] = now();
}

$order->update($updates);
```

Catatan:

- Jangan overwrite `shipped_at` jika sudah ada, supaya tanggal pertama dikirim tetap menjadi acuan.
- Jika ke depan ada fitur reshipment, perlu aturan tambahan, tetapi untuk scope saat ini cukup menjaga timestamp pertama.

### 4. Catat `completed_at` dan sumber saat pelanggan menekan tombol selesai

File: `app/Http/Controllers/Pelanggan/OrderController.php`

Pada method `complete()`, tambahkan field:

```php
$order->update([
    'status' => 'selesai',
    'received_image' => $receivedImagePath,
    'completed_at' => now(),
    'completion_source' => 'customer',
    'completion_notes' => 'Diselesaikan manual oleh pelanggan.',
]);
```

Catatan:

- Flow manual tetap wajib upload foto.
- Auto-complete tidak mengisi `received_image`.

### 5. Buat console command auto-complete

Buat command baru:

`app/Console/Commands/AutoCompleteShippedOrders.php`

Nama command:

```bash
orders:auto-complete-shipped
```

Logic utama:

- Ambil order dengan:
  - `status = 'dikirim'`
  - `shipped_at <= now()->subDays(3)`
  - `completed_at IS NULL`
- Proses dalam chunk agar aman untuk data besar.
- Update menjadi:
  - `status = 'selesai'`
  - `completed_at = now()`
  - `auto_completed_at = now()`
  - `completion_source = 'system'`
  - `completion_notes = 'Otomatis selesai setelah 3 hari sejak dikirim.'`

Contoh query:

```php
Order::where('status', 'dikirim')
    ->whereNotNull('shipped_at')
    ->where('shipped_at', '<=', now()->subDays(3))
    ->whereNull('completed_at')
    ->chunkById(100, function ($orders) {
        foreach ($orders as $order) {
            $order->update([
                'status' => 'selesai',
                'completed_at' => now(),
                'auto_completed_at' => now(),
                'completion_source' => 'system',
                'completion_notes' => 'Otomatis selesai setelah 3 hari sejak dikirim.',
            ]);
        }
    });
```

Catatan idempotensi:

- Command aman dijalankan berulang karena hanya memproses order `dikirim` dan `completed_at` masih null.
- Order yang sudah `selesai` manual tidak akan diproses.
- Order yang `dibatalkan` tidak akan diproses.

### 6. Daftarkan scheduler

File: `routes/console.php`

Tambahkan schedule:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('orders:auto-complete-shipped')->hourly();
```

Alternatif:

- Jalankan harian jika bisnis tidak perlu presisi jam:

```php
Schedule::command('orders:auto-complete-shipped')->dailyAt('00:10');
```

Rekomendasi:

- Gunakan `hourly()` agar order yang sudah lewat 3 hari tidak perlu menunggu sampai tengah malam.

Catatan deployment:

- Scheduler Laravel tetap butuh task runner server:

```bash
php artisan schedule:run
```

- Di production Linux, biasanya via cron setiap menit.
- Di Windows/local, gunakan Windows Task Scheduler atau jalankan manual saat testing.

### 7. Tambahkan audit status history jika tabel tersedia

Jika tabel `order_status_histories` memang ada, command bisa menambahkan history:

```php
$order->statusHistory()->create([
    'status' => 'selesai',
    'notes' => 'Otomatis selesai setelah 3 hari sejak dikirim.',
    'changed_at' => now(),
]);
```

Jika tabel belum ada, opsi:

- Tambah migration untuk tabel history secara terpisah.
- Atau cukup pakai `completion_source`, `completion_notes`, dan timestamp di `orders`.

Rekomendasi untuk scope awal:

- Prioritaskan kolom audit di `orders`.
- Tambahkan status history hanya jika tabel/migration sudah valid dan dipakai di fitur lain.

### 8. Update tampilan admin dan pelanggan

File yang relevan:

- `resources/views/admin/orders/index.blade.php`
- `resources/views/admin/orders/show.blade.php`
- `resources/views/pelanggan/orders/show.blade.php`
- Opsional `resources/views/pelanggan/orders/history.blade.php`

Tambahan tampilan yang disarankan:

- Saat status `dikirim`, tampilkan:
  - `Dikirim pada: {shipped_at}`
  - `Otomatis selesai pada: {shipped_at + 3 hari}`
- Saat status `selesai`, tampilkan:
  - Jika `completion_source = customer`: `Diselesaikan oleh pelanggan`
  - Jika `completion_source = system`: `Selesai otomatis oleh sistem`
  - Tampilkan `completion_notes` jika ada.

Catatan UX:

- Tombol `Selesai` tetap ada selama status masih `dikirim`.
- Jika sistem sudah otomatis menyelesaikan, tombol tidak tampil karena status sudah `selesai`.
- Foto bukti produk diterima bisa tetap kosong untuk order selesai otomatis.

### 9. Update laporan jika diperlukan

Area laporan:

- `resources/views/gm/reports/index.blade.php`
- `resources/views/gm/reports/excel.blade.php`
- `resources/views/direktur/reports/strategic.blade.php`
- Dashboard GM/Direktur/Admin

Perubahan opsional:

- Tambahkan kolom atau info sumber penyelesaian:
  - Manual pelanggan
  - Otomatis sistem
- Ini berguna untuk membedakan order selesai karena pelanggan konfirmasi atau karena auto-complete.

## Test yang Disarankan

### Feature test command

Buat file:

`tests/Feature/Admin/AutoCompleteShippedOrdersTest.php`

Skenario:

1. Command menyelesaikan order yang sudah dikirim lebih dari atau sama dengan 3 hari.
   - Order awal: `status = dikirim`, `shipped_at = now()->subDays(3)`.
   - Setelah command:
     - `status = selesai`
     - `completed_at` terisi
     - `auto_completed_at` terisi
     - `completion_source = system`
     - `completion_notes` sesuai.

2. Command tidak menyelesaikan order yang baru dikirim kurang dari 3 hari.
   - Order awal: `status = dikirim`, `shipped_at = now()->subDays(2)`.
   - Status tetap `dikirim`.

3. Command tidak menyentuh order yang belum dikirim.
   - Status `diproses`, `pembayaran_dikonfirmasi`, `dibatalkan`, atau `selesai` tidak berubah.

4. Command idempotent.
   - Jalankan command dua kali.
   - Order hanya berubah sekali dan tidak merusak timestamp/sumber.

5. Order selesai manual oleh pelanggan tidak diproses otomatis.
   - Status `selesai`, `completion_source = customer`.
   - Command tidak mengubah `completion_source`.

### Feature test admin update status

Tambahkan pada test admin order:

- Saat admin mengubah order ke `dikirim`, `shipped_at` terisi.
- Jika status sudah `dikirim` dan update lain terjadi, `shipped_at` tidak dioverwrite.

### Feature test pelanggan complete

Tambahkan pada `OrderListTest`:

- Saat pelanggan menekan `Selesai`, `completed_at` terisi.
- `completion_source = customer`.
- `received_image` tetap tersimpan seperti saat ini.

## Urutan Eksekusi yang Disarankan

1. Tambah migration kolom `shipped_at`, `completed_at`, `auto_completed_at`, `completion_source`, `completion_notes`.
2. Update model `Order` untuk fillable dan casts.
3. Update `Admin\OrderController::update()` agar mengisi `shipped_at` saat status menjadi `dikirim`.
4. Update `Pelanggan\OrderController::complete()` agar mengisi `completed_at` dan `completion_source = customer`.
5. Buat command `orders:auto-complete-shipped`.
6. Daftarkan scheduler di `routes/console.php`.
7. Tambah tampilan keterangan waktu auto-complete di halaman admin/pelanggan.
8. Tambah test feature untuk command, admin update, dan pelanggan complete.
9. Jalankan test:

```bash
php artisan test --filter=AutoCompleteShippedOrdersTest
php artisan test --filter=OrderListTest
php artisan test --filter=OrderNavigationBadgeTest
php artisan test --filter=OrderFilterTest
```

10. Jalankan migration:

```bash
php artisan migrate
```

11. Test manual command:

```bash
php artisan orders:auto-complete-shipped
```

## Catatan Risiko

- Jangan memakai `orders.updated_at` sebagai acuan 3 hari karena bisa berubah karena edit lain.
- Jangan mengubah order `dibatalkan` menjadi `selesai` otomatis.
- Jangan mewajibkan `received_image` untuk auto-complete, karena sistem tidak punya foto dari pelanggan.
- Scheduler hanya berjalan jika server menjalankan `php artisan schedule:run` secara berkala.
- Untuk order lama yang sudah `dikirim` sebelum fitur ini dibuat, perlu keputusan apakah `shipped_at` akan di-backfill dari `updated_at` atau dibiarkan null dan tidak auto-complete.

## Keputusan Bisnis yang Perlu Dikonfirmasi Saat Implementasi

1. Apakah batas 3 hari dihitung tepat 72 jam dari `shipped_at`, atau berdasarkan tanggal kalender?
   - Rekomendasi teknis: 72 jam dari `shipped_at`.

2. Apakah order lama yang sudah `dikirim` sebelum fitur ini dibuat perlu ikut auto-complete?
   - Rekomendasi aman: tidak otomatis sampai `shipped_at` tersedia, kecuali admin setuju backfill.

3. Apakah pelanggan masih boleh mengajukan pengembalian setelah sistem otomatis menyelesaikan order?
   - Saat ini pengembalian hanya bisa saat status `dikirim`.
   - Jika perlu masa komplain setelah auto-complete, perlu status/flow tambahan di luar scope ini.
