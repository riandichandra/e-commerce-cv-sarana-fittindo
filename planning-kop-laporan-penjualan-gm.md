# Planning Kop Laporan Penjualan GM

## Ringkasan Permintaan

Tambahkan kop laporan seperti contoh gambar ke laporan Excel GM.

Format kop yang diminta:

```text
Laporan Kuantitas Penjualan
Cabang CV. SARANA FITTINDO
Periode: 2 Maret 2026 - 2 Maret 2026
Konsumen : Semua
Karyawan : Semua
Nama Barang : Semua
```

Kop tersebut perlu disesuaikan di file laporan Excel yang diunduh GM.

## Temuan Codebase

### Route Laporan GM

File: `routes/web.php`

Route GM untuk laporan:

- `GET /gm/reports`
  - `GM\ReportController@index`
  - Nama route: `gm.reports.index`
- `GET /gm/reports/download`
  - `GM\ReportController@download`
  - Nama route: `gm.reports.download`

### Controller Laporan GM

File: `app/Http/Controllers/GM/ReportController.php`

Method `download()`:

```php
public function download(Request $request): StreamedResponse
{
    $filters = $this->filters($request);
    $orders = $this->reportQuery($filters)
        ->latest()
        ->get();

    $filename = 'laporan-gm-'.now()->format('Ymd-His').'.xls';

    return response()->streamDownload(function () use ($orders, $filters) {
        echo view('gm.reports.excel', [
            'orders' => $orders,
            'filters' => $filters,
            'generatedAt' => now(),
        ])->render();
    }, $filename, [
        'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
    ]);
}
```

Catatan:

- Laporan Excel dibuat dari Blade HTML, bukan library XLSX.
- File berekstensi `.xls` dan dibuka Excel sebagai HTML table.
- Data yang dikirim ke view saat ini:
  - `$orders`
  - `$filters`
  - `$generatedAt`

### View Excel GM

File: `resources/views/gm/reports/excel.blade.php`

Kop saat ini:

```blade
<table>
    <tr>
        <th colspan="16">Laporan GM CV Sarana Fittindo</th>
    </tr>
    <tr>
        <td colspan="16">Generated: {{ $generatedAt->format('d M Y H:i') }}</td>
    </tr>
    <tr>
        <td colspan="16">
            Periode:
            {{ $filters['start_date'] ?: '-' }}
            sampai
            {{ $filters['end_date'] ?: '-' }}
        </td>
    </tr>
</table>
```

Tabel data saat ini memakai 16 kolom:

1. No
2. Nomor Pesanan
3. Pesanan Date
4. Pelanggan
5. Telepon
6. Kota Pengiriman
7. Produk
8. Qty
9. Subtotal
10. Discount
11. Biaya Pengiriman
12. Total
13. Pembayaran Metode
14. Status Pembayaran
15. Status Pesanan
16. Diverifikasi Pada

### Filter Laporan Saat Ini

File: `app/Http/Controllers/GM/ReportController.php`

Method `filters()` hanya menerima:

- `start_date`
- `end_date`
- `status`
- `payment_status`

Belum ada filter:

- Konsumen
- Karyawan
- Nama barang
- Cabang

### Query Laporan Saat Ini

```php
return Order::with(['user', 'payment.paymentMethod', 'items'])
    ->when($filters['start_date'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
    ->when($filters['end_date'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
    ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
    ->when($filters['payment_status'], function ($query, $status) {
        $query->whereHas('payment', fn ($query) => $query->where('status', $status));
    });
```

Konsumen bisa diambil dari:

- `orders.user.name` jika user masih ada.
- fallback `orders.shipping_name`.

Nama barang bisa diambil dari:

- `order_items.product_name`.

Karyawan belum punya field eksplisit pada order. Kandidat yang mendekati:

- `payments.verified_by` sebagai admin/verifikator pembayaran.
- `orders.shipping_cost_confirmed_by` sebagai admin konfirmasi ongkir.
- Namun belum ada “sales/karyawan” yang jelas di struktur order.

Cabang belum punya tabel/kolom khusus. Yang paling aman untuk kop sekarang:

- Hardcode `CV. SARANA FITTINDO`.

## Struktur Database Relevan

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
- `shipping_name`
- `shipping_phone`
- `shipping_city`
- `created_at`
- `payment_method_id`

