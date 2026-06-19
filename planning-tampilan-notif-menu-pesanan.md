# Planning Tampilan Notif Menu Pesanan Admin

Dokumen ini berisi analisis codebase dan rencana implementasi untuk mengubah perilaku klik menu **Pesanan** di sidebar admin. Target perubahan: jika menu Pesanan menampilkan notif angka, maka saat menu tersebut diklik halaman pesanan langsung menampilkan data yang menjadi sumber notif tersebut, bukan seluruh pesanan.

Tahap ini hanya berisi analisis dan planning. Tidak ada perubahan kode aplikasi pada tahap ini.

## 1. Tujuan Perubahan

Kondisi sekarang:

- Sidebar admin menampilkan menu **PESANAN**.
- Menu tersebut memiliki notif angka jika ada pesanan dengan status tertentu.
- Ketika menu **PESANAN** diklik, user diarahkan ke `admin.orders.index` tanpa filter.
- Akibatnya halaman `admin/orders` menampilkan seluruh pesanan, bukan hanya pesanan yang menyebabkan notif muncul.

Kondisi yang diinginkan:

- Jika notif angka Pesanan muncul, klik menu **PESANAN** harus membuka halaman pesanan yang sudah terfilter pada data notif tersebut.
- Jika notif angka tidak muncul, klik menu **PESANAN** tetap membuka seluruh pesanan seperti sekarang.

## 2. Analisis Codebase Terkait

### 2.1 Sumber Notif Angka Menu Pesanan

File terkait:

- `app/Providers/AppServiceProvider.php`
- `resources/views/layouts/admin/navigation.blade.php`

Pada `AppServiceProvider`, terdapat `View::composer` untuk view `layouts.admin.navigation`.

Data yang dikirim ke navigation:

- `paidUnprocessedOrderCount`
- `pendingShippingCount`

Untuk badge menu Pesanan, variabel yang dipakai adalah:

- `paidUnprocessedOrderCount`

Query sumber notif:

- Menghitung jumlah order dengan `status = 'pembayaran_dikonfirmasi'`.

Makna bisnis dari notif ini:

- Ada pesanan yang pembayarannya sudah dikonfirmasi, tetapi belum diproses admin.
- Data ini perlu segera ditindaklanjuti oleh admin, biasanya dengan mengubah status ke `diproses` atau `dikirim`.

### 2.2 Link Menu Pesanan Saat Ini

File terkait:

- `resources/views/layouts/admin/navigation.blade.php`

Saat ini link menu Pesanan memakai route:

- `route('admin.orders.index')`

Route tersebut tidak membawa query string apa pun. Karena itu halaman index menerima request kosong dan menampilkan semua order.

Badge angka hanya ditampilkan jika:

- `$paidUnprocessedOrderCount > 0`

Namun kondisi badge belum memengaruhi URL menu.

### 2.3 Halaman Index Pesanan

File terkait:

- `app/Http/Controllers/Admin/OrderController.php`
- `app/Http/Requests/Admin/OrderSearchRequest.php`
- `resources/views/admin/orders/index.blade.php`

Controller `Admin\OrderController@index` sudah mendukung filter status:

- Jika request memiliki `status`, query order difilter dengan `where('status', $request->status)`.
- Jika request memiliki `q`, query mencari berdasarkan `order_number` atau nama user.
- Pagination sudah memakai `withQueryString()`, sehingga filter tetap terbawa saat pindah halaman.

Status yang diizinkan pada `OrderSearchRequest` mencakup:

- `menunggu_konfirmasi_ongkir`
- `belum_dibayar`
- `menunggu_verifikasi_pembayaran`
- `pembayaran_dikonfirmasi`
- `diproses`
- `dikirim`
- `selesai`
- `dibatalkan`

Karena `pembayaran_dikonfirmasi` sudah termasuk dalam validasi request, kebutuhan filter notif dapat dipenuhi tanpa menambah status baru.

### 2.4 View Index Pesanan

File terkait:

- `resources/views/admin/orders/index.blade.php`

View sudah memiliki form filter:

- Input `q` untuk pencarian nomor pesanan atau pelanggan.
- Select `status` untuk filter status.
- Tombol `Cari`.
- Tombol `Reset` ke `route('admin.orders.index')`.

Jika URL dibuka dengan query `?status=pembayaran_dikonfirmasi`, select status akan otomatis memilih status tersebut karena memakai:

- `request('status') === $s`

Artinya, dari sisi tampilan daftar pesanan, filter notif sudah kompatibel.

