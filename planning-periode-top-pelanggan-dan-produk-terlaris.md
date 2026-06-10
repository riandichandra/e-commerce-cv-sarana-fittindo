# Planning Periode Top Pelanggan dan Produk Terlaris

## Ringkasan Permintaan

Tambahkan filter periode `dari ... sampai ...` untuk:

- `Top Pelanggan`
- `Produk Teratas` atau produk terlaris

Filter periode tersebut harus memengaruhi data yang ditampilkan pada kedua section di dashboard GM.

## Temuan Codebase

### Controller Dashboard GM

File: `app/Http/Controllers/GM/DashboardController.php`

Dashboard GM saat ini memakai filter `year` dan `month` untuk beberapa metrik pendapatan.

Query `Top Pelanggan` saat ini:

```php
$topCustomers = Order::query()
    ->when($selectedYear, fn($q) => $q->whereYear('created_at', $selectedYear))
    ->when($selectedMonth, fn($q) => $q->whereMonth('created_at', $selectedMonth))
    ->select('user_id')
    ->selectRaw('COUNT(*) as order_count')
    ->selectRaw('SUM(total_amount) as total_spent')
    ->selectRaw('AVG(total_amount) as avg_order_value')
    ->groupBy('user_id')
    ->orderByDesc('order_count')
    ->orderByDesc('total_spent')
    ->with('user')
    ->paginate(5, ['*'], 'top_customers_page')
    ->withQueryString();
```

Artinya:

- Top Pelanggan sudah memakai periode, tetapi hanya dalam bentuk tahun dan bulan.
- Sumber tanggal adalah `orders.created_at`.

Query `Produk Teratas` saat ini:

```php
$topProducts = OrderItem::query()
    ->whereHas('order', fn($q) => $q->whereYear('created_at', $selectedYear))
    ->select('product_name')
    ->selectRaw('SUM(quantity) as total_quantity')
    ->selectRaw('SUM(subtotal) as total_sales')
    ->groupBy('product_name')
    ->orderByDesc('total_quantity')
    ->paginate(5, ['*'], 'top_products_page')
    ->withQueryString();
```

Artinya:

- Produk Teratas hanya memakai filter tahun.
- Produk Teratas belum mengikuti bulan, apalagi range tanggal custom.
- Ini membuat Top Pelanggan dan Produk Teratas tidak konsisten.

### View Dashboard GM

File: `resources/views/gm/dashboard.blade.php`

Section `Top Pelanggan` saat ini:

```blade
<h2>Top Pelanggan</h2>
<p>Pelanggan dengan jumlah pesanan terbanyak pada {{ bulan }} {{ tahun }}.</p>
```

Belum ada input tanggal langsung di section ini.

Section `Produk Teratas` saat ini:

```blade
<h2>Produk Teratas</h2>
<p>Produk terlaris berdasarkan jumlah pesanan.</p>
```

Di header section ini hanya ada dropdown `Tahun`, memakai parameter `year` dan hidden `month`.

### Struktur Database Relevan

#### `orders`

Kolom relevan:

- `id`
- `user_id`
- `order_number`
- `status`
- `subtotal`
- `discount_amount`
- `shipping_cost`
- `total_amount`
- `created_at`

Relasi:

- `items()`
- `user()`

`orders.created_at` adalah acuan periode yang paling siap dipakai untuk Top Pelanggan dan Produk Teratas.

#### `order_items`

Kolom relevan:

- `order_id`
- `product_id`
- `product_name`
- `product_price`
- `quantity`
- `subtotal`

Relasi:

- `order()`
- `product()`

Untuk Produk Teratas, aggregation dilakukan dari `order_items`, lalu filter periode diterapkan melalui relasi `order`.

#### `users`

Kolom relevan:

- `id`
- `name`
- `email`
- `phone`

Top Pelanggan dikelompokkan berdasarkan `orders.user_id`.

## Masalah Saat Ini