Relasi:

- `user()`
- `items()`
- `payment()`
- `paymentMethod()`

### `order_items`

Kolom penting:

- `order_id`
- `product_id`
- `product_name`
- `quantity`
- `subtotal`

Dipakai untuk:

- Kolom Produk.
- Qty total.
- Nama Barang di kop jika nanti ada filter nama barang.

### `payments`

Kolom penting:

- `order_id`
- `payment_method_id`
- `status`
- `verified_by`
- `verified_at`

Relasi:

- `paymentMethod()`
- `verifiedBy()`

Catatan:

- Untuk menampilkan/filter karyawan, `verified_by` bisa dipakai sebagai kandidat bila bisnis setuju “karyawan” berarti admin yang verifikasi pembayaran.

### `users`

Kolom penting:

- `id`
- `name`
- `email`
- `phone`

Dipakai untuk:

- Nama konsumen via `orders.user`.
- Nama karyawan jika memakai `payments.verifiedBy`.

## Masalah Saat Ini

1. Kop Excel belum sesuai format contoh.
2. Periode masih mentah dalam format tanggal input, bukan format Indonesia seperti `2 Maret 2026`.
3. Kop belum memiliki baris:
   - Cabang
   - Konsumen
   - Karyawan
   - Nama Barang
4. Filter laporan belum mendukung konsumen, karyawan, dan nama barang.
5. Struktur database belum punya konsep cabang atau sales/karyawan penanggung jawab order.

## Tujuan Perubahan

1. Kop Excel menampilkan judul `Laporan Kuantitas Penjualan`.
2. Kop menampilkan cabang `CV. SARANA FITTINDO`.
3. Kop menampilkan periode dalam format tanggal Indonesia.
4. Kop menampilkan:
   - `Konsumen : Semua` atau nama konsumen jika ada filter.
   - `Karyawan : Semua` atau nama karyawan jika ada filter.
   - `Nama Barang : Semua` atau nama barang jika ada filter.
5. Tampilan kop di Excel rapi dan center seperti contoh gambar.
6. Data laporan tetap tampil di bawah kop.

## Keputusan Desain yang Disarankan

### Tahap Awal

Untuk scope awal, tambahkan kop dengan nilai:

- `Cabang CV. SARANA FITTINDO`
- `Periode: {start_date} - {end_date}`
- `Konsumen : Semua`
- `Karyawan : Semua`
- `Nama Barang : Semua`

Jika filter `start_date` atau `end_date` kosong:

- Jika keduanya kosong: `Periode: Semua`
- Jika hanya start ada: `Periode: {start_date} - Semua`
- Jika hanya end ada: `Periode: Semua - {end_date}`

### Tahap Lanjutan

Jika ingin baris `Konsumen`, `Karyawan`, dan `Nama Barang` berubah sesuai filter, tambahkan filter baru pada halaman laporan.

Filter baru yang direkomendasikan:

- `customer_id`
- `employee_id`
- `product_name`

Catatan:

- `employee_id` perlu didefinisikan. Rekomendasi awal: admin/verifikator pembayaran dari `payments.verified_by`.
- `product_name` memakai string dari `order_items.product_name`, karena order item menyimpan snapshot nama barang.

## Rencana Implementasi

### 1. Tambahkan helper metadata kop di `ReportController`

File: `app/Http/Controllers/GM/ReportController.php`

Buat method private:

```php
private function reportHeader(array $filters): array
{
    return [
        'title' => 'Laporan Kuantitas Penjualan',
        'branch' => 'CV. SARANA FITTINDO',
        'period' => $this->periodLabel($filters['start_date'], $filters['end_date']),
        'customer' => 'Semua',
        'employee' => 'Semua',
        'product' => 'Semua',
    ];
}
```

### 2. Tambahkan helper format tanggal Indonesia

Masih di `ReportController`.

Tambahkan import:

```php
use Illuminate\Support\Carbon;
```

Helper:

```php
private function formatIndonesianDate(?string $date): ?string
{
    if (! $date) {
        return null;
    }

    return Carbon::parse($date)->translatedFormat('j F Y');
}
```

Pastikan locale aplikasi mendukung Indonesia. `config/app.php` kemungkinan sudah `locale => id` atau bisa gunakan map manual bulan jika perlu.

