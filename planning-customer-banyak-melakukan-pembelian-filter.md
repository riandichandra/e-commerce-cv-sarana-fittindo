**Rencana Implementasi: Customer dengan Pembelian Terbanyak + Filter Tahun/Bulan di Dashboard GM dan Direktur**

Ringkasan
- Tujuan: Tampilkan customer yang paling banyak melakukan pembelian di dashboard GM dan Direktur.
- Tambahkan filter `year` dan `month` pada halaman dashboard tersebut.
- Output: data top customer berdasarkan jumlah order dan/atau nilai order, responsive terhadap pilihan tahun dan bulan.

Analisis Codebase & Struktur Database
- Dashboard GM berada di `app/Http/Controllers/GM/DashboardController.php`.
- Dashboard Direktur berada di `app/Http/Controllers/Direktur/DashboardController.php`.
- Keduanya sudah menampilkan revenue berdasarkan `Payment` yang terverifikasi, sehingga model `Payment` dipakai untuk filter tahun/bulan.
- Relasi penting:
  - `Order` model memiliki field `user_id`, `order_number`, `status`, `total_amount`, `created_at`.
  - `Payment` model berelasi dengan `order` dan `order->user`.
  - `User` model tidak mendefinisikan relasi `orders`, tetapi `Order` punya `user()`.
- Struktur data relevan:
  - `orders` tabel menyediakan `user_id`, `created_at`, `total_amount`, `status`.
  - `users` tabel menyediakan `name`.
  - `payments` tabel menyediakan `verified_at` dan `status`.

Asumsi
- Customer dihitung dari `orders.user_id` yang berperan sebagai pelanggan.
- Top customer dapat dihitung menggunakan jumlah order (`order_count`) dan/atau total pembelian (`total_spent`).
- Filter tahun/bulan sebaiknya menggunakan `orders.created_at` untuk memetakan aktivitas customer, bukan hanya pembayaran terverifikasi.

Acceptance Criteria
- GM dan Direktur melihat sekumpulan top customer pada dashboard masing-masing.
- Data ditampilkan untuk `year` dan `month` terpilih.
- Pilihan default mengikuti `availableYears` dan `availableMonths` yang sudah ada pada dashboard.
- Hasil tetap tersedia walau tidak ada transaksi pada bulan tertentu.
- UI filter menggunakan form GET yang mempertahankan query `year` dan `month`.

Desain Data & Query
1. Sumber data: `Order` dengan eager load `user`.
2. Hitungan:
   - `order_count`: jumlah order per customer.
   - `total_spent`: total `total_amount` atau `subtotal + shipping - discount` per customer.
3. Filter waktu:
   - `whereYear('created_at', $selectedYear)`.
   - `whereMonth('created_at', $selectedMonth)`.
4. Urutan:
   - Pertama berdasarkan `order_count` desc.
   - Kedua berdasarkan `total_spent` desc.
5. Limitan: `limit(5)` atau `limit(10)` di dashboard.

Query Contoh (Eloquent / DB):

```php
$topCustomers = Order::query()
    ->when($selectedYear, fn($q) => $q->whereYear('created_at', $selectedYear))
    ->when($selectedMonth, fn($q) => $q->whereMonth('created_at', $selectedMonth))
    ->select('user_id')
    ->selectRaw('COUNT(*) as order_count')
    ->selectRaw('SUM(total_amount) as total_spent')
    ->groupBy('user_id')
    ->orderByDesc('order_count')
    ->orderByDesc('total_spent')
    ->limit(5)
    ->with('user')
    ->get();
```

Alternatif dengan `Payment` jika ingin bayar terverifikasi saja:

```php
$topCustomers = Payment::query()
    ->where('status', 'verified')
    ->whereYear('verified_at', $selectedYear)
    ->whereMonth('verified_at', $selectedMonth)
    ->whereHas('order.user')
    ->selectRaw('orders.user_id')
    ->selectRaw('COUNT(*) as verified_payment_count')
    ->selectRaw('SUM(amount) as total_paid')
    ->join('orders', 'payments.order_id', '=', 'orders.id')
    ->groupBy('orders.user_id')
    ->orderByDesc('verified_payment_count')
    ->limit(5)
    ->get();
```

Rekomendasi: Pakai `Order` karena lebih tepat untuk customer purchase frequency, kemudian gunakan `Payment` di laporan revenue jika diperlukan.

Controller
- Ubah `GM\DashboardController@index()` dan `Direktur\DashboardController@index()`:
  - Tambahkan `$topCustomers` query di atas.
  - Pastikan filter `selectedYear` dan `selectedMonth` dipakai untuk query.
  - Tambahkan `topCustomers` ke `compact()`.

View
- Tambahkan panel baru di `resources/views/gm/dashboard.blade.php` dan `resources/views/direktur/dashboard.blade.php`.
- Panel isi:
  - Nama customer
  - Jumlah order (`order_count`)
  - Total pembelian (`total_spent`)
  - Rata-rata order (opsional)
- Letakkan dekat chart revenue atau table `Recent Orders`.
- Gunakan bahasa yang konsisten: "Top Customers", "Pembelian Terbanyak", "Order Count", "Total Belanja".

UX Filter
- Dashboard sudah punya filter `year` dan `month` pada revenue cards.
- Pastikan form GET yang sama juga mempengaruhi `topCustomers`.
- Jika perlu, tambahkan teks keterangan: "Top Customer untuk {{ monthName }} {{ selectedYear }}".

Performance
- Pastikan query menggunakan `groupBy('user_id')` dan hanya memuat `user` yang diperlukan.
- Gunakan `pluck` atau eager load `user` untuk menghindari N+1.
- Jika dataset besar, index pada `orders.created_at` dan `orders.user_id` membantu.

Testing
- Tambahkan unit/feature test yang memverifikasi:
  - `topCustomers` muncul di view.
  - Filter `year` dan `month` memberi hasil yang sesuai.
  - Order with different users dihitung benar.
- Jalankan test dengan data seeder yang memiliki beberapa order per user.

Langkah Implementasi
1. Periksa ketersediaan `user()` relation di `Order` model dan `order` relation di `Payment` jika diperlukan.
2. Tambahkan query `topCustomers` di kedua dashboard controller.
3. Update `gm.dashboard` dan `direktur.dashboard` untuk menampilkan panel baru.
4. Pastikan filter parameter `year` dan `month` tetap terikut di URL form.
5. Tambah test atau validasi manual dengan data sample.

Estimasi Waktu
- Analisis & controller update: 30-45 menit.
- View update + QA: 30 menit.
- Test & fine tuning: 30 menit.

Catatan Tambahan
- Jika ingin statistik yang lebih lengkap, tambahkan kolom `average_order_value` dan `last_order_date`.
- Untuk GM mungkin lebih cocok menampilkan `order_count`, sedangkan Direktur bisa fokus pada `total_spent`.

---

File ini dibuat sebagai pedoman implementasi untuk fitur top customer dengan filter tahun dan bulan di dashboard GM dan Direktur.