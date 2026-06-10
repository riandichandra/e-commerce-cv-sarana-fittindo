# Planning Status Pesanan Dashboard GM

## Ringkasan Permintaan

Perbaiki fitur filter pada card `Pesanan` di dashboard GM dengan kriteria:

1. Filter hanya mempengaruhi data di card `Pesanan`.
2. Terdapat filter data berdasarkan periode `start date` sampai `end date`.
3. Terdapat filter berdasarkan status pemesanan.

Artinya, saat GM mengubah filter pada card `Pesanan`, area lain pada dashboard tidak ikut berubah, seperti:

- `Detail Pesanan`
- `Status Pesanan`
- `Top Pelanggan`
- `Produk Teratas`
- `Tren Pendapatan`
- card pendapatan dan pelanggan

## Temuan Codebase Saat Ini

### Controller Dashboard GM

File: `app/Http/Controllers/GM/DashboardController.php`

Method `index(Request $request)` saat ini sudah menerima filter:

- `order_period`
- `order_start_date`
- `order_end_date`
- `order_status`

Filter tersebut diproses melalui:

```php
$orderFilters = $this->orderFilters($request, array_keys($orderPeriodOptions), $orderStatuses);
[$orderStartDate, $orderEndDate] = $this->orderPeriodRange($orderFilters);
```

Kemudian dibuat query:

```php
$ordersInPeriodQuery = Order::query()
    ->whereBetween('created_at', [$orderStartDate, $orderEndDate]);
```

Masalahnya, query ini dipakai untuk beberapa area:

```php
$totalOrders = (clone $ordersInPeriodQuery)->count();
$selesaiOrders = (clone $ordersInPeriodQuery)->where('status', 'selesai')->count();
$diprosesOrders = (clone $ordersInPeriodQuery)
    ->whereIn('status', ['pembayaran_dikonfirmasi', 'diproses', 'dikirim'])
    ->count();

$orderStatusCounts = (clone $ordersInPeriodQuery)
    ->select('status', DB::raw('count(*) as total'))
    ->groupBy('status')
    ->pluck('total', 'status');

$detailOrders = (clone $ordersInPeriodQuery)
    ->when($orderFilters['order_status'], fn ($query, $status) => $query->where('status', $status))
    ->with(['user', 'payment'])
    ->withCount('items')
    ->latest()
    ->paginate(5, ['*'], 'detail_orders_page')
    ->withQueryString();
```

Akibatnya:

- Filter card `Pesanan` mempengaruhi card `Pesanan`.
- Filter juga mempengaruhi distribusi `Status Pesanan`.
- Filter juga mempengaruhi tabel `Detail Pesanan`.
- Pagination tabel detail membawa query filter card pesanan.

Ini bertentangan dengan kriteria baru: filter hanya mempengaruhi data di card `Pesanan`.

### View Dashboard GM

File: `resources/views/gm/dashboard.blade.php`

Filter sudah berada di card `Pesanan`:

- `order_period`
- `order_start_date`
- `order_end_date`
- `order_status`

Card `Total Pendapatan`, `Pendapatan Bulanan`, dan `Produk Teratas` masih menyimpan filter pesanan sebagai hidden input:

```blade
<input type="hidden" name="order_period" value="{{ $orderFilters['order_period'] }}">
<input type="hidden" name="order_start_date" value="{{ $orderFilters['order_start_date'] }}">
<input type="hidden" name="order_end_date" value="{{ $orderFilters['order_end_date'] }}">
<input type="hidden" name="order_status" value="{{ $orderFilters['order_status'] }}">
```

Masalahnya:

- Saat user mengganti filter pendapatan/tahun, filter card `Pesanan` ikut terbawa.
- Jika kriteria baru adalah filter hanya untuk card `Pesanan`, hidden input seperti ini sebaiknya dihapus dari form non-card-pesanan.

### Test Saat Ini

File: `tests/Feature/GM/GmListUiTest.php`

Saat ini ada test yang memastikan filter pesanan mempengaruhi detail order:

```php
test_gm_dashboard_can_filter_detail_orders_by_status()
test_gm_dashboard_can_filter_detail_orders_by_custom_period()
test_gm_dashboard_uses_manual_date_range_even_when_period_dropdown_is_not_custom()
```

Dengan kriteria baru, test ini perlu diubah:

- Filter card `Pesanan` harus mengubah angka card.
- Filter card `Pesanan` tidak boleh mengubah tabel detail.
- Filter card `Pesanan` tidak boleh mengubah distribusi status.