Alternatif aman tanpa bergantung locale:

```php
private function formatIndonesianDate(?string $date): ?string
{
    if (! $date) {
        return null;
    }

    $months = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    $date = Carbon::parse($date);

    return $date->day . ' ' . $months[$date->month] . ' ' . $date->year;
}
```

Rekomendasi:

- Pakai map manual agar output pasti seperti contoh `2 Maret 2026`.

### 3. Tambahkan helper label periode

```php
private function periodLabel(?string $startDate, ?string $endDate): string
{
    $start = $this->formatIndonesianDate($startDate) ?? 'Semua';
    $end = $this->formatIndonesianDate($endDate) ?? 'Semua';

    if ($start === 'Semua' && $end === 'Semua') {
        return 'Semua';
    }

    return $start . ' - ' . $end;
}
```

### 4. Kirim metadata kop ke view Excel

Update method `download()`:

```php
$header = $this->reportHeader($filters);
```

Lalu kirim ke view:

```php
echo view('gm.reports.excel', [
    'orders' => $orders,
    'filters' => $filters,
    'generatedAt' => now(),
    'header' => $header,
])->render();
```

### 5. Update `excel.blade.php`

File: `resources/views/gm/reports/excel.blade.php`

Ganti table kop lama dengan struktur yang lebih mirip gambar.

Contoh:

```blade
<table>
    <tr>
        <th colspan="16" style="font-size: 18px; font-weight: bold; text-align: center;">
            {{ $header['title'] }}
        </th>
    </tr>
    <tr>
        <th colspan="16" style="font-size: 12px; font-weight: bold; text-align: center;">
            Cabang {{ $header['branch'] }}
        </th>
    </tr>
    <tr>
        <th colspan="16" style="font-size: 12px; font-weight: bold; text-align: center;">
            Periode: {{ $header['period'] }}
        </th>
    </tr>
    <tr>
        <th colspan="16" style="font-size: 12px; font-weight: bold; text-align: center;">
            Konsumen : {{ $header['customer'] }}
        </th>
    </tr>
    <tr>
        <th colspan="16" style="font-size: 12px; font-weight: bold; text-align: center;">
            Karyawan : {{ $header['employee'] }}
        </th>
    </tr>
    <tr>
        <th colspan="16" style="font-size: 12px; font-weight: bold; text-align: center;">
            Nama Barang : {{ $header['product'] }}
        </th>
    </tr>
    <tr>
        <td colspan="16">&nbsp;</td>
    </tr>
</table>
```

Catatan:

- `colspan="16"` mengikuti jumlah kolom tabel data saat ini.
- Style inline lebih aman untuk HTML Excel.
- `Generated` bisa dipindahkan ke bawah tabel atau dihapus dari kop agar sesuai contoh. Jika tetap diperlukan, taruh kecil di bawah kop:

```blade
<tr>
    <td colspan="16" style="font-size: 10px; text-align: right;">
        Dicetak: {{ $generatedAt->format('d M Y H:i') }}
    </td>
</tr>
```

### 6. Sesuaikan nama laporan dan filename

Saat ini filename:

```php
laporan-gm-YYYYMMDD-HHMMSS.xls
```

Rekomendasi:

```php
$filename = 'laporan-kuantitas-penjualan-'.now()->format('Ymd-His').'.xls';
```

Alasan:

- Sesuai judul kop baru.

### 7. Opsional: Tambah filter Konsumen, Karyawan, Nama Barang

Jika kop harus mencerminkan filter aktual, tambahkan filter ke halaman laporan.

#### Tambah validasi filter

```php
'customer_id' => ['nullable', 'integer', 'exists:users,id'],
'employee_id' => ['nullable', 'integer', 'exists:users,id'],
'product_name' => ['nullable', 'string', 'max:200'],
```

#### Update query

```php
->when($filters['customer_id'], fn ($query, $id) => $query->where('user_id', $id))
->when($filters['employee_id'], function ($query, $id) {
    $query->whereHas('payment', fn ($payment) => $payment->where('verified_by', $id));
})
->when($filters['product_name'], function ($query, $productName) {
    $query->whereHas('items', fn ($item) => $item->where('product_name', $productName));
})
```

#### Isi header

