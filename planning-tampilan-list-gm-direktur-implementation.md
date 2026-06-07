# Planning Implementasi Tampilan List GM dan Direktur

## Tujuan
Merapikan semua tampilan list yang ada di area GM dan Direktur agar konsisten dengan tampilan list admin/marketing yang sudah dirapikan: tabel lebih bersih, data lebih mudah dipindai, badge status lebih jelas, empty state lebih rapi, dan seluruh header nomor memakai `No.` bukan simbol `#`.

## Analisis Codebase

### Route GM
File: `routes/web.php`

Route GM saat ini berada pada group:
- Prefix: `/gm`
- Name: `gm.`
- Middleware: `auth`, `role:gm`

Route yang memiliki list:
- `gm.dashboard` melalui `GM\DashboardController@index`
- `gm.reports.index` melalui `GM\ReportController@index`
- `gm.reports.download` untuk export Excel, tidak perlu diubah sebagai tampilan web
- `gm.settings.index` tidak memiliki list utama

### Route Direktur
File: `routes/web.php`

Route Direktur saat ini berada pada group:
- Prefix: `/direktur`
- Name: `direktur.`
- Middleware: `auth`, `role:direktur`

Route yang memiliki list:
- `direktur.dashboard` melalui `Direktur\DashboardController@index`
- `direktur.reports.strategic` melalui `Direktur\ReportController@strategic`
- `direktur.settings.index` tidak memiliki list utama

## Analisis Controller dan Data List

### GM Dashboard
File: `app/Http/Controllers/GM/DashboardController.php`

Data list yang dikirim:
- `$topCustomers`: pelanggan dengan jumlah pesanan terbanyak, paginated.
- `$recentOrders`: pesanan terbaru, paginated.
- `$topProducts`: produk terlaris, paginated.
- `$orderStatusCounts`: ringkasan status pesanan.
- `$monthlySales`: data grafik penjualan bulanan.

View terkait:
- `resources/views/gm/dashboard.blade.php`

### GM Report
File: `app/Http/Controllers/GM/ReportController.php`

Data list yang dikirim:
- `$orders`: laporan order dengan relasi `user`, `payment.paymentMethod`, dan `items`, paginated 10 data.
- `$summary`: total order, revenue, discount, dan verified revenue.
- `$filters`: filter tanggal, status pesanan, dan status pembayaran.

View terkait:
- `resources/views/gm/reports/index.blade.php`
- `resources/views/gm/reports/excel.blade.php`

Catatan:
- `excel.blade.php` adalah template export Excel, tidak perlu dirapikan seperti UI web agar format export tetap stabil.

### Direktur Dashboard
File: `app/Http/Controllers/Direktur/DashboardController.php`

Data list yang dikirim:
- `$topCustomers`: pelanggan terbaik berdasarkan order dan total belanja, paginated.
- `$recentOrders`: pesanan terbaru, paginated.
- `$topProducts`: produk terlaris berdasarkan total sales, paginated.
- `$activePromotions`: promosi aktif, paginated.
- `$orderStatusCounts`: ringkasan status pesanan.
- `$monthlyRevenueTrend`: data grafik revenue bulanan.

View terkait:
- `resources/views/direktur/dashboard.blade.php`

### Direktur Strategic Report
File: `app/Http/Controllers/Direktur/ReportController.php`

Data list yang dikirim:
- `$orders`: data pesanan monitoring, paginated 10 data.
- `$topProducts`: produk terlaris, limit 8.
- `$promotionStatus`: ringkasan running/upcoming/inactive.
- `$orderStatusCounts`: ringkasan status pesanan dari hasil filter.
- `$summary`: total order, sales, discount, dan verified revenue.

View terkait:
- `resources/views/direktur/reports/strategic.blade.php`

## Analisis Struktur Database

### Tabel `orders`
Migration: `database/migrations/2026_04_30_141321_create_orders_table.php`