1. `Top Pelanggan` hanya bisa difilter lewat `year` dan `month`.
2. `Produk Teratas` hanya memakai `year`, tidak memakai `month`.
3. Belum ada filter eksplisit `dari tanggal` dan `sampai tanggal`.
4. Filter tahun/bulan untuk pendapatan bercampur dengan kebutuhan periode Top Pelanggan/Produk Terlaris.
5. Pagination Top Pelanggan dan Produk Teratas harus tetap membawa query periode.

## Tujuan Perubahan

1. Tambahkan filter tanggal `Dari` dan `Sampai` untuk Top Pelanggan dan Produk Teratas.
2. Kedua section memakai periode yang sama.
3. Data Top Pelanggan dihitung dari order dalam periode tersebut.
4. Data Produk Teratas dihitung dari order item yang order-nya berada dalam periode tersebut.
5. Tampilan section menjelaskan periode yang sedang dipakai.
6. Pagination tetap mempertahankan filter periode.

## Keputusan Desain yang Disarankan

Gunakan parameter query khusus agar tidak bentrok dengan filter `year` dan `month` yang sudah dipakai pendapatan:

- `top_start_date`
- `top_end_date`

Default:

- `top_start_date`: awal bulan berjalan.
- `top_end_date`: hari ini.

Alasan:

- Nama `top_` menjelaskan filter ini khusus untuk Top Pelanggan dan Produk Teratas.
- Tidak merusak filter pendapatan existing.
- Bisa dipakai bersama pagination `top_customers_page` dan `top_products_page`.

## Rencana Implementasi

### 1. Update `DashboardController@index` agar menerima Request

File: `app/Http/Controllers/GM/DashboardController.php`

Ubah:

```php
public function index()
```

Menjadi:

```php
public function index(Request $request)
```

Tambahkan import:

```php
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
```

### 2. Validasi filter periode top

Tambahkan validasi:

```php
$topPeriodInput = $request->validate([
    'top_start_date' => ['nullable', 'date'],
    'top_end_date' => ['nullable', 'date', 'after_or_equal:top_start_date'],
]);
```

Catatan:

- Jika nanti controller juga memvalidasi filter dashboard lain, gabungkan dalam satu `$request->validate()`.
- Karena `year` dan `month` sudah dipakai existing tanpa validate eksplisit, pastikan validasi baru tidak menolak query existing seperti pagination.

### 3. Resolve tanggal default

Tambahkan logic:

```php
$topStartDate = filled($topPeriodInput['top_start_date'] ?? null)
    ? Carbon::parse($topPeriodInput['top_start_date'])->startOfDay()
    : now()->startOfMonth();

$topEndDate = filled($topPeriodInput['top_end_date'] ?? null)
    ? Carbon::parse($topPeriodInput['top_end_date'])->endOfDay()
    : now()->endOfDay();
```

Variabel yang dikirim ke view:

- `$topStartDate`
- `$topEndDate`
- `$topFilters`

Contoh:

```php
$topFilters = [
    'top_start_date' => $topStartDate->toDateString(),
    'top_end_date' => $topEndDate->toDateString(),
];
```

### 4. Update query Top Pelanggan

Ganti query existing:

```php
$topCustomers = Order::query()
    ->when($selectedYear, fn($q) => $q->whereYear('created_at', $selectedYear))
    ->when($selectedMonth, fn($q) => $q->whereMonth('created_at', $selectedMonth))
```

Menjadi:

```php
$topCustomers = Order::query()
    ->whereBetween('created_at', [$topStartDate, $topEndDate])
```

Query lengkap tetap:

```php
$topCustomers = Order::query()
    ->whereBetween('created_at', [$topStartDate, $topEndDate])
    ->select('user_id')
    ->selectRaw('COUNT(*) as order_count')
    ->selectRaw('SUM(total_amount) as total_spent')
    ->selectRaw('AVG(total_amount) as avg_order_value')
    ->groupBy('user_id')
    ->orderByDesc('order_count')
    ->orderByDesc('total_spent')
    ->with('user')
    ->paginate(5, ['*'], 'top_customers_page')
    ->withQueryString();
```

Catatan:

- Jika order dengan `user_id` null tidak mungkin terjadi karena FK required, aman.
- Jika ingin hanya transaksi berhasil, bisa tambahkan `whereHas('payment', status terverifikasi)`, tetapi existing query menghitung semua order. Rekomendasi awal: pertahankan behavior existing, hanya ubah periode.

