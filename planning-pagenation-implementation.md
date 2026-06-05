# Planning Implementasi Pagination untuk View Tabel Database

## Ringkasan Kondisi Saat Ini

Project Laravel ecommerce ini sudah memiliki banyak view yang menampilkan data database dalam bentuk tabel. Sebagian besar halaman list utama sudah memakai pagination Laravel, tetapi masih ada beberapa tabel yang memakai `get()`, `limit()->get()`, atau collection relation langsung sehingga belum memiliki navigasi halaman.

Catatan penamaan:

- Nama file mengikuti permintaan: `planning-pagenation-implementation.md`.
- Istilah teknis yang dipakai di kode tetap `pagination`, sesuai istilah Laravel.

## Tujuan

Menambahkan pagination pada semua view yang menampilkan tabel dari database agar:

1. Halaman tidak berat ketika data bertambah banyak.
2. Semua tabel list memiliki pola navigasi yang konsisten.
3. Filter/search tetap terbawa saat pindah halaman.
4. Nomor urut tabel tetap benar lintas halaman.
5. Tabel dashboard yang menampilkan ringkasan tetap terkontrol dan tidak memuat data terlalu banyak.

## Prinsip Implementasi

1. Gunakan pagination bawaan Laravel:
   - `paginate($perPage)`
   - `withQueryString()` untuk halaman yang punya filter/search.
2. Gunakan `simplePaginate()` hanya jika total row tidak perlu ditampilkan dan query sangat berat.
3. Gunakan `links()` di Blade untuk render navigasi.
4. Nomor urut tabel memakai:

```php
$collection->firstItem() + $loop->index
```

5. Untuk tabel dashboard yang sifatnya preview, ada dua pilihan:
   - Tetap `limit()` jika tabel hanya preview dengan tombol “Lihat Semua”.
   - Atau buat pagination lokal dengan query parameter khusus jika user benar-benar ingin semua tabel database berpaginasi.
6. Jika satu halaman punya lebih dari satu tabel paginated, gunakan nama page query yang berbeda:

```php
paginate(5, ['*'], 'recent_orders_page')
paginate(5, ['*'], 'recent_payments_page')
```

Ini penting agar klik pagination satu tabel tidak mengubah tabel lain di halaman yang sama.

## Inventaris View Tabel

### Sudah menggunakan pagination

Halaman berikut sudah memakai `paginate()` di controller dan `links()` di view. Perlu dicek konsistensi nomor urut dan `withQueryString()`.

| Area | View | Controller | Status |
| --- | --- | --- | --- |
| Admin Produk | `resources/views/admin/products/index.blade.php` | `Admin\ProductController@index` | Sudah paginate |
| Admin Kategori | `resources/views/admin/categories/index.blade.php` | `Admin\ProductCategoryController@index` | Sudah paginate |
| Admin Brand | `resources/views/admin/brands/index.blade.php` | `Admin\ProductBrandController@index` | Sudah paginate |
| Admin Orders | `resources/views/admin/orders/index.blade.php` | `Admin\OrderController@index` | Sudah paginate + query string |
| Admin Pending Ongkir | `resources/views/admin/pending-shipping-costs/index.blade.php` | `Admin\OrderController@pendingShippingCosts` | Sudah paginate |
| Admin Payments | `resources/views/admin/payments/index.blade.php` | `Admin\PaymentController@index` | Sudah paginate + query string |
| Admin Payment Methods | `resources/views/admin/payment-methods/index.blade.php` | `Admin\PaymentMethodController@index` | Sudah paginate |
| Marketing Promosi | `resources/views/marketing/promotions/index.blade.php` | `Marketing\PromotionController@index` | Sudah paginate |
| Marketing Users | `resources/views/marketing/users/index.blade.php` | `Marketing\UserController@index` | Sudah paginate + query string |
| GM Reports | `resources/views/gm/reports/index.blade.php` | `GM\ReportController@index` | Sudah paginate + query string |
| Direktur Strategic Reports | `resources/views/direktur/reports/strategic.blade.php` | `Direktur\ReportController@strategic` | Sudah paginate + query string |
| Pelanggan Order History | `resources/views/pelanggan/orders/history.blade.php` | `Pelanggan\OrderController@history` | Sudah paginate |
| Pelanggan Products | `resources/views/pelanggan/products/index.blade.php` | `Pelanggan\ProductController@index` | Sudah paginate |

### Belum menggunakan pagination

Halaman berikut masih perlu direncanakan perubahan.

