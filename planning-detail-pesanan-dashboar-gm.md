# Planning Detail Pesanan Dashboard GM

## Ringkasan Permintaan

Pada dashboard GM, kartu `Pesanan` saat ini menampilkan format seperti `0 / 7` dan meta `2 pesanan sedang berjalan`. Permintaan perubahan:

- Tambahkan detail pesanan pada dashboard GM.
- Tambahkan pilihan periode.
- Tambahkan dropdown status untuk menampilkan pesanan sesuai status.
- Filter tersebut dipakai untuk menampilkan pesanan pada dashboard GM, terutama area detail pesanan.

Catatan nama file mengikuti permintaan: `planning-detail-pesanan-dashboar-gm.md`.

## Temuan Codebase

### Controller Dashboard GM

File: `app/Http/Controllers/GM/DashboardController.php`

Data pesanan saat ini dihitung global tanpa filter periode/status:

```php
$totalOrders = Order::count();
$selesaiOrders = Order::where('status', 'selesai')->count();
$diprosesOrders = Order::whereIn('status', ['pembayaran_dikonfirmasi', 'diproses', 'dikirim'])->count();
```

Kartu pesanan memakai tiga angka tersebut:

- `totalOrders`: total semua pesanan.
- `selesaiOrders`: total pesanan status `selesai`.
- `diprosesOrders`: total pesanan berjalan, yaitu status:
  - `pembayaran_dikonfirmasi`
  - `diproses`
  - `dikirim`

Controller juga sudah punya filter tahun dan bulan untuk pendapatan:

- `year`
- `month`

Namun filter itu belum dipakai untuk kartu `Pesanan` dan daftar `Pesanan Terbaru`.

### View Dashboard GM

File: `resources/views/gm/dashboard.blade.php`

Kartu pesanan berada di bagian summary cards:

```blade
<p class="text-xs ...">Pesanan</p>
<p class="mt-3 text-3xl ...">{{ $selesaiOrders }} / {{ $totalOrders }}</p>
<p class="mt-2 text-sm ...">{{ $diprosesOrders }} pesanan sedang berjalan</p>
```

Dashboard juga sudah menampilkan:

- Status Pesanan: distribusi status order secara global.
- Pesanan Terbaru: tabel order terbaru tanpa filter status/periode khusus dashboard.
- Top Pelanggan dan Produk Teratas.

### Report GM Sebagai Referensi

File: `app/Http/Controllers/GM/ReportController.php`

Halaman laporan GM sudah punya pola filter:

- `start_date`
- `end_date`
- `status`
- `payment_status`

Query laporan:

```php
Order::with(['user', 'payment.paymentMethod', 'items'])
    ->when($filters['start_date'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
    ->when($filters['end_date'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
    ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
    ->when($filters['payment_status'], function ($query, $status) {
        $query->whereHas('payment', fn ($query) => $query->where('status', $status));
    });
```

Pola ini bisa dipakai ulang sebagai acuan filter dashboard GM.

### Model dan Database Order

File: `app/Models/Order.php`

Status order yang valid:

- `menunggu_konfirmasi_ongkir`
- `belum_dibayar`
- `menunggu_verifikasi_pembayaran`
- `pembayaran_dikonfirmasi`
- `diproses`
- `dikirim`
- `selesai`
- `dibatalkan`

Relasi penting:

- `user()`
- `items()`
- `payment()`
- `paymentMethod()`
- `delivery()`

Kolom database `orders` yang relevan:

- `id`
- `user_id`
- `order_number`
- `status`
- `subtotal`
- `discount_amount`
- `shipping_cost`
- `total_amount`
- `payment_method_id`
- `shipping_name`
- `shipping_phone`
- `created_at`
- `updated_at`

Untuk kebutuhan filter periode dashboard GM, kolom paling siap dipakai adalah `orders.created_at`.

## Masalah Saat Ini