### 2.5 Route Admin Pesanan

File terkait:

- `routes/web.php`

Route pesanan admin menggunakan resource route:

- `Route::resource('orders', Admin\OrderController::class)->only(['index', 'show', 'update']);`

Nama route index:

- `admin.orders.index`

Route ini menerima query string secara normal, sehingga URL berikut valid:

- `/admin/orders?status=pembayaran_dikonfirmasi`

## 3. Kesimpulan Analisis

Perubahan utama tidak perlu menyentuh database, migration, model, atau route baru.

Codebase sudah memiliki hampir semua dukungan yang dibutuhkan:

- Badge notif sudah menghitung order `pembayaran_dikonfirmasi`.
- Halaman pesanan sudah mendukung filter status.
- Request validation sudah mengizinkan `pembayaran_dikonfirmasi`.
- View index sudah mempertahankan filter pada pagination.

Perubahan paling kecil dan tepat adalah mengubah URL menu **PESANAN** di sidebar admin secara kondisional:

- Jika `paidUnprocessedOrderCount > 0`, arahkan ke `admin.orders.index` dengan query `status=pembayaran_dikonfirmasi`.
- Jika `paidUnprocessedOrderCount == 0`, arahkan ke `admin.orders.index` tanpa query.

## 4. Rencana Implementasi

### 4.1 Perubahan Link Menu Pesanan

File yang direncanakan berubah:

- `resources/views/layouts/admin/navigation.blade.php`

Rencana:

- Buat variabel URL menu Pesanan di Blade, misalnya konsep `ordersMenuUrl`.
- Jika `$paidUnprocessedOrderCount > 0`, URL mengarah ke route index pesanan dengan parameter query status:
  - `status = pembayaran_dikonfirmasi`
- Jika tidak ada notif, URL tetap:
  - `route('admin.orders.index')`

Contoh perilaku yang diharapkan:

- Ada 5 notif pesanan: klik menu Pesanan membuka `/admin/orders?status=pembayaran_dikonfirmasi`.
- Tidak ada notif: klik menu Pesanan membuka `/admin/orders`.

### 4.2 Sinkronisasi Badge dan Data yang Ditampilkan

Data badge saat ini dihitung dari:

- `Order::where('status', 'pembayaran_dikonfirmasi')->count()`

Maka filter klik juga harus memakai status yang sama:

- `status=pembayaran_dikonfirmasi`

Ini penting supaya angka badge dan jumlah data pada halaman hasil klik tidak berbeda karena definisi filter yang tidak sinkron.

### 4.3 Tampilan Halaman Saat Filter Notif Aktif

File yang mungkin direncanakan berubah jika ingin UX lebih jelas:

- `resources/views/admin/orders/index.blade.php`

Perubahan ini opsional, tetapi disarankan:

- Tampilkan teks konteks saat filter status aktif, misalnya halaman sedang menampilkan pesanan dengan status tertentu.
- Karena select status sudah otomatis aktif, perubahan ini tidak wajib.

Planning minimal:

- Tidak perlu mengubah tampilan index karena select status sudah menunjukkan filter aktif.

Planning lebih informatif:

- Tambahkan label kecil seperti `Filter aktif: Pembayaran Dikonfirmasi` agar admin langsung paham kenapa daftar yang muncul bukan seluruh pesanan.

### 4.4 Tombol Reset Tetap Menampilkan Semua Pesanan

Tombol reset di halaman pesanan saat ini sudah mengarah ke:

- `route('admin.orders.index')`

Rencana:

- Biarkan tombol reset tetap seperti sekarang.
- Jika admin masuk dari notif dan ingin kembali melihat seluruh pesanan, klik `Reset` akan menghapus filter status.

### 4.5 Active State Sidebar

Active state menu Pesanan saat ini memakai:

- `request()->routeIs('admin.orders.*')`

Rencana:

- Tidak perlu diubah.
- Ketika URL menjadi `/admin/orders?status=pembayaran_dikonfirmasi`, route tetap `admin.orders.index`, sehingga menu Pesanan tetap aktif.

## 5. Alternatif Implementasi

### Alternatif A - Query Status Langsung di Link Sidebar

Ubah link sidebar menjadi kondisional:

- Jika ada notif, route ke `admin.orders.index` dengan query status.
- Jika tidak ada notif, route ke `admin.orders.index` normal.

Kelebihan:

- Perubahan sangat kecil.
- Tidak perlu route/controller baru.
- Memakai filter yang sudah tersedia.
- Risiko rendah.

