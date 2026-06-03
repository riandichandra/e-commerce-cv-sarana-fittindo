# Top Product Year Filter - Planning Document

## Tujuan
Menambahkan opsi filter tahun pada data `Top Products` di dashboard GM dan Direktur sehingga daftar produk teratas hanya menampilkan data penjualan pada tahun yang dipilih.

## Ringkasan Masalah
Saat ini `Top Products` di kedua dashboard menghitung total quantity / total sales dari tabel `order_items` tanpa filter periode. Akibatnya daftar top product selalu mencakup seluruh data historis.

## Sumber Data dan Kolom Penting
- Model: `App\Models\OrderItem` (relasi `order()` ke `App\Models\Order`)
- Tabel utama: `order_items` (kolom: `product_id`, `product_name`, `quantity`, `subtotal`, `created_at`)
- Rekomendasi sumber tahun: gunakan tanggal pada `orders.created_at` (bukan payments), karena Top Product merepresentasikan penjualan/order.

## Dampak
- Analisis kesalahan periode (misleading): manajemen tidak dapat melihat produk terlaris per tahun
- Perlu kemampuan memilih tahun untuk laporan periodik dan komparasi

## Desain Solusi (High Level)
1. Tambahkan `availableYears` dan `selectedYear` (sudah ada untuk revenue) tetapi gunakan tahun dari `orders` atau `order_items`.
2. Filter query `topProducts` menggunakan kondisi tahun: sertakan hanya `OrderItem` yang berasosiasi dengan `Order` pada tahun yang dipilih.
3. UI: tambahkan dropdown `Tahun` pada bagian Top Products atau gunakan global year control yang sudah ada pada dashboard (reuse `availableYears` dan `selectedYear`).
4. Pengembalian default: jika tidak ada `year` di query string maka gunakan `request('year', now()->year)` atau tahun pertama tersedia.

## Query yang Direkomendasikan
Gunakan Eloquent dengan `whereHas` untuk mengaplikasikan filter pada relasi `order`:

OrderItem::query()
    ->whereHas('order', fn($q) => $q->whereYear('created_at', $selectedYear))
    ->select('product_name')
    ->selectRaw('SUM(quantity) as total_quantity')
    ->selectRaw('SUM(subtotal) as total_sales')
    ->groupBy('product_name')
    ->orderByDesc('total_quantity') // atau total_sales
    ->limit(5)
    ->get();

Alternatif: join ke tabel `orders` dan filter `orders.created_at`.

## UX / View
- Tempatkan dropdown `Tahun` di panel Top Products (atau shared filter di header jika ingin konsisten)
- Submitting dropdown menggunakan `GET` sehingga menghasilkan query string `?year=2025`
- Tampilkan teks meta: `Top products untuk tahun 2025` atau `Tahun terpilih: 2025`.

## File yang Harus Diubah
- `app/Http/Controllers/GM/DashboardController.php` — update query `topProducts` untuk filter `selectedYear` (controller sudah menyediakan `availableYears`/`selectedYear` setelah perubahan revenue)
- `resources/views/gm/dashboard.blade.php` — tambahkan dropdown tahun (atau reuse existing) dan update label
- `app/Http/Controllers/Direktur/DashboardController.php` — update `topProducts` query sama seperti GM
- `resources/views/direktur/dashboard.blade.php` — tambah dropdown tahun untuk top product

## Langkah Implementasi (Detail)
1. Ambil `availableYears` dari `Order::selectRaw('YEAR(created_at) as year')->distinct()->orderByDesc('year')->pluck('year')`.
2. Tentukan `selectedYear = (int) request('year', now()->year)`; fallback ke `availableYears->first()` bila tidak valid.
3. Ubah `topProducts` Eloquent query dengan `whereHas('order', ...)` untuk memfilter berdasarkan `selectedYear`.
4. Kirim `availableYears` dan `selectedYear` ke view (jika belum ada).
5. Pada view, tambahkan select `year` di area Top Products; saat berubah submit form GET.
6. Tampilkan teks meta yang menjelaskan tahun terpilih.

## Testing
- Unit/Feature tests:
  - Buat beberapa `Order` dan `OrderItem` di tahun berbeda, panggil controller dengan `?year=YYYY`, assert topProducts hanya dari tahun tersebut.
  - Test default behavior (tanpa query param) menggunakan `now()->year`.
- Manual testing:
  - Buat sample data di dev DB, buka dashboard, pilih tahun berbeda, verifikasi daftar top product berubah sesuai tahun.

## Edge Cases & Catatan
- Jika tahun yang dipilih tidak memiliki data order, tampilkan pesan "Tidak ada data untuk tahun ini" atau tampilkan fallback (mis. top kosong).
- Jika menggunakan `product_name` (text) untuk groupBy, perhatikan normalisasi nama; idealnya kelompokkan berdasarkan `product_id` untuk akurasi.
- Jika ingin sortable by `total_sales` atau `total_quantity`, tambahkan toggle di UI.

## Timeline Perkiraan
- Implementasi controller + view: 30–60 menit
- Testing & QA: 30 menit
- Total: ~1–1.5 jam

## Kesimpulan
Implementasi ini sederhana dan tidak memerlukan perubahan skema DB. Rekomendasi: gunakan `orders.created_at` sebagai sumber tahun, grup berdasarkan `product_id` bila memungkinkan, dan tampilkan dropdown `Tahun` pada panel Top Products di kedua dashboard.