1. Kartu `Pesanan` GM masih global, tidak mengikuti periode.
2. Tidak ada dropdown status pada dashboard GM untuk memfilter detail pesanan.
3. `Pesanan Terbaru` hanya latest global, belum menjadi detail pesanan yang bisa difilter.
4. Query count status juga global, belum sinkron dengan periode dashboard.
5. View `Status Pesanan` memakai `Payment::make(['status' => $status])->status_label`, padahal `$status` berasal dari `orders.status`. Ini berpotensi label salah untuk status order, walau beberapa default tetap terbaca karena fallback `ucwords`.

## Tujuan Perubahan

1. Kartu `Pesanan` tetap menampilkan format `selesai / total`, tetapi berdasarkan periode yang dipilih.
2. Meta `pesanan sedang berjalan` juga mengikuti periode.
3. Dashboard GM punya filter periode untuk detail pesanan.
4. Dashboard GM punya dropdown status order untuk menampilkan pesanan sesuai status.
5. Tabel detail pesanan di dashboard GM mengikuti filter periode dan status.
6. Query tetap efisien dan pagination tetap membawa query string filter.

## Rekomendasi UX

Tambahkan satu section khusus di dashboard GM, misalnya:

Judul: `Detail Pesanan`

Kontrol filter:

- `Periode`
  - `Hari ini`
  - `7 hari terakhir`
  - `30 hari terakhir`
  - `Bulan ini`
  - `Tahun ini`
  - `Custom`
- Jika `Custom`, tampilkan:
  - `Tanggal mulai`
  - `Tanggal selesai`
- `Status Pesanan`
  - `Semua Status`
  - `Menunggu Konfirmasi Ongkir`
  - `Belum Dibayar`
  - `Menunggu Verifikasi Pembayaran`
  - `Pembayaran Dikonfirmasi`
  - `Diproses`
  - `Dikirim`
  - `Selesai`
  - `Dibatalkan`

Tombol:

- `Filter`
- `Reset`

Tabel detail pesanan:

- `No.`
- `Nomor Pesanan`
- `Pelanggan`
- `Item`
- `Total`
- `Pembayaran`
- `Status Pesanan`
- `Tanggal`

Catatan:

- Tabel `Pesanan Terbaru` yang sudah ada bisa diubah menjadi `Detail Pesanan` agar tidak ada dua tabel order yang mirip.
- Alternatif lain: pertahankan `Pesanan Terbaru` dan tambahkan tabel baru `Detail Pesanan`, tetapi ini membuat dashboard lebih panjang.
- Rekomendasi: ganti heading `Pesanan Terbaru` menjadi `Detail Pesanan` dan tambahkan filter di header tabel tersebut.

## Rencana Implementasi

### 1. Tambahkan validasi/filter dashboard GM

File: `app/Http/Controllers/GM/DashboardController.php`

Tambahkan `Request $request` pada method `index()`:

```php
public function index(Request $request)
```

Tambahkan import:

```php
use Illuminate\Http\Request;
```

Validasi filter dashboard:

```php
$orderStatuses = [
    'menunggu_konfirmasi_ongkir',
    'belum_dibayar',
    'menunggu_verifikasi_pembayaran',
    'pembayaran_dikonfirmasi',
    'diproses',
    'dikirim',
    'selesai',
    'dibatalkan',
];

$filters = $request->validate([
    'order_period' => ['nullable', 'string', Rule::in(['today', 'last_7_days', 'last_30_days', 'this_month', 'this_year', 'custom'])],
    'order_start_date' => ['nullable', 'required_if:order_period,custom', 'date'],
    'order_end_date' => ['nullable', 'required_if:order_period,custom', 'date', 'after_or_equal:order_start_date'],
    'order_status' => ['nullable', 'string', Rule::in($orderStatuses)],
]);
```

Tambahkan import:

```php
use Illuminate\Validation\Rule;
```

Default filter:

- `order_period = this_month`
- `order_status = null`

Alasan:

- Dashboard biasanya perlu default yang tidak terlalu luas.
- `this_month` sesuai konteks dashboard yang sudah punya pendapatan bulanan.

### 2. Buat resolver range tanggal