| Area | View | Sumber Data | Catatan |
| --- | --- | --- | --- |
| Admin Users | `resources/views/admin/users/index.blade.php` | `Role::with(['users' => ...])->get()` | Tabel per role memakai collection `role->users`; perlu pagination per role atau ubah jadi tabel user tunggal dengan filter role |
| Admin Dashboard | `resources/views/admin/dashboard.blade.php` | `$recentOrders`, `$recentPayments` | Tabel/daftar preview memakai `limit(5)->get()` |
| Admin Order Detail | `resources/views/admin/orders/show.blade.php` | `$order->items` | Tabel item order; bisa banyak jika order besar |
| GM Dashboard | `resources/views/gm/dashboard.blade.php` | `$topProducts`, `$topCustomers`, `$recentOrders` | Beberapa tabel preview memakai `limit(5)->get()` |
| Direktur Dashboard | `resources/views/direktur/dashboard.blade.php` | `$topProducts`, `$topCustomers`, `$recentOrders`, `$activePromotions` | Beberapa tabel preview memakai `limit()->get()` |

### Tidak direkomendasikan untuk pagination UI

| Area | View | Alasan |
| --- | --- | --- |
| GM Excel Export | `resources/views/gm/reports/excel.blade.php` | Ini template export, bukan tampilan UI. Pagination di export bisa memotong data laporan. |

## Rencana Perubahan Per Area

### 1. Admin Users

File:

- `app/Http/Controllers/Admin/UserController.php`
- `resources/views/admin/users/index.blade.php`

Kondisi saat ini:

```php
$roles = Role::with(['users' => fn ($query) => $query->latest()])
    ->orderBy('id')
    ->get();
```

Masalah:

- Semua user untuk semua role dimuat sekaligus.
- Tabel per role tidak bisa memakai `links()` secara natural karena data user berada di eager loaded collection.

Opsi implementasi:

#### Opsi A: Ubah menjadi satu tabel user dengan filter role

Direkomendasikan.

Controller:

- Ambil daftar role untuk filter.
- Query `User::with('roles')`.
- Filter by `role` jika request berisi role.
- Search nama/email/telepon jika ingin sekalian konsisten.
- `paginate(10)->withQueryString()`.

View:

- Satu tabel user.
- Tambahkan filter role di atas tabel.
- Tampilkan role user sebagai badge.
- Render `{{ $users->links() }}`.

Kelebihan:

- Pagination lebih sederhana dan standar.
- Query lebih efisien.
- Nomor urut mudah benar.

Kekurangan:

- Tampilan berubah dari beberapa tabel per role menjadi satu tabel.

#### Opsi B: Pertahankan tabel per role dengan pagination per role

Controller:

- Ambil roles.
- Untuk tiap role, query user role tersebut dengan `paginate(10, ['*'], "role_{$role->id}_page")`.
- Kirim map paginator per role ke view.

View:

- Tetap render tabel per role.
- Setiap tabel punya `links()` sendiri.

Kelebihan:

- Tampilan sekarang tetap dipertahankan.

Kekurangan:

- Lebih kompleks.
- Banyak paginator dalam satu halaman bisa membuat URL ramai.
- Query bertambah sesuai jumlah role.

Rekomendasi:

- Gunakan Opsi A jika tidak ada kebutuhan wajib memisahkan tabel per role.
- Gunakan Opsi B jika desain “satu tabel per role” harus dipertahankan.

### 2. Admin Dashboard

File:

- `app/Http/Controllers/Admin/DashboardController.php`
- `resources/views/admin/dashboard.blade.php`

Tabel/data terkait:

- `recentOrders`
- `recentPayments`

Kondisi saat ini:

- `recentOrders`: `limit(5)->get()`
- `recentPayments`: `limit(5)->get()`

Rencana:

Jika dashboard harus ikut dipaginasi:

```php
$recentOrders = Order::with(['user', 'payment', 'paymentMethod'])
    ->withCount('items')
    ->latest()
    ->paginate(5, ['*'], 'recent_orders_page')
    ->withQueryString();

$recentPayments = Payment::with(['order.user', 'paymentMethod'])
    ->latest()
    ->paginate(5, ['*'], 'recent_payments_page')
    ->withQueryString();
```

View:

- Tambahkan `{{ $recentOrders->links() }}` di bawah tabel pesanan terbaru.
- Untuk pembayaran terbaru, jika tetap berupa list card bukan tabel, bisa tetap tambahkan pagination di bawah list.