File: `tests/Feature/PaginationTablesTest.php`

Ada test:

```php
test_gm_dashboard_detail_order_pagination_keeps_filters()
```

Test ini saat ini memastikan pagination detail order membawa:

- `order_period`
- `order_status`

Dengan kriteria baru, test ini perlu disesuaikan karena filter card `Pesanan` tidak lagi perlu ikut pagination detail order.

## Definisi Perilaku Baru

### Filter Card Pesanan

Filter di card `Pesanan` harus memakai query string khusus:

- `card_order_start_date`
- `card_order_end_date`
- `card_order_status`

Rekomendasi:

- Hilangkan `order_period` dari card agar sesuai permintaan yang menyebut periode `start date` sampai `end date`.
- Card langsung menampilkan dua date input:
  - `card_order_start_date`
  - `card_order_end_date`
- Dropdown status:
  - `card_order_status`

### Output Card Pesanan

Card tetap bisa menampilkan:

```text
{pesanan_selesai} / {total_pesanan}
{pesanan_berjalan} pesanan sedang berjalan
Periode: {start_date} - {end_date}
Status: {status atau Semua Status}
```

Namun karena ada filter status, perlu definisi angka yang jelas.

Rekomendasi paling konsisten:

- Angka kanan `total_pesanan`: total pesanan dalam periode dan status yang dipilih.
- Angka kiri `pesanan_selesai`: pesanan selesai dalam periode dan status yang dipilih.
- Jika `card_order_status = selesai`, maka format bisa menjadi `N / N`.
- Jika `card_order_status = dikirim`, maka format bisa menjadi `0 / N`.

Alternatif jika ingin `selesai / total` selalu berdasarkan periode saja:

- Status filter hanya mempengaruhi total detail status, bukan angka selesai.
- Ini membingungkan karena card sedang difilter status.

Rekomendasi: semua angka dalam card mengikuti periode + status.

### Area Dashboard Lain

Area lain tidak boleh memakai filter card:

- `Status Pesanan`: kembali global atau mengikuti aturan dashboard awal, bukan filter card.
- `Detail Pesanan`: kembali latest/global atau punya filter sendiri di masa depan.
- `Top Pelanggan`: tetap memakai `year/month`.
- `Produk Teratas`: tetap memakai `year`.
- `Total Pendapatan`: tetap memakai `year`.
- `Pendapatan Bulanan`: tetap memakai `year/month`.

## Rencana Implementasi

### 1. Ganti nama filter card pesanan di controller

File: `app/Http/Controllers/GM/DashboardController.php`

Ganti filter lama:

- `order_period`
- `order_start_date`
- `order_end_date`
- `order_status`

Menjadi filter khusus card:

- `card_order_start_date`
- `card_order_end_date`
- `card_order_status`

Validasi:

```php
$cardOrderFilters = $request->validate([
    'card_order_start_date' => ['nullable', 'date'],
    'card_order_end_date' => ['nullable', 'date', 'after_or_equal:card_order_start_date'],
    'card_order_status' => ['nullable', 'string', Rule::in($orderStatuses)],
]);
```

Default:

```php
$cardOrderStartDate = Carbon::parse($cardOrderFilters['card_order_start_date'] ?? now()->startOfMonth())->startOfDay();
$cardOrderEndDate = Carbon::parse($cardOrderFilters['card_order_end_date'] ?? now()->endOfMonth())->endOfDay();
$cardOrderStatus = $cardOrderFilters['card_order_status'] ?? null;
```

Catatan:

- Jika hanya start date diisi, end date default akhir bulan atau hari ini. Rekomendasi: validasi UX sebaiknya memaksa keduanya, tetapi untuk dashboard lebih ramah jika satu sisi tetap punya default.
- Jika end date lebih kecil dari start date, Laravel validation redirect dengan error.

### 2. Pisahkan query card pesanan

Buat query khusus:

```php
$cardOrdersQuery = Order::query()
    ->whereBetween('created_at', [$cardOrderStartDate, $cardOrderEndDate])
    ->when($cardOrderStatus, fn ($query, $status) => $query->where('status', $status));
```

Hitung card dari query tersebut:

```php
$totalOrders = (clone $cardOrdersQuery)->count();
$selesaiOrders = (clone $cardOrdersQuery)->where('status', 'selesai')->count();
$diprosesOrders = (clone $cardOrdersQuery)
    ->whereIn('status', ['pembayaran_dikonfirmasi', 'diproses', 'dikirim'])
    ->count();
```