Tambahkan helper private di controller:

```php
private function orderPeriodRange(array $filters): array
{
    return match ($filters['order_period']) {
        'today' => [now()->startOfDay(), now()->endOfDay()],
        'last_7_days' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
        'last_30_days' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
        'this_year' => [now()->startOfYear(), now()->endOfYear()],
        'custom' => [
            Carbon::parse($filters['order_start_date'])->startOfDay(),
            Carbon::parse($filters['order_end_date'])->endOfDay(),
        ],
        default => [now()->startOfMonth(), now()->endOfMonth()],
    };
}
```

Tambahkan import:

```php
use Illuminate\Support\Carbon;
```

Catatan:

- `last_7_days` dihitung termasuk hari ini.
- `last_30_days` dihitung termasuk hari ini.

### 3. Buat query dasar detail pesanan

Di `DashboardController@index`, setelah filter disiapkan:

```php
[$orderStartDate, $orderEndDate] = $this->orderPeriodRange($orderFilters);

$ordersFilteredQuery = Order::query()
    ->whereBetween('created_at', [$orderStartDate, $orderEndDate])
    ->when($orderFilters['order_status'], fn ($query, $status) => $query->where('status', $status));
```

Gunakan clone query untuk summary:

```php
$filteredTotalOrders = (clone $ordersFilteredQuery)->count();
$filteredSelesaiOrders = (clone $ordersFilteredQuery)->where('status', 'selesai')->count();
$filteredRunningOrders = (clone $ordersFilteredQuery)
    ->whereIn('status', ['pembayaran_dikonfirmasi', 'diproses', 'dikirim'])
    ->count();
```

Catatan penting:

- Jika `order_status` dipilih `selesai`, maka `filteredRunningOrders` akan 0 karena query sudah dibatasi status selesai.
- Untuk meta `pesanan sedang berjalan`, ada dua pilihan:
  - Tetap mengikuti filter status aktif. Artinya kalau status `selesai`, berjalan = 0.
  - Mengikuti periode saja, tidak mengikuti filter status. Ini lebih stabil untuk kartu ringkasan.
- Rekomendasi: kartu ringkasan mengikuti periode saja, sedangkan tabel mengikuti periode + status. Dengan begitu `2 pesanan sedang berjalan` tetap bermakna walau dropdown tabel sedang menampilkan `Selesai`.

Implementasi rekomendasi:

```php
$ordersInPeriodQuery = Order::query()
    ->whereBetween('created_at', [$orderStartDate, $orderEndDate]);

$filteredTotalOrders = (clone $ordersInPeriodQuery)->count();
$filteredSelesaiOrders = (clone $ordersInPeriodQuery)->where('status', 'selesai')->count();
$filteredRunningOrders = (clone $ordersInPeriodQuery)
    ->whereIn('status', ['pembayaran_dikonfirmasi', 'diproses', 'dikirim'])
    ->count();

$detailOrdersQuery = (clone $ordersInPeriodQuery)
    ->when($orderFilters['order_status'], fn ($query, $status) => $query->where('status', $status));
```

### 4. Update data yang dikirim ke view

Tambahkan variabel baru:

- `$orderStatuses`
- `$orderPeriodOptions`
- `$orderFilters`
- `$orderStartDate`
- `$orderEndDate`
- `$filteredTotalOrders`
- `$filteredSelesaiOrders`
- `$filteredRunningOrders`
- `$detailOrders`
- `$filteredOrderStatusCounts`

Contoh:

```php
$detailOrders = $detailOrdersQuery
    ->with(['user', 'payment', 'paymentMethod'])
    ->withCount('items')
    ->latest()
    ->paginate(5, ['*'], 'detail_orders_page')
    ->withQueryString();

$filteredOrderStatusCounts = (clone $ordersInPeriodQuery)
    ->select('status', DB::raw('count(*) as total'))
    ->groupBy('status')
    ->pluck('total', 'status');
```

Catatan:

- Bisa mengganti `$recentOrders` dengan `$detailOrders`.
- Jika tetap memakai `$recentOrders`, pastikan tidak membingungkan karena ada dua daftar order.

### 5. Update kartu Pesanan

File: `resources/views/gm/dashboard.blade.php`

Ubah value kartu pesanan:

Dari:

```blade
{{ $selesaiOrders }} / {{ $totalOrders }}
{{ $diprosesOrders }} pesanan sedang berjalan
```

Menjadi:

```blade
{{ $filteredSelesaiOrders }} / {{ $filteredTotalOrders }}
{{ $filteredRunningOrders }} pesanan sedang berjalan
```

Tambahkan keterangan kecil periode:

```blade
<p class="mt-1 text-xs text-gray-400">
    Periode: {{ $orderStartDate->format('d M Y') }} - {{ $orderEndDate->format('d M Y') }}
</p>
```

### 6. Tambahkan form filter pada section detail pesanan

Pada header section yang sekarang `Pesanan Terbaru`, ganti ke `Detail Pesanan`.

Tambahkan form:

```blade
<form method="GET" action="{{ route('gm.dashboard') }}" class="...">
    <input type="hidden" name="year" value="{{ $selectedYear }}">
    <input type="hidden" name="month" value="{{ $selectedMonth }}">

    <select name="order_period">...</select>
    <input type="date" name="order_start_date" ...>
    <input type="date" name="order_end_date" ...>
    <select name="order_status">...</select>

    <button type="submit">Filter</button>
    <a href="{{ route('gm.dashboard', ['year' => $selectedYear, 'month' => $selectedMonth]) }}">Reset</a>
</form>
```

Catatan:

- Preserve `year` dan `month` agar filter pendapatan/top products tidak hilang.
- Untuk custom date, input tanggal selalu bisa ditampilkan; atau bisa ditampilkan hanya ketika dropdown `Custom` dipilih dengan Alpine.js.
- Karena aplikasi sudah memakai Alpine di layout, opsi dinamis bisa pakai `x-data`.

### 7. Update tabel detail pesanan

Ganti sumber data dari `$recentOrders` ke `$detailOrders`.

Ubah teks:

- Header: `Detail Pesanan`
- Deskripsi: `Menampilkan pesanan berdasarkan periode dan status yang dipilih.`
- Empty state: `Tidak ada pesanan pada filter ini.`

Pagination:

```blade
{{ $detailOrders->links() }}
```

Pastikan `withQueryString()` dipakai di controller agar:

- `order_period`
- `order_start_date`
- `order_end_date`
- `order_status`
- `year`
- `month`

tetap terbawa saat pindah halaman.

### 8. Update distribusi status pesanan

Saat ini `$orderStatusCounts` global.

Opsional:

- Tetap global dan label tetap `Distribusi status order saat ini`.
- Atau jadikan berdasarkan periode, nama variabel `$filteredOrderStatusCounts`.

Rekomendasi:

- Jadikan berdasarkan periode agar konsisten dengan kartu pesanan.
- Tampilkan caption `Distribusi status order pada periode terpilih.`

Perbaiki label status:

Dari:

```blade
{{ \App\Models\Payment::make(['status' => $status])->status_label }}
```

Menjadi:

```blade
{{ \App\Models\Order::make(['status' => $status])->status_label }}
```

Alasan:

- `$status` berasal dari `orders.status`, bukan `payments.status`.

### 9. Update test

File yang bisa ditambah/diubah:

- `tests/Feature/GM/GmListUiTest.php`
- `tests/Feature/PaginationTablesTest.php`

Test baru yang disarankan:

1. `test_gm_dashboard_order_card_uses_selected_period`
   - Buat order selesai bulan ini.
   - Buat order selesai bulan lalu.
   - Request dashboard dengan `order_period=this_month`.
   - Assert kartu menampilkan count bulan ini saja.

2. `test_gm_dashboard_can_filter_detail_orders_by_status`
   - Buat order `selesai`.
   - Buat order `dikirim`.
   - Request dashboard `order_status=dikirim`.
   - Assert order dikirim tampil.
   - Assert order selesai tidak tampil.