Kolom relevan:
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
- `shipping_city`
- `created_at`

Relasi model:
- `Order::user()`
- `Order::items()`
- `Order::payment()`
- `Order::paymentMethod()`

Accessor yang dapat dipakai:
- `$order->status_label`
- `$order->status_badge_class`

### Tabel `order_items`
Migration: `database/migrations/2026_04_30_141322_create_order_items_table.php`

Kolom relevan:
- `order_id`
- `product_id`
- `product_name`
- `product_price`
- `quantity`
- `subtotal`

Dipakai untuk:
- Top produk GM/Direktur.
- Jumlah item pada list order.

### Tabel `payments`
Migration: `database/migrations/2026_04_30_141324_create_payments_table.php`

Kolom relevan:
- `order_id`
- `payment_method_id`
- `amount`
- `status`
- `transfer_date`
- `sender_name`
- `verified_at`

Relasi model:
- `Payment::order()`
- `Payment::paymentMethod()`

Accessor yang dapat dipakai:
- `$payment->status_label`

### Tabel `products`
Migration: `database/migrations/2026_04_30_141259_create_products_table.php`

Kolom relevan:
- `name`
- `price`
- `stock`
- `is_active`
- `is_featured`

Catatan:
- Di dashboard GM/Direktur top produk berasal dari agregasi `order_items`, bukan langsung list produk.

### Tabel `promotions`
Migration: `database/migrations/2026_04_30_141343_create_promotions_table.php`

Kolom relevan:
- `name`
- `code`
- `type`
- `value`
- `start_date`
- `end_date`
- `is_active`
- `created_by`

Dipakai di dashboard Direktur untuk `activePromotions`.

## Scope Implementasi

### 1. Rapikan List pada GM Dashboard
File: `resources/views/gm/dashboard.blade.php`

Rencana perubahan:
- Rapikan section `Top Pelanggan` menjadi card/table putih dengan border dan header rapi.
- Pastikan kolom nomor memakai `No.` jika menggunakan nomor urut.
- Rapikan section `Pesanan Terbaru` menjadi table/card yang lebih mudah dipindai:
  - Pesanan dan tanggal.
  - Pelanggan.
  - Jumlah item.
  - Total.
  - Status pembayaran.
  - Status pesanan.
- Rapikan section `Top Produk` menjadi list/table kompak:
  - No.
  - Nama produk.
  - Jumlah terjual.
  - Total penjualan.
- Pertahankan pagination yang sudah ada:
  - `$topCustomers->links()`
  - `$recentOrders->links()`
  - `$topProducts->links()`
- Tambahkan empty state yang lebih rapi pada list kosong.

### 2. Rapikan List pada GM Report
File: `resources/views/gm/reports/index.blade.php`

Rencana perubahan:
- Ubah container laporan dari blok pink polos menjadi card putih dengan border, header, toolbar filter, dan footer pagination.
- Ganti header `#` menjadi `No.`
- Rapikan filter:
  - Date start/end.
  - Status pesanan.
  - Status pembayaran.
  - Tombol filter.
- Rapikan tabel `LAPORAN ORDER`:
  - No.
  - Pesanan.
  - Pelanggan.
  - Item.
  - Subtotal.
  - Diskon.
  - Total.
  - Pembayaran.
  - Status Pesanan.
  - Tanggal.
- Gunakan badge status rounded seperti list admin/marketing.
- Empty state dibuat lebih informatif.
- Tombol download Excel tetap ada dan tidak mengubah endpoint export.

### 3. Rapikan List pada Direktur Dashboard
File: `resources/views/direktur/dashboard.blade.php`

Rencana perubahan:
- Rapikan section `Top Pelanggan` agar konsisten dengan GM.
- Rapikan section `Pesanan Terbaru` agar tidak ada nested table yang membingungkan dan tampil sebagai tabel/card bersih.
- Rapikan section `Top Produk`:
  - Nama produk.
  - Quantity.
  - Total sales.
  - Progress/indikator tetap boleh dipertahankan jika tidak membuat layout ramai.