Catatan:

- Karena status filter sudah diterapkan ke `$cardOrdersQuery`, jika status dipilih `dikirim`, `selesaiOrders` otomatis 0.
- Jika status dipilih `selesai`, `diprosesOrders` otomatis 0.

### 3. Kembalikan query area lain agar tidak memakai filter card

Ubah `orderStatusCounts`.

Dari:

```php
$orderStatusCounts = (clone $ordersInPeriodQuery)
```

Menjadi:

```php
$orderStatusCounts = Order::select('status', DB::raw('count(*) as total'))
    ->groupBy('status')
    ->pluck('total', 'status');
```

Ubah `detailOrders`.

Dari:

```php
$detailOrders = (clone $ordersInPeriodQuery)
    ->when($orderFilters['order_status'], ...)
```

Menjadi:

```php
$detailOrders = Order::with(['user', 'payment'])
    ->withCount('items')
    ->latest()
    ->paginate(5, ['*'], 'detail_orders_page')
    ->withQueryString();
```

Catatan:

- `withQueryString()` boleh tetap dipakai, tetapi jika query string card ikut terbawa ke pagination detail, itu secara URL masih ada.
- Jika ingin benar-benar bersih, gunakan `appends($request->except(['card_order_start_date', 'card_order_end_date', 'card_order_status']))`.
- Rekomendasi: untuk detail orders, jangan bawa filter card pesanan.

Contoh:

```php
$detailOrders = Order::with(['user', 'payment'])
    ->withCount('items')
    ->latest()
    ->paginate(5, ['*'], 'detail_orders_page')
    ->appends($request->except([
        'card_order_start_date',
        'card_order_end_date',
        'card_order_status',
    ]));
```

### 4. Update data yang dikirim ke view

Ganti variabel:

- `$orderFilters` menjadi `$cardOrderFilters`
- `$orderStartDate` menjadi `$cardOrderStartDate`
- `$orderEndDate` menjadi `$cardOrderEndDate`

Tambahkan:

```php
'cardOrderFilters',
'cardOrderStartDate',
'cardOrderEndDate',
```

Jika masih butuh option status:

```php
'orderStatuses'
```

Hapus:

```php
'orderPeriodOptions'
```

Karena kriteria baru hanya meminta periode start date sampai end date, bukan preset periode.

### 5. Update form card Pesanan

File: `resources/views/gm/dashboard.blade.php`

Hapus dropdown periode:

```blade
<select id="order_period" name="order_period">...</select>
```

Ganti input tanggal:

```blade
<input type="date" name="card_order_start_date" value="{{ $cardOrderFilters['card_order_start_date'] }}">
<input type="date" name="card_order_end_date" value="{{ $cardOrderFilters['card_order_end_date'] }}">
```

Ganti dropdown status:

```blade
<select name="card_order_status">
    <option value="">Semua Status</option>
    @foreach ($orderStatuses as $status)
        <option value="{{ $status }}" @selected($cardOrderFilters['card_order_status'] === $status)>
            {{ \App\Models\Order::make(['status' => $status])->status_label }}
        </option>
    @endforeach
</select>
```

Hapus JavaScript:

```blade
onchange="document.getElementById('order_period').value = 'custom'"
```

Karena tidak ada lagi dropdown periode preset.

Reset card:

```blade
<a href="{{ route('gm.dashboard', ['year' => $selectedYear, 'month' => $selectedMonth]) }}">Reset</a>
```

### 6. Hapus hidden input filter pesanan dari form lain

Pada form `Total Pendapatan`, hapus:

```blade
<input type="hidden" name="order_period" ...>
<input type="hidden" name="order_start_date" ...>
<input type="hidden" name="order_end_date" ...>
<input type="hidden" name="order_status" ...>
```

Pada form `Pendapatan Bulanan`, hapus hidden input yang sama.

Pada form `Produk Teratas`, hapus hidden input yang sama.

Jika setelah ganti nama masih ada hidden:

```blade
card_order_start_date
card_order_end_date
card_order_status
```

Jangan taruh hidden tersebut di form non-card-pesanan.

Alasan:

- Filter card `Pesanan` tidak boleh terbawa saat user mengganti tahun/bulan/top product.

### 7. Update teks card

Ubah:

```blade
Periode: {{ $orderStartDate->format('d M Y') }} - {{ $orderEndDate->format('d M Y') }}
```

