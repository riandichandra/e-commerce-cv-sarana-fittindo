# Planning Implementasi Tampilan List Marketing

## Tujuan
Merapikan semua tampilan list yang ada di menu marketing agar konsisten dengan gaya list admin yang sudah dibuat sebelumnya: tabel lebih bersih, mudah dipindai, label nomor memakai `No.`, action jelas, status lebih informatif, dan tampilan tetap enak dilihat untuk kebutuhan operasional marketing.

## Analisis Codebase

### Route Marketing
File: `routes/web.php`

Route marketing saat ini berada pada group:
- Prefix: `/marketing`
- Name: `marketing.`
- Middleware: `auth`, `role:marketing`

Route yang berhubungan dengan list:
- `marketing.dashboard` melalui `Marketing\DashboardController@index`
- `marketing.promotions.index` melalui `Marketing\PromotionController@index`
- `marketing.users.index` melalui `Marketing\UserController@index`
- `marketing.settings.index` tidak memiliki list data utama, hanya halaman pengaturan profil.

### Controller Marketing
File terkait:
- `app/Http/Controllers/Marketing/DashboardController.php`
- `app/Http/Controllers/Marketing/PromotionController.php`
- `app/Http/Controllers/Marketing/UserController.php`

Data list yang dikirim controller:
- Dashboard:
  - `$recentPromotions`
  - `$recentCustomers`
  - `$monthlyCustomers`
- Promosi:
  - `$promotions` dengan relasi `createdBy`, pagination 10 data.
- Pengguna/Pelanggan:
  - `$customers` dengan filter `search` dan `status`, pagination 10 data.

### View Marketing
File terkait:
- `resources/views/marketing/dashboard.blade.php`
- `resources/views/marketing/promotions/index.blade.php`
- `resources/views/marketing/users/index.blade.php`

Kondisi saat ini:
- `marketing/promotions/index.blade.php` masih memakai tabel sederhana, header `#`, dan tombol `ADD PROMOTION`/`EDIT` dengan gaya lama.
- `marketing/users/index.blade.php` sudah diperbaiki error variabel dari `$pelanggan` ke `$customers`, tetapi tampilan list masih gaya lama dan container belum serapi list admin.
- `marketing/dashboard.blade.php` memiliki list ringkas promosi terbaru dan pelanggan terbaru yang perlu diseragamkan secara visual tanpa mengubah fungsi dashboard.

## Analisis Struktur Database

### Tabel `promotions`
Migration: `database/migrations/2026_04_30_141343_create_promotions_table.php`

Kolom utama:
- `id`
- `name`
- `code`
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
- `created_at`
- `updated_at`

Relasi model:
- `Promotion::createdBy()` ke `User`
- `Promotion::orders()`
- `Promotion::products()`

Kolom yang relevan untuk list marketing:
- Nama promosi, kode, deskripsi singkat.
- Tipe dan nilai diskon.
- Minimum pembelian dan maksimum diskon.
- Periode mulai-selesai.
- Status: berjalan, terjadwal, berakhir, nonaktif.
- Pembuat promosi.
- Aksi edit.

### Tabel `users`
Migration: `database/migrations/0001_01_01_000000_create_users_table.php`

Kolom utama:
- `id`
- `name`
- `email`
- `email_verified_at`
- `phone`
- `is_active`
- `created_at`
- `updated_at`

Role memakai Spatie Permission, sehingga pelanggan didapat dari relasi role dengan nama `pelanggan`.

Kolom yang relevan untuk list marketing:
- Nama pelanggan.
- Email.
- Nomor telepon.
- Status aktif/nonaktif.
- Status verifikasi email.
- Tanggal terdaftar.

## Scope Implementasi

### 1. Rapikan List Promosi Marketing
File: `resources/views/marketing/promotions/index.blade.php`

Rencana perubahan:
- Ubah layout header menjadi lebih rapi dengan judul, ringkasan total promosi, dan tombol tambah promosi.
- Ganti label `#` menjadi `No.`.
- Gunakan container tabel putih dengan border, shadow ringan, dan header tabel abu-abu muda.
- Tampilkan informasi promosi dalam format yang lebih mudah dipindai:
  - Kolom `No.`
  - Kolom `Promosi`: nama, kode, deskripsi singkat, link banner jika ada.
  - Kolom `Tipe & Nilai`: percent/nominal, nilai diskon, maksimum diskon jika ada.
  - Kolom `Periode`: tanggal mulai dan selesai.
  - Kolom `Syarat`: minimum pembelian.
  - Kolom `Status`: badge berjalan/terjadwal/berakhir/nonaktif.
  - Kolom `Aksi`: tombol edit dengan ikon.