- Rapikan section `Promosi Aktif`:
  - Nama promosi.
  - Kode.
  - Periode.
  - Nilai diskon.
  - Pembuat.
- Pertahankan pagination untuk semua list paginated.
- Empty state dibuat seragam.

### 4. Rapikan List pada Direktur Strategic Report
File: `resources/views/direktur/reports/strategic.blade.php`

Rencana perubahan:
- Rapikan container `DATA PESANAN` menjadi card/table putih dengan border.
- Ganti header `#` menjadi `No.`
- Rapikan filter tanggal dan status.
- Rapikan tabel order:
  - No.
  - Pesanan.
  - Pelanggan.
  - Item.
  - Total.
  - Pembayaran.
  - Status.
  - Tanggal.
- Rapikan sidebar:
  - `Promosi` tetap sebagai ringkasan status.
  - `Produk Terlaris` dibuat menjadi list kompak yang seragam.
  - `Status Pesanan` jika ada tetap dipertahankan, namun diberi spacing dan badge/progress yang lebih rapi.

### 5. Konsistensi Label dan Bahasa
Rencana perubahan:
- Semua simbol `#` di view GM/Direktur web diganti `No.`
- Label campuran Inggris dirapikan jika mengganggu:
  - `Date` menjadi `Tanggal`.
  - `Discount` menjadi `Diskon`.
  - `Total Amount` menjadi `Total Penjualan` atau `Total Amount` disesuaikan konteks.
  - `DOWNLOAD EXCEL` boleh dipertahankan atau diganti `DOWNLOAD EXCEL`; karena tombol export sudah lazim, tidak wajib diubah.
- Status pembayaran memakai label model jika memungkinkan: `status_label`.

## File yang Akan Diubah
- `resources/views/gm/dashboard.blade.php`
- `resources/views/gm/reports/index.blade.php`
- `resources/views/direktur/dashboard.blade.php`
- `resources/views/direktur/reports/strategic.blade.php`

## File yang Tidak Akan Diubah
- `resources/views/gm/reports/excel.blade.php`
- Route GM/Direktur.
- Controller GM/Direktur, kecuali ditemukan kebutuhan minor yang aman dan tidak mengubah logic bisnis.
- Struktur database dan migration.

## File Test yang Akan Ditambahkan/Diubah
- `tests/Feature/GM/GmListUiTest.php`
- `tests/Feature/Direktur/DirekturListUiTest.php`

Rencana test GM:
- GM dashboard dapat render dan menampilkan section list utama.
- GM report dapat render dan menampilkan `No.`.
- GM report tidak lagi menampilkan header `#`.
- Data order, pelanggan, pembayaran, dan status tetap muncul.

Rencana test Direktur:
- Direktur dashboard dapat render dan menampilkan section list utama.
- Direktur strategic report dapat render dan menampilkan `No.`.
- Strategic report tidak lagi menampilkan header `#`.
- Data order, top produk, promosi/status tetap muncul.

## Batasan Implementasi
- Tidak mengubah perhitungan revenue, summary, filter, pagination, atau query laporan.
- Tidak mengubah permission role GM/Direktur.
- Tidak mengubah export Excel GM.
- Tidak mengubah struktur database.
- Fokus pada Blade UI dan test regresi tampilan.

## Validasi Setelah Implementasi
Perintah yang akan dijalankan:
- `php artisan test --filter=GmListUiTest`
- `php artisan test --filter=DirekturListUiTest`
- `php artisan test --filter=GM`
- `php artisan test --filter=Direktur`
- `npm run build`

Jika server lokal tersedia, lakukan pengecekan manual pada:
- `/gm/dashboard`
- `/gm/reports`
- `/direktur/dashboard`
- `/direktur/strategic-reports`