Kekurangan:

- Definisi notif tetap tersebar: count ada di `AppServiceProvider`, filter link ada di Blade.

Rekomendasi:

- Gunakan alternatif ini untuk implementasi awal karena sesuai kebutuhan dan paling minim risiko.

### Alternatif B - Tambah Parameter Khusus `notif=pesanan`

Tambahkan query khusus, misalnya:

- `/admin/orders?notif=paid_unprocessed`

Controller lalu menerjemahkan parameter tersebut menjadi filter `pembayaran_dikonfirmasi`.

Kelebihan:

- Makna URL lebih eksplisit sebagai tampilan notifikasi.
- Jika nanti notif Pesanan mencakup beberapa status, controller bisa memprosesnya lebih fleksibel.

Kekurangan:

- Perlu update validasi request.
- Perlu tambahan logic controller.
- Lebih besar dari kebutuhan saat ini.

Rekomendasi:

- Tidak dipakai untuk tahap awal kecuali notif Pesanan nanti direncanakan mencakup beberapa kategori sekaligus.

### Alternatif C - Route Khusus Pesanan Notif

Tambah route seperti:

- `admin.orders.notifications`

Route ini menampilkan daftar order yang menjadi sumber notif.

Kelebihan:

- Pemisahan alur sangat jelas.

Kekurangan:

- Perlu route baru.
- Perlu action controller baru atau redirect.
- Lebih kompleks dari kebutuhan sederhana.

Rekomendasi:

- Tidak diperlukan untuk kebutuhan saat ini.

## 6. Rekomendasi Final

Gunakan **Alternatif A**:

- Ubah link menu **PESANAN** di `resources/views/layouts/admin/navigation.blade.php` agar kondisional berdasarkan `$paidUnprocessedOrderCount`.
- Jika count lebih dari 0, arahkan ke route `admin.orders.index` dengan query:
  - `status=pembayaran_dikonfirmasi`
- Jika count 0, arahkan ke route `admin.orders.index` tanpa query.

Alasan:

- Sumber notif sudah jelas: order dengan status `pembayaran_dikonfirmasi`.
- Filter status sudah tersedia dan tervalidasi.
- Tidak perlu perubahan database.
- Tidak perlu migration, model, atau route baru.
- Risiko regresi rendah.

## 7. Risiko dan Hal yang Perlu Diperhatikan

- Jika di masa depan definisi badge berubah, link filter harus ikut diubah agar tetap sinkron.
- Jika badge nantinya menghitung lebih dari satu status, query `status=pembayaran_dikonfirmasi` tidak lagi cukup dan perlu pendekatan parameter khusus atau filter multi-status.
- Jika admin sedang berada di halaman pesanan dengan filter lain lalu klik menu Pesanan, filter akan diganti menjadi filter notif selama badge masih muncul. Ini sesuai kebutuhan saat ini, tetapi perlu dipahami sebagai perilaku baru.
- Jika jumlah badge berubah setelah order diproses, klik berikutnya bisa kembali ke seluruh pesanan ketika count sudah 0.
- Jika ada cache view/config yang aktif di server, perubahan Blade mungkin perlu clear/cache ulang saat deployment.

## 8. Rencana Validasi Setelah Implementasi

Setelah nanti implementasi disetujui, validasi yang perlu dilakukan:

1. Pastikan saat ada order `pembayaran_dikonfirmasi`, badge Pesanan muncul.
2. Klik menu Pesanan dari sidebar admin.
3. Pastikan URL menjadi `/admin/orders?status=pembayaran_dikonfirmasi`.
4. Pastikan daftar hanya menampilkan order dengan status `Pembayaran Dikonfirmasi`.
5. Pastikan select status pada filter otomatis memilih `Pembayaran Dikonfirmasi`.
6. Klik tombol `Reset`.
7. Pastikan URL kembali ke `/admin/orders` dan daftar menampilkan seluruh pesanan.
8. Ubah semua order `pembayaran_dikonfirmasi` menjadi status lain pada data uji.
9. Pastikan badge hilang.
10. Klik menu Pesanan lagi.
11. Pastikan halaman membuka seluruh pesanan tanpa query filter.

## 9. Batasan Tahap Ini

Pada tahap ini belum dilakukan:

- Perubahan Blade.
- Perubahan controller.
- Perubahan request validation.
- Perubahan route.
- Perubahan database.
- Penulisan test.

Implementasi baru boleh dilakukan setelah planning ini disetujui.