- Gunakan perhitungan status berbasis tanggal seperti halaman promosi admin agar promosi yang berakhir hari ini masih dianggap berjalan.
- Tambahkan empty state yang lebih rapi saat belum ada promosi.
- Pagination ditempatkan di footer tabel.

### 2. Rapikan List Pelanggan Marketing
File: `resources/views/marketing/users/index.blade.php`

Rencana perubahan:
- Rapikan area statistik total dan aktif agar konsisten dengan list admin/marketing.
- Rapikan filter search dan status dalam toolbar yang lebih bersih.
- Pastikan label `No.` tetap dipakai.
- Ubah tabel menjadi container putih dengan border dan shadow ringan.
- Tampilkan data pelanggan dalam format yang lebih jelas:
  - Kolom `No.`
  - Kolom `Pelanggan`: nama dan email.
  - Kolom `Telepon`.
  - Kolom `Status Akun`: badge aktif/nonaktif.
  - Kolom `Verifikasi Email`: badge terverifikasi/belum.
  - Kolom `Terdaftar`.
- Tambahkan empty state yang lebih rapi saat filter tidak menemukan pelanggan.
- Pagination ditempatkan di footer tabel.

### 3. Rapikan List Ringkas di Dashboard Marketing
File: `resources/views/marketing/dashboard.blade.php`

Rencana perubahan:
- Rapikan blok `Promosi Terbaru` agar tampil seperti list kompak yang mudah dipindai.
- Rapikan blok `Pelanggan Terbaru` agar konsisten dengan list pelanggan.
- Pertahankan data dan link `Lihat Semua`.
- Tidak mengubah struktur chart/grafik pelanggan kecuali spacing kecil jika dibutuhkan agar list tidak terlihat kacau.

### 4. Konsistensi Bahasa dan Label
Rencana perubahan:
- Semua simbol `#` pada list marketing diganti menjadi `No.`
- Label campuran Inggris yang tidak perlu akan dirapikan:
  - `PROMOTION LISTS` menjadi `LIST PROMOSI`.
  - `CUSTOMER LISTS` menjadi `LIST PELANGGAN`.
  - `ADD PROMOTION` dapat dibuat tetap sebagai tombol aksi atau diganti menjadi `TAMBAH PROMOSI` agar konsisten.
  - `EDIT` dapat diganti menjadi `EDIT` atau `UBAH`; pilihan implementasi: `EDIT` tetap aman karena sudah lazim di UI admin yang ada.
- Typo yang sudah diperbaiki `Daftared` tetap menjadi `Terdaftar`.

## File yang Akan Diubah
- `resources/views/marketing/promotions/index.blade.php`
- `resources/views/marketing/users/index.blade.php`
- `resources/views/marketing/dashboard.blade.php`

## File Test yang Akan Ditambahkan/Diubah
- `tests/Feature/Marketing/MarketingListUiTest.php`

Rencana test:
- Marketing dapat membuka halaman list promosi.
- Halaman list promosi menampilkan `No.`, data promosi, status, dan tombol edit.
- Halaman list promosi tidak lagi menampilkan header `#`.
- Marketing dapat membuka halaman list pelanggan.
- Halaman list pelanggan menampilkan `No.`, data pelanggan, status akun, dan status verifikasi email.
- Dashboard marketing tetap render dan menampilkan list ringkas promosi/pelanggan.

## Batasan Implementasi
- Tidak mengubah logic CRUD promosi.
- Tidak mengubah route atau permission marketing.
- Tidak mengubah struktur database.
- Tidak menghapus fitur tambah/edit promosi milik marketing.
- Fokus hanya pada tampilan list dan konsistensi label.

## Validasi Setelah Implementasi
Perintah yang akan dijalankan:
- `php artisan test --filter=Marketing`
- `php artisan test --filter=MarketingListUiTest`
- `npm run build`

Jika server lokal tersedia, lakukan pengecekan manual pada:
- `/marketing/promotions`
- `/marketing/users`
- `/marketing/dashboard`