### 5. Update query Produk Teratas

Ganti:

```php
->whereHas('order', fn($q) => $q->whereYear('created_at', $selectedYear))
```

Menjadi:

```php
->whereHas('order', fn($q) => $q->whereBetween('created_at', [$topStartDate, $topEndDate]))
```

Query lengkap:

```php
$topProducts = OrderItem::query()
    ->whereHas('order', fn ($q) => $q->whereBetween('created_at', [$topStartDate, $topEndDate]))
    ->select('product_name')
    ->selectRaw('SUM(quantity) as total_quantity')
    ->selectRaw('SUM(subtotal) as total_sales')
    ->groupBy('product_name')
    ->orderByDesc('total_quantity')
    ->paginate(5, ['*'], 'top_products_page')
    ->withQueryString();
```

Tambahan yang disarankan:

```php
->orderByDesc('total_sales')
```

Alasan:

- Jika quantity sama, produk dengan total penjualan lebih besar tampil lebih atas.

### 6. Update view Top Pelanggan

File: `resources/views/gm/dashboard.blade.php`

Di header section `Top Pelanggan`, ubah deskripsi:

```blade
Pelanggan dengan jumlah pesanan terbanyak dari {{ $topStartDate->format('d M Y') }} sampai {{ $topEndDate->format('d M Y') }}.
```

Tambahkan form filter:

```blade
<form method="GET" action="{{ route('gm.dashboard') }}" class="...">
    <input type="hidden" name="year" value="{{ $selectedYear }}">
    <input type="hidden" name="month" value="{{ $selectedMonth }}">

    <label>
        Dari
        <input type="date" name="top_start_date" value="{{ $topFilters['top_start_date'] }}">
    </label>

    <label>
        Sampai
        <input type="date" name="top_end_date" value="{{ $topFilters['top_end_date'] }}">
    </label>

    <button type="submit">Filter</button>
    <a href="{{ route('gm.dashboard', ['year' => $selectedYear, 'month' => $selectedMonth]) }}">Reset</a>
</form>
```

Catatan:

- Letakkan form di header section, mirip form tahun pada `Produk Teratas`.
- `year` dan `month` dipertahankan agar filter pendapatan tidak hilang.

### 7. Update view Produk Teratas

Di section `Produk Teratas`, hapus dropdown tahun khusus produk atau ganti dengan info periode yang sama.

Rekomendasi:

- Satu form periode diletakkan di atas `Top Pelanggan`.
- Produk Teratas memakai periode yang sama dan menampilkan caption:

```blade
Produk terlaris dari {{ $topStartDate->format('d M Y') }} sampai {{ $topEndDate->format('d M Y') }}.
```

Jika ingin form tersedia juga di Produk Teratas:

- Gunakan input `top_start_date` dan `top_end_date` yang sama.
- Jangan buat nama parameter berbeda agar tidak ada dua sumber kebenaran.

### 8. Pertahankan query string pada pagination

Controller sudah memakai:

```php
->withQueryString()
```

Pastikan link pagination tetap membawa:

- `year`
- `month`
- `top_start_date`
- `top_end_date`

Tidak perlu perubahan tambahan selama `withQueryString()` tetap ada.

### 9. Update empty state

Untuk Top Pelanggan:

```blade
Belum ada data pelanggan untuk periode dari ... sampai ...
```

Untuk Produk Teratas:

```blade
Belum ada produk terjual pada periode ini.
```

### 10. Tidak Perlu Migration

Perubahan ini tidak membutuhkan migration karena:

- Filter memakai `orders.created_at`.
- Top Pelanggan memakai data `orders`.
- Produk Teratas memakai data `order_items` dan relasi `order`.

## Test yang Disarankan

File: `tests/Feature/GM/GmListUiTest.php`

### 1. Top Pelanggan mengikuti periode dari-sampai

Skenario:

- Buat customer A dengan order tanggal 2026-06-01.
- Buat customer B dengan order tanggal 2026-05-01.
- Request:

```php
route('gm.dashboard', [
    'top_start_date' => '2026-06-01',
    'top_end_date' => '2026-06-30',
])
```

Assert:

- Customer A tampil.
- Customer B tidak tampil di section Top Pelanggan.

Catatan:

- Agar assert tidak tertukar dengan section lain, bisa gunakan nama order/customer unik atau cek string spesifik.

### 2. Produk Teratas mengikuti periode dari-sampai

Skenario:

- Produk A terjual dalam periode.
- Produk B terjual di luar periode.

Assert:

- Produk A tampil.
- Produk B tidak tampil.

### 3. Produk Teratas memakai tanggal sampai secara inklusif

Skenario:

- Order dibuat pada `top_end_date` jam 23:00.
- Produk harus tetap tampil.

Alasan:

- `topEndDate` harus memakai `endOfDay()`.

### 4. Pagination mempertahankan periode

Skenario:

- Buat lebih dari 5 top customers atau products.
- Request dengan `top_start_date` dan `top_end_date`.

Assert:

- Response mengandung `top_start_date=...`.
- Response mengandung `top_end_date=...`.
- Response mengandung `top_customers_page=2` atau `top_products_page=2`.

File existing yang relevan:

- `tests/Feature/PaginationTablesTest.php`
  - Sudah ada test pagination dashboard GM.
  - Tambahkan assert periode top agar tidak hilang ketika pagination.

## Validasi dan Edge Case

### Tanggal akhir sebelum tanggal mulai

Request:

- `top_start_date = 2026-06-10`
- `top_end_date = 2026-06-01`

Hasil:

- Laravel validation error.

### Salah satu tanggal kosong

Opsi rekomendasi:

- Jika `top_start_date` kosong, default awal bulan berjalan.
- Jika `top_end_date` kosong, default hari ini.

Alternatif:

- Wajibkan keduanya jika salah satu terisi.

Rekomendasi:

- Untuk UX dashboard, lebih nyaman default partial seperti di atas, tetapi tetap validasi `top_end_date after_or_equal top_start_date` bila keduanya terisi.

### Order dibatalkan

Existing query menghitung semua order, termasuk dibatalkan, jika ada item.

Pilihan:

1. Pertahankan existing behavior: semua order masuk top pelanggan/produk.
2. Hanya hitung order sukses/aktif, misalnya status bukan `dibatalkan`.
3. Hanya hitung payment `terverifikasi`.

Rekomendasi awal:

- Pertahankan existing behavior agar perubahan hanya fokus pada periode.
- Jika bisnis menginginkan “terlaris” berdasarkan penjualan valid, buat task terpisah untuk mengecualikan order batal/ditolak.

## Urutan Eksekusi yang Disarankan

1. Update `GM\DashboardController@index` agar menerima `Request`.
2. Tambahkan parsing dan validasi `top_start_date` serta `top_end_date`.
3. Buat variabel `$topStartDate`, `$topEndDate`, dan `$topFilters`.
4. Update query `$topCustomers` memakai `whereBetween('created_at', [$topStartDate, $topEndDate])`.
5. Update query `$topProducts` memakai `whereHas('order', whereBetween created_at range yang sama)`.
6. Tambahkan `orderByDesc('total_sales')` sebagai tie-breaker produk.
7. Kirim variabel baru ke view.
8. Update header `Top Pelanggan` dengan form `Dari` dan `Sampai`.
9. Update header/caption `Produk Teratas` agar menampilkan periode yang sama.
10. Pastikan hidden input `year` dan `month` tetap dikirim dari form periode top.
11. Tambahkan atau update tests.
12. Jalankan:

```bash
php artisan test --filter=GmListUiTest
php artisan test --filter=PaginationTablesTest
```

## Output yang Diharapkan

Dashboard GM akan menampilkan:

- Filter periode:
  - `Dari: [tanggal]`
  - `Sampai: [tanggal]`
- `Top Pelanggan` berdasarkan order dalam tanggal tersebut.
- `Produk Teratas` berdasarkan order item dari order dalam tanggal tersebut.
- Caption periode yang jelas:
  - `dari 01 Jun 2026 sampai 30 Jun 2026`
- Pagination tetap menjaga filter periode.