Catatan:

- Karena dashboard sudah punya link “Lihat Semua”, boleh juga diputuskan tetap preview tanpa pagination. Namun jika mengikuti permintaan “semua view yang menampilkan tabel dari database”, tabel `Pesanan Terbaru` perlu dipaginasi.

### 3. Admin Order Detail

File:

- `app/Http/Controllers/Admin/OrderController.php`
- `resources/views/admin/orders/show.blade.php`

Tabel:

- Order items pada detail order.

Kondisi saat ini:

- Controller load `items.product`.
- View loop `$order->items`.

Rencana:

- Jangan load semua item hanya untuk tabel.
- Buat paginator khusus:

```php
$order->load(['user', 'paymentMethod', 'payment', 'delivery']);
$items = $order->items()
    ->with('product')
    ->paginate(10, ['*'], 'items_page')
    ->withQueryString();
```

View:

- Ganti `$order->items` pada tabel menjadi `$items`.
- Gunakan nomor urut `$items->firstItem() + $loop->index`.
- Tambahkan `{{ $items->links() }}`.

Perhatian:

- Bagian lain di detail order yang butuh jumlah item jangan memakai `$order->items->sum()` jika relasi tidak di-load. Gunakan `$order->items()->sum('quantity')`, `withCount`, atau variabel agregat dari controller.

### 4. GM Dashboard

File:

- `app/Http/Controllers/GM/DashboardController.php`
- `resources/views/gm/dashboard.blade.php`

Tabel/data terkait:

- `topProducts`
- `topCustomers`
- `recentOrders`

Rencana:

- Ganti query `limit(5)->get()` menjadi paginator dengan page name berbeda:

```php
$topProducts = OrderItem::query()
    ...
    ->paginate(5, ['*'], 'top_products_page')
    ->withQueryString();

$topCustomers = Order::query()
    ...
    ->paginate(5, ['*'], 'top_customers_page')
    ->withQueryString();

$recentOrders = Order::with(['user', 'payment'])
    ->withCount('items')
    ->latest()
    ->paginate(5, ['*'], 'recent_orders_page')
    ->withQueryString();
```

View:

- Tambahkan `links()` di bawah masing-masing tabel.
- Pastikan filter `year` dan `month` tetap terbawa melalui `withQueryString()`.
- Nomor urut memakai `firstItem()`.

Catatan:

- Query agregat `topProducts` dan `topCustomers` masih bisa dipaginate karena berbasis query builder/Eloquent sebelum `get()`.

### 5. Direktur Dashboard

File:

- `app/Http/Controllers/Direktur/DashboardController.php`
- `resources/views/direktur/dashboard.blade.php`

Tabel/data terkait:

- `topProducts`
- `topCustomers`
- `recentOrders`
- `activePromotions`

Rencana:

- Ganti `limit()->get()` menjadi paginator dengan page name berbeda:

```php
$topProducts = OrderItem::query()
    ...
    ->paginate(5, ['*'], 'top_products_page')
    ->withQueryString();

$topCustomers = Order::query()
    ...
    ->paginate(5, ['*'], 'top_customers_page')
    ->withQueryString();

$recentOrders = Order::with(['user', 'payment'])
    ->withCount('items')
    ->latest()
    ->paginate(6, ['*'], 'recent_orders_page')
    ->withQueryString();

$activePromotions = Promotion::with('createdBy')
    ...
    ->paginate(5, ['*'], 'active_promotions_page')
    ->withQueryString();
```

View:

- Tambahkan `links()` di bawah masing-masing tabel.
- Pastikan filter `year` dan `month` tetap terbawa.
- Jika `activePromotions` tampil sebagai list/card, tetap bisa diberi pagination jika datanya berasal dari database dan list panjang.

## Rencana Konsistensi untuk Halaman yang Sudah Paginate

Walaupun sudah memakai pagination, beberapa halaman perlu dicek konsistensinya:

1. Pastikan semua halaman filter/search memakai `withQueryString()`:
   - Admin products jika nanti punya filter.
   - Marketing promotions jika nanti punya filter.
   - Admin categories/brands/payment methods jika nanti punya sorting/filter.
2. Pastikan nomor urut memakai paginator:

```blade
{{ $items->firstItem() + $loop->index }}
```

3. Pastikan `links()` ada di bawah tabel dan tidak tersembunyi oleh container overflow.
4. Pastikan empty state tetap muncul saat data kosong.

## Rencana Pagination Component

Opsional tapi disarankan:

- Gunakan pagination Tailwind bawaan Laravel.
- Jika tampilan default tidak cocok dengan desain, publish pagination view:

```bash
php artisan vendor:publish --tag=laravel-pagination
```

Lalu sesuaikan:

- `resources/views/vendor/pagination/tailwind.blade.php`

Namun untuk scope awal, gunakan `{{ $paginator->links() }}` terlebih dahulu agar minim risiko.

## Rencana Test

Tambahkan atau update feature test untuk memastikan pagination berjalan.

File test yang bisa ditambahkan:

- `tests/Feature/PaginationTablesTest.php`

Skenario minimal:

1. Admin users menampilkan pagination saat jumlah user melebihi per page.
2. Admin order detail menampilkan item page 1 dan page 2 dengan query `items_page`.
3. Admin dashboard recent orders bisa pindah ke `recent_orders_page=2`.
4. GM dashboard mempertahankan filter `year` dan `month` saat pagination.
5. Direktur dashboard mempertahankan filter `year` dan `month` saat pagination.
6. Halaman yang sudah punya search/filter tetap membawa query string saat pindah halaman:
   - Admin orders.
   - Admin payments.
   - Marketing users.
   - GM reports.
   - Direktur reports.

Assertion yang disarankan:

- `assertOk()`
- `assertSee()` untuk data page aktif.
- `assertDontSee()` untuk data page lain jika dataset cukup jelas.
- `assertSee('page=2', false)` atau assert link pagination mengandung query terkait.

## Risiko dan Hal yang Perlu Dijaga

1. Banyak paginator dalam satu halaman
   - Wajib pakai page name unik agar tidak saling mengganggu.

2. Query agregat dengan pagination
   - Pastikan query masih berada di builder sebelum `paginate()`.
   - Hindari `get()->paginate()` manual.

3. Relasi yang sebelumnya collection
   - Contoh `$order->items` menjadi `$items`.
   - Pastikan bagian view lain tidak bergantung pada relasi yang tidak di-load.

4. Filter dashboard
   - Pagination harus mempertahankan `year` dan `month`.

5. Export Excel
   - Jangan dipaginasi karena export laporan biasanya harus memuat semua data sesuai filter.

## Urutan Implementasi

1. Audit final semua view `<table>` dan tandai mana yang sudah/baru perlu pagination.
2. Update Admin Users:
   - pilih Opsi A atau B,
   - update controller,
   - update view,
   - tambahkan `links()`.
3. Update Admin Order Detail:
   - buat paginator item order,
   - update view table item,
   - jaga agregat item.
4. Update Admin Dashboard:
   - paginate `recentOrders`,
   - paginate atau tetap preview `recentPayments` sesuai keputusan scope,
   - tambahkan page name unik.
5. Update GM Dashboard:
   - paginate `topProducts`, `topCustomers`, `recentOrders`,
   - tambahkan `links()` per tabel.
6. Update Direktur Dashboard:
   - paginate `topProducts`, `topCustomers`, `recentOrders`, `activePromotions`,
   - tambahkan `links()` per tabel/list.
7. Rapikan nomor urut di semua tabel paginated.
8. Tambahkan feature test pagination.
9. Jalankan test:

```bash
php artisan test --filter=PaginationTablesTest
php artisan test --filter=OrderFilterTest
php artisan test --filter=CheckoutTest
```

10. Jalankan lint controller yang diubah:

```bash
php -l app/Http/Controllers/Admin/UserController.php
php -l app/Http/Controllers/Admin/OrderController.php
php -l app/Http/Controllers/Admin/DashboardController.php
php -l app/Http/Controllers/GM/DashboardController.php
php -l app/Http/Controllers/Direktur/DashboardController.php
```

## Rekomendasi Scope Tahap Pertama

Untuk tahap pertama, prioritas terbaik:

1. Admin Users, karena saat ini memuat semua user per role.
2. Admin Order Detail, karena item order dapat bertambah banyak.
3. GM dan Direktur dashboard, karena memiliki beberapa tabel agregat.
4. Admin dashboard recent orders, karena sudah punya tabel database.
5. Review halaman yang sudah paginate untuk konsistensi nomor urut dan query string.

Dashboard preview yang sudah punya tombol “Lihat Semua” bisa tetap dibatasi jika ingin menjaga dashboard tetap ringkas. Namun bila mengikuti permintaan secara literal, setiap tabel database di dashboard juga diberi paginator kecil dengan query parameter unik.
