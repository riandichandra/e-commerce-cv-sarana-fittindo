**Rencana Implementasi: Search & Filter pada `route/admin/payments`**

Ringkasan
- Tujuan: Tambahkan fitur pencarian pada halaman admin payments berdasarkan `order_number` dan `customer name`, serta filter berdasarkan `payment.status`.
- Dampak: Perubahan pada controller, view, dan penambahan validasi request; rekomendasi indeks DB untuk performa.

Analisis Codebase & Struktur Database (ringkas)
- Model `Payment` ada di `app/Models/Payment.php`. Relasi penting:
  - `payment->order()` (belongsTo) — `orders` memiliki `order_number`, `user_id` dan detail pengiriman.
  - `payment->order->user` — `users` menyimpan `name`, `email`, `phone`.
  - `payment->paymentMethod()` dan `payment->verifiedBy()` juga ada.
- Controller admin saat ini: `app/Http/Controllers/Admin/PaymentController.php`.
  - `index()` saat ini: `Payment::with(['order.user','paymentMethod','verifiedBy'])->latest()->paginate(10)`.
- View target: `resources/views/admin/payments/index.blade.php`.
- Tabel DB relevan: `payments` (order_id, payment_method_id, amount, status, sender_name, transfer_date, proof_image, verified_by), `orders` (order_number, user_id, total_amount, shipping_...); `users` (name).

Asumsi
- `Order` berhubungan ke `User` melalui `user_id`.
- Status payment terdefinisi sebagai `pending`, `verified`, `rejected`.
- Route admin payments adalah `GET /admin/payments` -> `Admin\PaymentController@index`.

Acceptance Criteria
- Admin dapat mencari dengan string yang cocok sebagian (LIKE) pada `payments.order.order_number` atau `payments.order.user.name`.
- Admin dapat memilih satu status pembayaran (All/pending/verified/rejected) untuk memfilter.
- Kombinasi search + status bekerja bersama (AND semantic).
- Pagination mempertahankan query string (`q`, `status`).
- Query tidak menghasilkan N+1 (gunakan eager loading `with`).

Desain API / Controller
1. Terima query params: `q` (string, optional), `status` (string, optional), `page`.
2. Validasi via FormRequest (lihat bagian Validasi).
3. Query Eloquent (saran):
   - Mulai dari `Payment::with(['order.user','paymentMethod','verifiedBy'])`.
   - Jika `status` diberikan: `->where('status', $status)`.
   - Jika `q` diberikan: `->where(function($q2) use ($keyword) { $q2->whereHas('order', fn($o)=> $o->where('order_number','like',"%$keyword%"))
       ->orWhereHas('order.user', fn($u)=> $u->where('name','like',"%$keyword%")); })`.
   - Urutkan `->orderBy('created_at','desc')`.
   - `->paginate(10)->withQueryString()`.

Contoh query snippet:

```php
$payments = Payment::with(['order.user','paymentMethod','verifiedBy'])
    ->when($request->status, fn($q)=> $q->where('status', $request->status))
    ->when($request->q, function($q) use ($request) {
        $kw = $request->q;
        $q->where(function($sub) use ($kw) {
            $sub->whereHas('order', fn($o) => $o->where('order_number','like', "%{$kw}%"))
                ->orWhereHas('order.user', fn($u) => $u->where('name','like', "%{$kw}%"));
        });
    })
    ->orderBy('created_at','desc')
    ->paginate(10)
    ->withQueryString();
```

Validasi Request
- Buat `app/Http/Requests/Admin/PaymentSearchRequest.php`:
  - `q`: nullable|string|max:100
  - `status`: nullable|string|in:pending,verified,rejected
- Gunakan request ini di `PaymentController@index`.

View (Blade)
- File target: `resources/views/admin/payments/index.blade.php`.
- Tambahkan form `GET` di atas tabel:
  - Input text `name="q"` value `request('q')` placeholder `Search order number or customer`.
  - Select `name="status"` opsi All + `pending`,`verified`,`rejected` (selected `request('status')`).
  - Tombol `Search` dan `Reset` (link ke route tanpa query).
- Pastikan pagination sudah menggunakan `withQueryString()` pada controller.

Database & Indexing (rekomendasi)
- Jika `orders.order_number` sering dicari, pastikan ada index di kolom tersebut.
  - Opsi migration singkat: `Schema::table('orders', fn(Blueprint $t) => $t->index('order_number'));`
- `users.name` partial searches tidak mudah diindeks; for large datasets consider full-text search later.

Testing
- Tambahkan feature tests `tests/Feature/AdminPaymentSearchTest.php`:
  - Search by order number (exact & partial).
  - Search by customer name (partial).
  - Filter by status.
  - Combination search + status.
- Use factories/DummyDataSeeder for test data or create test-specific factories.

Keamanan & Performance
- Batasi `q` length to avoid heavy payloads.
- Escape output in Blade (Laravel default).
- For heavy load, offload search to full-text index or external search engine.

Langkah Implementasi (Teknis, urut)
1. Buat `app/Http/Requests/Admin/PaymentSearchRequest.php`.
2. Update `app/Http/Controllers/Admin/PaymentController.php`:
   - Terima `PaymentSearchRequest $request` pada `index()`.
   - Terapkan query filter/search seperti snippet di atas.
   - Kirim `statuses` (['pending','verified','rejected']) ke view.
3. Update view `resources/views/admin/payments/index.blade.php` untuk menambahkan form search & select status.
4. (Optional) Tambah migration index `orders.order_number` jika diperlukan.
5. Tambah feature tests di `tests/Feature`.
6. Manual QA: jalankan seeder, buka `GET /admin/payments?q=...&status=...` dan verifikasi.

Estimasi Waktu
- Implementasi controller + view: 1 jam.
- Tests + migration: 1 jam.
- QA/manual testing: 30 menit.

Rollout
- Implement di branch `feature/search-payments`.
- PR + CI + QA.

Catatan Tambahan
- Jika ingin search juga berdasarkan `sender_name` atau `paymentMethod.name`, tambahkan `orWhere`/`orWhereHas` untuk kolom-kolom tersebut.
- Untuk UX, pertimbangkan menampilkan current filters dan hasil hitungan (total results).

---

File ini dibuat sebagai pedoman implementasi. Setelah konfirmasi, saya bisa lanjut membuat `PaymentSearchRequest`, mengubah `PaymentController@index`, dan menambahkan form di view serta tests dan migration jika diinginkan.