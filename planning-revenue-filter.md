# Revenue Filter Planning

## Tujuan
Membuat filter pilihan tahun untuk `Total Revenue` dan filter pilihan bulan untuk `Monthly Revenue` di dashboard GM dan Direktur.

## Masalah yang Ditemukan
- `totalRevenue` di `GM\DashboardController` dan `Direktur\DashboardController` saat ini menghitung:
  - `Payment::where('status', 'verified')->sum('amount')`
  - Ini menampilkan semua data verified tanpa filter tahun.
- `monthlyRevenue` di kedua dashboard saat ini menghitung:
  - `Payment::where('status', 'verified')->whereYear('verified_at', now()->year)->whereMonth('verified_at', now()->month)->sum('amount')`
  - Ini hanya menampilkan bulan berjalan pada tahun berjalan, tanpa pilihan bulan yang dapat dipilih berdasarkan tahun terpilih.

## Sumber Data
- Model: `App\Models\Payment`
- Tabel: `payments`
- Kolom penting:
  - `amount`
  - `status`
  - `verified_at`
- Kondisi filter yang sudah benar: hanya `status = 'verified'`

## Scope Implementasi
- Tambahkan filter `year` untuk `totalRevenue`
- Tambahkan filter `month` untuk `monthlyRevenue` berdasarkan `year` terpilih
- Terapkan di:
  - `app/Http/Controllers/GM/DashboardController.php`
  - `app/Http/Controllers/Direktur/DashboardController.php`
  - `resources/views/gm/dashboard.blade.php`
  - `resources/views/direktur/dashboard.blade.php`
- Tidak perlu migrasi database baru.

## Desain Solusi

### 1. Input filter di URL / query string
Gunakan query parameters seperti:
- `?year=2025`
- `?year=2025&month=3`

### 2. Controller
Tambahkan logika berikut di masing-masing `index()`:
- Ambil list tahun yang tersedia dari `Payment::where('status','verified')->selectRaw('YEAR(verified_at) as year')->distinct()->orderByDesc('year')->pluck('year')`
- Tentukan `selectedYear` dari request (default `now()->year` atau tahun pertama tersedia)
- Tentukan `selectedMonth` dari request (default `now()->month` ketika tahun terpilih sama dengan tahun sekarang, atau default `1` / latest month pada tahun terpilih)
- Hitung `totalRevenue` dengan `whereYear('verified_at', $selectedYear)`
- Hitung `monthlyRevenue` dengan `whereYear('verified_at', $selectedYear)->whereMonth('verified_at', $selectedMonth)`
- Buat list bulan yang tersedia untuk `selectedYear` dari dataset verified payment, atau gunakan hardcoded 1..12 jika lebih sederhana

### 3. View
Pada dashboard GM dan Direktur:
- Ubah kartu `Total Revenue` menjadi memiliki dropdown/select tahun
- Ubah kartu `Monthly Revenue` menjadi memiliki dropdown/select bulan yang aktif setelah memilih tahun
- Tampilkan label meta yang sesuai, misalnya:
  - `Tahun terpilih: 2025`
  - `Bulan terpilih: Maret 2025`
- Gunakan method `GET` agar filter menggunakan query string sehingga page refresh memproses ulang data.

### 4. Interaksi antar filter
- Jika `year` berubah, `month` bisa di-reset ke default bulan pertama atau bulan terbaru di tahun itu.
- Jika `month` berubah, hitung ulang `monthlyRevenue` untuk `selectedYear`.
- Jika `month` tidak dipilih, gunakan nilai default yang logis:
  - bulan berjalan jika `selectedYear == now()->year`
  - bulan terakhir dengan data jika `selectedYear` bukan tahun sekarang

## Rencana Implementasi

### Langkah 1: Analisis dan persiapan data
- Baca struktur `payments` dan `verified_at` ada.
- Pastikan `status = 'verified'` tetap sebagai baseline filter.

