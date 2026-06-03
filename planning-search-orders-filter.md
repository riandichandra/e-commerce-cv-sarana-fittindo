**Rencana Implementasi: Search & Filter pada `route/admin/orders`**

Ringkasan
- Tujuan: Tambahkan pencarian pada halaman admin orders berdasarkan `order_number` dan `customer name`, serta filter berdasarkan `status` order.
- Dampak: Perubahan minimal pada controller, view, dan penambahan validasi; rekomendasi indeks DB untuk performa.

Asumsi
- Model `Order` berelasi ke `User` (customer) melalui `user_id`.
- Route admin orders ada di `routes/web.php` atau group `admin` dan saat ini menggunakan controller `Admin\OrderController@index`.
- Tabel `orders` memiliki kolom `order_number`, `status`, `user_id`, `created_at`.

Persyaratan Acceptance Criteria
- Admin dapat memasukkan kata kunci pencarian yang mencocokkan `order_number` atau `user.name`.
- Admin dapat memilih satu `status` untuk memfilter daftar (mis. `completed`, `pending_payment`, `cancelled`, dsb.).
- Kombinasi search + status berlaku bersama-sama (AND logic).
- Pagination mempertahankan query string (q, status).
- Hasil ter-optimasi dan tidak menimbulkan N+1 queries.

Database & Indexing (rekomendasi)
- Pastikan ada index pada `orders.order_number` (jika belum ada):
  - Migration singkat (opsional): `Schema::table('orders', fn (Blueprint $t) => $t->index('order_number'))`.
- `users.name` sulit di-index untuk pencarian partial; gunakan pencarian LIKE pada join atau pertimbangkan full-text search di masa depan.

Desain API / Controller
1. Terima query params: `q` (string, optional), `status` (string, optional), `page`.
2. Validasi input (panjang maksimal, allowed statuses).
3. Query Eloquent (ringkasan):
   - Mulai dari `Order::with(['user', 'items', 'payment'])` untuk eager loads.
   - Jika `status` diberikan: `->where('status', $status)`.
   - Jika `q` diberikan: join ke users atau gunakan whereHas:
     - `->where(function($q2) use ($keyword) { $q2->where('order_number', 'like', "%$keyword%")
         ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%$keyword%")); })`.
   - Urutkan `->orderBy('created_at', 'desc')`.
   - `->paginate(25)->withQueryString();`

Validasi Request
- Buat `app/Http/Requests/Admin/OrderSearchRequest.php`:
  - `q`: string|max:100
  - `status`: nullable|in:<daftar status dari model atau config>`

Routing
- Pastikan route `GET /admin/orders` menunjuk ke `Admin\OrderController@index`.
- Tidak perlu route baru.

View (Blade)
- File target: kemungkinan `resources/views/admin/orders/index.blade.php`.
- Tambahkan form method `GET` di atas tabel orders dengan:
  - Input text `name="q"` value `request('q')` placeholder `Search order number or customer`.
  - Select `name="status"` dengan opsi `''` (All) + available statuses, selected `request('status')`.
  - Submit button.
- Pastikan pagination links menjaga query string: `{{ $orders->links() }}` sudah pakai `withQueryString()` di controller.

Testing
- Feature tests di `tests/Feature/AdminOrderSearchTest.php`:
  - Test search by exact order_number returns the order.
  - Test search by partial customer name returns related orders.
  - Test filter by status returns only matching statuses.
  - Test combination search+status.

Keamanan & Performance
- Batasi panjang `q` untuk menghindari abuse.
- Escape output di Blade (bawaan Laravel) dan hindari menampilkan input mentah.
- Untuk dataset besar, pertimbangkan menambahkan full-text search atau external search (Algolia/Elastic).

Langkah Implementasi (Teknis, urut)
1. Buat `OrderSearchRequest` di `app/Http/Requests/Admin/OrderSearchRequest.php`.
2. Update `Admin\OrderController@index`:
   - Gunakan request request class.
   - Implement query Eloquent seperti di desain API.
3. Update Blade view `resources/views/admin/orders/index.blade.php`:
   - Tambah form pencarian dan select status.
   - Pastikan value diisi dari `request()`.
4. Tambah (opsional) migration untuk index `order_number` bila belum ada.
5. Tambah tests fitur di `tests/Feature`.
6. Jalankan `php artisan migrate` (jika migration dibuat) dan `php artisan db:seed --class=DummyDataSeeder` untuk data testing, lalu jalankan tests.

Snippet contoh query (untuk developer implementasi)

```php
$orders = Order::with(['user', 'items', 'payment'])
    ->when($request->status, fn($q) => $q->where('status', $request->status))
    ->when($request->q, function($q) use ($request) {
        $keyword = $request->q;
        $q->where(function($sq) use ($keyword) {
            $sq->where('order_number', 'like', "%{$keyword}%")
               ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$keyword}%"));
        });
    })
    ->orderBy('created_at', 'desc')
    ->paginate(25)
    ->withQueryString();
```

Estimasi Waktu
- Implementasi controller + view: 1-2 jam.
- Tests + minor migration: 1 jam.
- QA/manual testing: 30-60 menit.

Rollout
- Implementasikan di branch feature/search-orders.
- Buat pull request, jalankan CI tests.
- Deploy setelah QA.

Catatan Tambahan
- Jika admin ingin search multi-field (email, phone), tambahkan ke `orWhereHas('user',...)` kondisi tambahan.
- Untuk pencarian yang lebih cepat pada `users.name`, pertimbangkan menyimpan `user_name` denormalized di `orders` tabel jika search akan sering dilakukan.

---

File ini dibuat untuk dijadikan pedoman implementasi. Setelah konfirmasi, saya bisa lanjut membuat `OrderSearchRequest`, mengedit controller dan view, serta menambahkan tests dan migration.