3. `test_gm_dashboard_can_filter_detail_orders_by_custom_period`
   - Buat order di tanggal tertentu.
   - Request `order_period=custom`, `order_start_date`, `order_end_date`.
   - Assert hanya order dalam range tampil.

4. `test_gm_dashboard_detail_order_pagination_keeps_filters`
   - Buat lebih dari 5 order.
   - Request dengan `order_period` dan `order_status`.
   - Assert link pagination mengandung query filter.

5. Update test existing `test_gm_dashboard_retains_year_month_on_pagination`
   - Pastikan query baru tidak menghapus `year` dan `month`.

### 10. Tidak Perlu Migration

Untuk kebutuhan ini tidak perlu perubahan database.

Alasannya:

- Periode bisa memakai `orders.created_at`.
- Status sudah ada pada `orders.status`.
- Relasi user/payment/items sudah tersedia.

## Detail Data dan Definisi Angka

### Format `0 / 6`

Disarankan tetap:

- Angka kiri: jumlah pesanan `selesai` pada periode terpilih.
- Angka kanan: total pesanan pada periode terpilih.

Contoh:

`0 / 6` berarti dari 6 pesanan pada periode terpilih, 0 sudah selesai.

### Pesanan Sedang Berjalan

Status yang dihitung sebagai berjalan:

- `pembayaran_dikonfirmasi`
- `diproses`
- `dikirim`

Tidak dihitung sebagai berjalan:

- `menunggu_konfirmasi_ongkir`
- `belum_dibayar`
- `menunggu_verifikasi_pembayaran`
- `selesai`
- `dibatalkan`

Catatan:

- Jika bisnis menganggap `menunggu_verifikasi_pembayaran` juga pesanan berjalan, status ini bisa ditambahkan.
- Rekomendasi awal: tetap memakai definisi existing agar tidak mengubah arti angka secara diam-diam.

## Risiko dan Catatan Implementasi

- Jangan memakai `Payment::status_label` untuk status order.
- Hati-hati dengan nama query string agar tidak bentrok dengan filter pendapatan yang sudah memakai `year` dan `month`.
- Pakai prefix `order_` untuk filter pesanan:
  - `order_period`
  - `order_start_date`
  - `order_end_date`
  - `order_status`
- Pastikan semua pagination memakai `withQueryString()`.
- Jika memakai `custom`, validasi `order_end_date` harus `after_or_equal:order_start_date`.
- Jika request filter invalid, Laravel akan redirect dengan error validation; bisa ditampilkan jika perlu.

## Urutan Eksekusi yang Disarankan

1. Update `GM\DashboardController@index` agar menerima `Request`.
2. Tambahkan daftar status order dan pilihan periode.
3. Tambahkan helper private untuk validasi filter dan resolve tanggal periode.
4. Hitung ulang summary pesanan berdasarkan periode.
5. Buat query detail pesanan berdasarkan periode dan status.
6. Ganti `recentOrders` menjadi `detailOrders` atau tetap support keduanya jika ingin kompatibilitas sementara.
7. Update kartu `Pesanan` di `gm/dashboard.blade.php`.
8. Tambahkan form filter periode/status pada section detail pesanan.
9. Update tabel agar memakai `$detailOrders`.
10. Update status distribution agar memakai `Order::status_label`.
11. Tambahkan/ubah test feature GM.
12. Jalankan test:

```bash
php artisan test --filter=GmListUiTest
php artisan test --filter=PaginationTablesTest
```

## Output yang Diharapkan

Dashboard GM akan memiliki:

- Kartu `Pesanan` seperti:
  - `0 / 6`
  - `2 pesanan sedang berjalan`
  - Periode yang sedang dipakai.
- Form filter detail pesanan:
  - Pilihan periode.
  - Tanggal mulai/selesai untuk custom.
  - Dropdown status pesanan.
- Tabel detail pesanan yang berubah sesuai filter.
- Pagination yang tetap membawa filter.