### Langkah 2: Modifikasi Controller
Untuk masing-masing controller:
1. Ambil list tahun verified:
   - `Payment::where('status', 'verified')->whereNotNull('verified_at')->selectRaw('YEAR(verified_at) as year')->distinct()->orderByDesc('year')->pluck('year')`
2. Ambil `selectedYear` dari request.
3. Ambil `availableMonths` untuk `selectedYear` jika ingin lebih tepat.
4. Hitung `totalRevenue` berdasarkan tahun terpilih.
5. Hitung `monthlyRevenue` berdasarkan tahun + bulan terpilih.
6. Kirim variabel baru ke view:
   - `availableYears`
   - `selectedYear`
   - `availableMonths`
   - `selectedMonth`

### Langkah 3: Update View
- Tambahkan form `GET` di bagian summary card:
  - select `year` untuk total revenue
  - select `month` untuk monthly revenue
- Pastikan page menggunakan route dashboard yang sama, misalnya `route('gm.dashboard')` atau `route('direktur.dashboard')`
- Tampilkan nilai `Rp` sesuai terpilih dan meta bulan/tahun.

### Langkah 4: Uji Coba Internal
- Cek `year` dropdown muncul berdasarkan data tersimpan.
- Pilih tahun berbeda, lalu validasi `Total Revenue` berubah.
- Pilih bulan berbeda dalam tahun terpilih, lalu validasi `Monthly Revenue` berubah.
- Pastikan filter tidak mempengaruhi data summary card lain secara salah.

### Langkah 5: Testing
- Buat feature test untuk controller GM/Direktur:
  - `selectedYear` menghasilkan total hanya tahun tersebut
  - `selectedMonth` menghasilkan monthly data untuk tahun+bulan
- Buat unit test query filter jika perlu.
- Jalankan manual test di browser untuk UX dropdown.

## Detail Teknis

### A. Default values
- `selectedYear` default: `request('year', now()->year)` atau tahun pertama tersedia jika tidak ada data pada tahun sekarang.
- `selectedMonth` default:
  - `request('month', now()->month)` ketika tahun sekarang
  - `request('month', $latestMonthInSelectedYear)` jika tahun berbeda

### B. Opsi bulan
- Bisa gunakan array statis 1..12 dengan label `['01' => 'Jan', ..., '12' => 'Des']`
- Atau list bulan berdasar data payment di tahun terpilih untuk menonaktifkan bulan tanpa data

### C. UX Flow
1. User buka dashboard GM/Direktur
2. User memilih `Tahun` pada card Total Revenue
3. Sistem reload dengan query `?year=2024`
4. User memilih `Bulan` pada card Monthly Revenue
5. Sistem reload dengan query `?year=2024&month=5`

### D. Scope tambahan yang bisa dipertimbangkan
- Tambahkan `availableYears` di shared helper atau service jika kedua dashboard menggunakan logika serupa.
- Tambahkan badge/label `Filter aktif` untuk memperjelas tahun/bulan terpilih.
- Jika ingin UX lebih baik, implementasikan AJAX pada dropdown bulan sebagai enhancement.

## File yang Harus Diubah
- `app/Http/Controllers/GM/DashboardController.php`
- `resources/views/gm/dashboard.blade.php`
- `app/Http/Controllers/Direktur/DashboardController.php`
- `resources/views/direktur/dashboard.blade.php`

## Catatan Database
- Tidak perlu perubahan schema.
- Basis filter sudah pada kolom `verified_at`.
- Pastikan data `verified_at` terisi hanya untuk `status = 'verified'`.

## Kesimpulan
Perubahan ini akan membuat:
- `Total Revenue` tampil hanya dari tahun yang dipilih
- `Monthly Revenue` tampil hanya dari bulan yang dipilih pada tahun tersebut
- Dashboard GM dan Direktur menjadi lebih fleksibel dan relevan untuk analisis periodik