```php
'customer' => $filters['customer_id'] ? User::find($filters['customer_id'])?->name : 'Semua',
'employee' => $filters['employee_id'] ? User::find($filters['employee_id'])?->name : 'Semua',
'product' => $filters['product_name'] ?: 'Semua',
```

Catatan:

- Perlu import `App\Models\User`.
- Untuk daftar product name, bisa ambil dari `OrderItem::query()->distinct()->pluck('product_name')`.
- Ini menambah scope UI di halaman laporan, jadi bisa dikerjakan setelah kop dasar.

## Rekomendasi Implementasi Bertahap

### Tahap 1: Kop Excel Saja

Fokus:

- Tambah metadata header.
- Format periode Indonesia.
- Update view Excel.
- Filename lebih sesuai.

Tidak mengubah filter halaman laporan.

Output:

- Kop selalu tampil.
- Konsumen/Karyawan/Nama Barang bernilai `Semua`.

### Tahap 2: Filter Tambahan

Fokus:

- Tambah filter konsumen, karyawan, nama barang di halaman laporan.
- Query report mengikuti filter tersebut.
- Kop menampilkan nilai filter aktual.

Rekomendasi:

- Kerjakan Tahap 1 dulu karena sesuai permintaan utama “tambahkan kop seperti gambar”.

## Test yang Disarankan

File baru atau update:

- `tests/Feature/GM/GmReportExcelTest.php`
- Atau tambahkan ke `tests/Feature/GM/GmListUiTest.php`

### 1. Excel download menampilkan kop baru

Skenario:

- Login sebagai GM.
- Request route:

```php
route('gm.reports.download', [
    'start_date' => '2026-03-02',
    'end_date' => '2026-03-02',
])
```

Assert response content mengandung:

- `Laporan Kuantitas Penjualan`
- `Cabang CV. SARANA FITTINDO`
- `Periode: 2 Maret 2026 - 2 Maret 2026`
- `Konsumen : Semua`
- `Karyawan : Semua`
- `Nama Barang : Semua`

### 2. Excel tetap menampilkan data order

Skenario:

- Buat order dalam periode.
- Download Excel.

Assert:

- Nomor pesanan tampil.
- Nama pelanggan tampil.
- Nama produk tampil.

### 3. Periode semua jika tanpa tanggal

Skenario:

- Download tanpa `start_date` dan `end_date`.

Assert:

- `Periode: Semua`

### 4. Content type tetap Excel

Assert header:

```php
$response->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');
```

Catatan:

- Header bisa mengandung tambahan charset/format tergantung Laravel. Jika assert strict bermasalah, cukup cek body dan status response.

## Risiko dan Catatan Teknis

- Karena Excel dibuat dari HTML, styling harus inline dan sederhana.
- `colspan` harus mengikuti jumlah kolom tabel data. Saat ini 16.
- Jika jumlah kolom berubah, kop juga harus disesuaikan.
- Format tanggal Indonesia lebih aman memakai map bulan manual daripada bergantung locale server.
- “Karyawan” belum jelas di database. Jangan mengisi dengan data sembarang tanpa konfirmasi bisnis.
- “Cabang” belum ada tabel/kolom, jadi untuk sekarang hardcode `CV. SARANA FITTINDO`.

## Urutan Eksekusi yang Disarankan

1. Tambahkan helper `formatIndonesianDate()`.
2. Tambahkan helper `periodLabel()`.
3. Tambahkan helper `reportHeader()`.
4. Update `download()` agar mengirim `$header` ke view Excel.
5. Ubah filename menjadi `laporan-kuantitas-penjualan-...xls`.
6. Update `resources/views/gm/reports/excel.blade.php` dengan kop baru.
7. Tambahkan test download Excel.
8. Jalankan:

```bash
php artisan test --filter=GmReportExcelTest
php artisan test --filter=GmListUiTest
```

## Output yang Diharapkan

Bagian atas file Excel akan menjadi:

```text
Laporan Kuantitas Penjualan
Cabang CV. SARANA FITTINDO
Periode: 2 Maret 2026 - 2 Maret 2026
Konsumen : Semua
Karyawan : Semua
Nama Barang : Semua
```

Lalu di bawahnya tetap ada tabel data order/penjualan seperti laporan GM saat ini.