Menjadi:

```blade
Periode: {{ $cardOrderStartDate->format('d M Y') }} - {{ $cardOrderEndDate->format('d M Y') }}
```

Tambahkan status aktif:

```blade
<p class="mt-1 text-xs font-semibold text-gray-400">
    Status: {{ $cardOrderFilters['card_order_status']
        ? \App\Models\Order::make(['status' => $cardOrderFilters['card_order_status']])->status_label
        : 'Semua Status' }}
</p>
```

### 8. Update test

File: `tests/Feature/GM/GmListUiTest.php`

Test baru/ubah:

#### A. Filter card pesanan berdasarkan tanggal

Skenario:

- Buat order Mei.
- Buat order Juni.
- Request:

```php
route('gm.dashboard', [
    'card_order_start_date' => '2026-05-01',
    'card_order_end_date' => '2026-05-31',
])
```

Assert:

- Card menampilkan periode Mei.
- Card count sesuai order Mei.

Catatan:

- Jangan assert tabel detail hanya menampilkan Mei, karena filter tidak boleh mempengaruhi tabel.

#### B. Filter card pesanan berdasarkan status

Skenario:

- Buat order `selesai`.
- Buat order `dikirim`.
- Request `card_order_status=dikirim`.
- Assert card count hanya menghitung `dikirim`.

#### C. Filter card tidak mempengaruhi detail pesanan

Skenario:

- Buat order Mei.
- Buat order Juni.
- Request filter card periode Mei.
- Assert:
  - Card periode Mei.
  - Tabel detail tetap menampilkan order Juni jika order Juni adalah latest/global.

#### D. Filter card tidak mempengaruhi distribusi status

Skenario:

- Buat order `selesai` di Mei.
- Buat order `dikirim` di Juni.
- Request filter card status `selesai` atau periode Mei.
- Assert section `Status Pesanan` tetap memuat status dari order lain.

File: `tests/Feature/PaginationTablesTest.php`

Update test:

- `test_gm_dashboard_detail_order_pagination_keeps_filters` tidak lagi boleh mengharapkan filter card ada di pagination detail.

Ubah menjadi:

```php
$response->assertSee('detail_orders_page=2');
$response->assertDontSee('card_order_status=selesai');
```

Atau hapus test tersebut jika tidak lagi relevan.

### 9. Jalankan verifikasi

Command:

```bash
php -l app/Http/Controllers/GM/DashboardController.php
php -l resources/views/gm/dashboard.blade.php
php -l tests/Feature/GM/GmListUiTest.php
php artisan test --filter=GmListUiTest
php artisan test --filter=PaginationTablesTest
```

Jika memungkinkan, buka dashboard GM dan cek manual:

1. Filter card `Pesanan` tanggal Mei.
2. Pastikan angka card berubah.
3. Pastikan `Detail Pesanan` tidak ikut berubah karena filter card.
4. Pastikan `Status Pesanan` tidak ikut berubah karena filter card.
5. Pastikan card lain tidak membawa filter pesanan ketika mengganti tahun/bulan.

## Risiko dan Catatan

- Jika user mengharapkan tabel detail ikut filter, ini bertentangan dengan kriteria baru nomor 1. Kriteria baru harus diprioritaskan.
- Query string lama `order_period`, `order_start_date`, `order_end_date`, `order_status` mungkin masih tersimpan di URL lama. Setelah implementasi, sebaiknya tidak dipakai lagi untuk card pesanan.
- Jika ingin backward compatibility, controller bisa membaca query lama sebagai fallback, tetapi ini bisa membuat perilaku sulit dipahami. Rekomendasi: pakai query string baru khusus card.
- Perlu hati-hati dengan pagination `withQueryString()` karena bisa membawa filter card ke link tabel lain. Gunakan `appends($request->except([...]))` jika perlu memisahkan query.

## Output yang Diharapkan

Dashboard GM setelah perubahan:

- Card `Pesanan` punya filter:
  - `Start Date`
  - `End Date`
  - `Status Pemesanan`
- Filter tersebut hanya mengubah:
  - angka `selesai / total` di card pesanan
  - jumlah `pesanan sedang berjalan`
  - teks periode/status di card pesanan
- Filter tersebut tidak mengubah:
  - tabel `Detail Pesanan`
  - distribusi `Status Pesanan`
  - `Top Pelanggan`
  - `Produk Teratas`
  - card pendapatan
  - pagination area lain
