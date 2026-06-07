# Planning Implementasi Perbaikan Tampilan Semua List Menu Admin

## Ringkasan Permintaan

Tampilan semua list yang ada pada menu admin perlu dibuat lebih rapi, konsisten, dan enak dilihat. Selain itu, semua simbol `#` yang dipakai sebagai label nomor tabel perlu diganti menjadi `No.`

Target utama:

- Semua halaman list di menu admin memiliki gaya tabel/card yang lebih konsisten.
- Header nomor tabel memakai `No.`, bukan `#`.
- Tampilan lebih mudah dipindai oleh admin.
- Tidak mengubah struktur database.
- Tidak mengubah flow bisnis seperti update status pesanan, verifikasi pembayaran, input ongkir, create/edit data.

## Ringkasan Analisis Codebase

Area admin memakai Laravel Blade dengan layout:

- Layout: `resources/views/layouts/admin/layout.blade.php`
- Navigasi: `resources/views/layouts/admin/navigation.blade.php`
- Controller admin: `app/Http/Controllers/Admin/*`
- View admin: `resources/views/admin/*`

Menu admin pada sidebar saat ini:

- Dasbor: `admin.dashboard`
- Produk: `admin.products.index`
- Pesanan: `admin.orders.index`
- Ongkir Manual: `admin.pending-shipping-costs.index`
- Pembayaran: `admin.payments.index`
- Metode Pembayaran: `admin.payment-methods.index`
- Promosi: ada item sidebar, tetapi saat ini tidak berupa link route admin yang aktif
- Pengguna: `admin.users.index`
- Pengaturan: `admin.settings.index`

List yang relevan untuk scope ini:

1. Dashboard admin:
   - Recent orders
   - Recent payments
2. Produk:
   - List produk
   - List kategori
   - List merek
3. Pesanan:
   - List pesanan
   - Detail pesanan, bagian order items
4. Ongkir manual:
   - List pesanan menunggu input ongkir
5. Pembayaran:
   - List pembayaran
6. Metode pembayaran:
   - List rekening/metode pembayaran
7. Pengguna:
   - List pengguna

Catatan:

- Halaman settings admin berisi form, bukan list tabel utama.
- Menu promosi di sidebar admin saat ini belum mengarah ke route admin; promosi dikelola pada area marketing.

## Struktur Database Terkait

### `users`

Migration:

- `database/migrations/0001_01_01_000000_create_users_table.php`

Kolom penting:

- `id`
- `name`
- `email`
- `email_verified_at`
- `password`
- `phone`
- `is_active`
- `created_at`
- `updated_at`

Relasi:

- User memakai Spatie roles.
- `User hasMany Order`
- `User hasMany Wishlist`
- `User hasOne Cart`

List terkait:

- `resources/views/admin/users/index.blade.php`

### `roles` dan tabel permission Spatie

Migration:

- `database/migrations/2026_05_13_063659_create_permission_tables.php`

Dipakai pada:

- Filter role halaman pengguna.
- Badge role di list pengguna.

### `products`

Migration:

- `database/migrations/2026_04_30_141259_create_products_table.php`
- `database/migrations/2026_06_02_000000_update_products_stock_and_status.php`

Kolom penting:

- `id`
- `category_id`
- `brand_id`
- `name`
- `slug`
- `description`
- `price`
- `stock`
- `status`
- `weight`
- `thickness`
- `dimensions`
- `is_featured`
- `is_active`
- `deleted_at`

Relasi:

- `Product belongsTo ProductCategory`
- `Product belongsTo ProductBrand`
- `Product hasMany ProductImage`

List terkait:

- `resources/views/admin/products/index.blade.php`

Catatan:

- Revisi terakhir: list produk tidak perlu menampilkan gambar.
- Controller list produk cukup memakai `with(['category', 'brand'])`.

### `product_categories`

Migration:

- `database/migrations/2026_04_30_141255_create_product_categories_table.php`

Kolom penting:

- `id`
- `name`
- `slug`
- `description`
- `is_active`

List terkait:

- `resources/views/admin/categories/index.blade.php`

### `product_brands`

Migration:

- `database/migrations/2026_04_30_141257_create_product_brands_table.php`

Kolom penting:

- `id`
- `name`
- `slug`
- `description`
- `logo`
- `is_active`

List terkait:

- `resources/views/admin/brands/index.blade.php`

### `orders`

Migration:

- `database/migrations/2026_04_30_141321_create_orders_table.php`
- `database/migrations/2026_06_04_010000_add_shipping_cost_confirmation_to_orders_table.php`
- `database/migrations/2026_06_05_000000_add_promotion_snapshot_to_orders_table.php`

Kolom penting:

- `id`
- `user_id`
- `order_number`
- `status`
- `subtotal`
- `discount_amount`
- `shipping_cost`
- `shipping_cost_status`
- `shipping_cost_confirmed_at`
- `shipping_cost_confirmed_by`
- `total_amount`
- `payment_method_id`
- data alamat pengiriman denormalized
- `cancelled_by`
- `cancellation_reason`
- `cancelled_at`
- `received_image`
- snapshot promosi
- `created_at`

Status order:

- `menunggu_konfirmasi_ongkir`
- `belum_dibayar`
- `menunggu_verifikasi_pembayaran`
- `pembayaran_dikonfirmasi`
- `diproses`
- `dikirim`
- `selesai`
- `dibatalkan`

List terkait:

- `resources/views/admin/orders/index.blade.php`
- `resources/views/admin/orders/show.blade.php`
- `resources/views/admin/pending-shipping-costs/index.blade.php`
- `resources/views/admin/dashboard.blade.php`

### `order_items`

Migration:

- `database/migrations/2026_04_30_141322_create_order_items_table.php`

Kolom penting:

- `order_id`
- `product_id`
- `product_name`
- `product_price`
- `quantity`
- `subtotal`

List terkait:

- Tabel order items pada `resources/views/admin/orders/show.blade.php`

### `payments`

Migration:

- `database/migrations/2026_04_30_141324_create_payments_table.php`

Kolom penting:

- `order_id`
- `payment_method_id`
- `amount`
- `proof_image`
- `transfer_date`
- `sender_name`
- `status`
- `verified_by`
- `verified_at`
- `rejection_reason`
- `notes`

Status payment:

- `menunggu`
- `terverifikasi`
- `ditolak`

List terkait:

- `resources/views/admin/payments/index.blade.php`
- `resources/views/admin/dashboard.blade.php`

### `payment_methods`

Migration:

- `database/migrations/2026_04_30_141319_create_payment_methods_table.php`

Kolom penting:

- `name`
- `code`
- `account_number`
- `account_name`
- `bank_name`
- `instructions`
- `icon`
- `is_active`
- `sort_order`

List terkait:

- `resources/views/admin/payment-methods/index.blade.php`

## Temuan Simbol `#`

Simbol `#` sebagai header nomor tabel ditemukan pada:

- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/pending-shipping-costs/index.blade.php`
- `resources/views/admin/payments/index.blade.php`
- `resources/views/admin/payment-methods/index.blade.php`
- `resources/views/admin/orders/show.blade.php`
- `resources/views/admin/orders/index.blade.php`
- `resources/views/admin/categories/index.blade.php`
- `resources/views/admin/brands/index.blade.php`

List produk sudah direvisi sebelumnya dan memakai:

- `resources/views/admin/products/index.blade.php` dengan header `No.`

Catatan penting:

- Jangan mengganti simbol `#` yang merupakan bagian dari warna Tailwind arbitrary value, misalnya `bg-[#FFF1F3]`, `bg-[#fff7f8]`, `text-[#...]`.
- Jangan mengganti simbol `#` pada data test seperti `"Item #{$i}"`, karena itu bukan header tabel.
- Fokus penggantian adalah teks header nomor tabel dari `#` menjadi `No.`

## Kondisi Tampilan Saat Ini

Pola lama pada banyak list:

- Container dominan `bg-[#FFF1F3] p-5 w-full`
- Tabel `mt-3 w-full`
- Header tabel text kecil abu-abu dan border bawah
- Row polos dengan border bawah
- Tombol aksi text seperti `EDIT`, `DETAIL`, `Verify`, `Reject`
- Empty state hanya teks sederhana
- Beberapa form filter masih menempel langsung di atas tabel
- Beberapa tabel sangat lebar tetapi belum punya `min-w` yang konsisten

Masalah utama:

1. Tampilan antar-list belum konsisten.
2. Header nomor masih `#` di banyak halaman.
3. Visual hierarchy data penting belum kuat.
4. Badge status sudah ada, tetapi gaya dan warna belum konsisten antar halaman.
5. Empty state masih terasa mentah.
6. Tabel pembayaran dan pesanan cukup lebar sehingga perlu lebar minimum dan scroll yang lebih terkontrol.
7. Beberapa action dalam satu kolom terlalu padat.
8. Sebagian judul masih bahasa Inggris, misalnya `PAYMENT LISTS`, `BANK ACCOUNT LISTS`, `ORDER ITEMS`.

## Tujuan Perubahan

Target implementasi:

1. Semua list admin terasa satu sistem desain.
2. Header nomor memakai `No.`.
3. Card list memakai tampilan putih, border halus, dan header section yang jelas.
4. Tabel lebih rapi dengan padding konsisten, hover row, dan badge status.
5. Filter/search berada pada area header/card yang rapi.
6. Empty state informatif dengan icon dan pesan yang jelas.
7. Pagination tetap tersedia dan tidak rusak.
8. Flow update status, verifikasi pembayaran, input ongkir, edit data tetap berjalan.

## Scope Implementasi

File view utama yang perlu dirapikan:

- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/products/index.blade.php`
- `resources/views/admin/categories/index.blade.php`
- `resources/views/admin/brands/index.blade.php`
- `resources/views/admin/orders/index.blade.php`
- `resources/views/admin/orders/show.blade.php`
- `resources/views/admin/pending-shipping-costs/index.blade.php`
- `resources/views/admin/payments/index.blade.php`
- `resources/views/admin/payment-methods/index.blade.php`
- `resources/views/admin/users/index.blade.php`

File controller yang mungkin perlu penyesuaian ringan:

- `app/Http/Controllers/Admin/DashboardController.php`
- `app/Http/Controllers/Admin/UserController.php`
- `app/Http/Controllers/Admin/OrderController.php`
- `app/Http/Controllers/Admin/PaymentController.php`
- `app/Http/Controllers/Admin/PaymentMethodController.php`
- `app/Http/Controllers/Admin/ProductCategoryController.php`
- `app/Http/Controllers/Admin/ProductBrandController.php`

Controller yang kemungkinan tidak perlu diubah:

- `Admin\ProductController`, kecuali jika ingin menambah count/filter lebih lanjut.

Tidak termasuk scope:

- Mengubah struktur database.
- Mengubah route.
- Mengubah create/edit form.
- Membuat fitur delete.
- Menambahkan menu promosi admin baru.
- Mengubah behavior bisnis pembayaran, pesanan, ongkir, atau role.

## Pola Desain Yang Disarankan

### Page Header

Gunakan pola seragam:

- Breadcrumb kecil.
- Judul besar.
- Deskripsi singkat opsional.
- Tombol utama di kanan.

Contoh arah:

```blade
<div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
    <div>
        <p class="text-sm font-semibold text-primary">...</p>
        <h1 class="mt-1 text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
    </div>
    <div class="flex flex-wrap gap-3">...</div>
</div>
```

### List Card

Gunakan card list yang seragam:

```blade
<div class="w-full overflow-hidden border border-[#f2c8d0] bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-[#f2c8d0] bg-[#fff7f8] p-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-lg font-black tracking-wide text-texthighlight">...</h2>
            <p class="mt-1 text-sm text-gray-600">...</p>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[...]">...</table>
    </div>
</div>
```

### Table Header

Header tabel:

- Text uppercase kecil.
- Border bawah.
- Background putih.
- Nomor tabel: `No.`

```blade
<tr class="border-b border-gray-200 bg-white text-left text-xs font-bold uppercase tracking-wide text-gray-500">
    <th class="w-16 px-5 py-4">No.</th>
</tr>
```

Catatan:

- Karena ada `uppercase`, browser akan menampilkan `NO.` secara visual. Source tetap `No.`

### Row

Row tabel:

- `divide-y divide-gray-100` pada tbody.
- `hover:bg-[#fff7f8]`.
- Padding `px-5 py-4`.
- Data utama bold `text-texthighlight`.

### Badge

Gunakan badge konsisten:

- Sukses: `bg-emerald-50 text-emerald-700`
- Warning: `bg-yellow-50 text-yellow-700`
- Info: `bg-blue-50 text-blue-700`
- Danger: `bg-red-50 text-red-700`
- Neutral: `bg-gray-100 text-gray-600`

### Empty State

Setiap list kosong:

- Icon sesuai domain.
- Judul singkat.
- Subtext.
- Tombol jika ada aksi tambah.

## Rencana Per Halaman

### 1. Produk

File:

- `resources/views/admin/products/index.blade.php`

Status:

- Sudah dirapikan sebelumnya.
- Sudah memakai `No.`
- Tidak menampilkan gambar produk sesuai revisi terakhir.

Rencana:

- Jadikan produk sebagai referensi pola card/tabel untuk list lain.
- Pastikan tetap tidak ada gambar produk.
- Pertahankan ringkasan total/aktif/tersedia/stok kosong.

### 2. Kategori

File:

- `resources/views/admin/categories/index.blade.php`

Data:

- `$categories = ProductCategory::withCount('products')->latest()->paginate(10)`

Rencana:

- Ganti `#` menjadi `No.`
- Ubah container menjadi card putih.
- Judul menjadi `Daftar Kategori`
- Subtext: `Kelola pengelompokan produk yang tampil di katalog.`
- Kolom:
  - `No.`
  - `Kategori`
  - `Jumlah Produk`
  - `Status`
  - `Aksi`
- Nama kategori tampil bold, slug/description singkat jika tersedia.
- Badge status konsisten.
- Empty state dengan icon `mdi:tag-outline`.

### 3. Merek

File:

- `resources/views/admin/brands/index.blade.php`

Data:

- `$brands = ProductBrand::withCount('products')->latest()->paginate(10)`

Rencana:

- Ganti `#` menjadi `No.`
- Ubah container menjadi card putih.
- Judul menjadi `Daftar Merek`
- Kolom:
  - `No.`
  - `Merek`
  - `Jumlah Produk`
  - `Status`
  - `Aksi`
- Tidak wajib menampilkan logo.
- Empty state dengan icon `mdi:factory`.

### 4. Pengguna

File:

- `resources/views/admin/users/index.blade.php`

Data:

- User dengan relasi roles.
- Filter role via query `role_id`.

Rencana:

- Ganti `#` menjadi `No.`
- Ubah container menjadi card putih.
- Filter role pindah ke header card agar lebih rapi.
- Judul menjadi `Daftar Pengguna`
- Kolom:
  - `No.`
  - `Pengguna`
  - `Kontak`
  - `Role`
  - `Status`
  - `Dibuat`
  - `Aksi`
- Gabungkan nama/email/telepon agar lebih compact.
- Badge role konsisten.
- Empty state dengan icon `mdi:account-group-outline`.

### 5. Pesanan

File:

- `resources/views/admin/orders/index.blade.php`

Data:

- Order with `user`, `paymentMethod`, `payment`, `delivery`, `items_count`.
- Search `q`.
- Filter `status`.

Rencana:

- Ganti `#` menjadi `No.`
- Ubah container menjadi card putih.
- Form search/filter dibuat responsive dan tetap mempertahankan query behavior.
- Judul menjadi `Daftar Pesanan`
- Kolom:
  - `No.`
  - `Pesanan`
  - `Pelanggan`
  - `Item`
  - `Total`
  - `Pembayaran`
  - `Status`
  - `Pengiriman`
  - `Tanggal`
  - `Aksi`
- Nomor pesanan dibuat bold.
- Status order/payment/delivery memakai badge konsisten.
- Kolom aksi tetap mendukung:
  - update status jika status memungkinkan
  - detail pesanan
- Empty state dengan icon `mdi:receipt-text-outline`.

### 6. Detail Pesanan, Tabel Order Items

File:

- `resources/views/admin/orders/show.blade.php`

Data:

- `$items = $order->items()->with('product')->paginate(10, ['*'], 'items_page')`

Rencana:

- Ganti `#` menjadi `No.` pada tabel item.
- Rapikan card `ORDER ITEMS` menjadi `Item Pesanan`.
- Kolom:
  - `No.`
  - `Produk`
  - `Harga`
  - `Qty`
  - `Subtotal`
- Gunakan table styling yang sama.
- Empty state dengan icon `mdi:package-variant-closed`.
- Hati-hati ada `</div>` berlebih di akhir file; cek saat implementasi agar struktur tidak rusak.

### 7. Ongkir Manual

File:

- `resources/views/admin/pending-shipping-costs/index.blade.php`

Data:

- Orders menunggu konfirmasi ongkir.
- Form input `shipping_cost` per row.

Rencana:

- Ganti `#` menjadi `No.`
- Ubah container menjadi card putih.
- Judul menjadi `Daftar Pesanan Menunggu Ongkir`
- Kolom:
  - `No.`
  - `Pesanan`
  - `Pelanggan`
  - `Alamat`
  - `Item`
  - `Subtotal`
  - `Input Ongkir`
  - `Aksi`
- Form input ongkir dibuat lebih rapi dan tetap submit ke route yang sama.
- Empty state dengan icon `mdi:truck-check-outline`.

### 8. Pembayaran

File:

- `resources/views/admin/payments/index.blade.php`

Data:

- Payment with `order.user`, `paymentMethod`, `verifiedBy`.
- Search `q`.
- Filter `status`.
- Action verify/reject.

Rencana:

- Ganti `#` menjadi `No.`
- Ubah container menjadi card putih.
- Judul menjadi `Daftar Pembayaran`
- Filter search/status dibuat responsive.
- Kolom:
  - `No.`
  - `Pesanan`
  - `Pelanggan`
  - `Metode`
  - `Nominal`
  - `Pengirim`
  - `Tanggal Transfer`
  - `Bukti`
  - `Status`
  - `Diverifikasi`
  - `Aksi`
- Ganti label Inggris `Amount`, `Sender`, `Action` ke bahasa Indonesia.
- Proof image button tetap ada jika ada bukti.
- Verify/Reject button dibuat lebih ringkas, tetap POST/PATCH sama.
- Empty state dengan icon `mdi:cash-check`.

### 9. Metode Pembayaran

File:

- `resources/views/admin/payment-methods/index.blade.php`

Data:

- PaymentMethod ordered by `sort_order`, `bank_name`.

Rencana:

- Ganti `#` menjadi `No.`
- Ubah container menjadi card putih.
- Judul menjadi `Daftar Rekening Bank`
- Kolom:
  - `No.`
  - `Metode`
  - `Bank`
  - `Nomor Rekening`
  - `Nama Rekening`
  - `Urutan`
  - `Status`
  - `Aksi`
- Catatan: kolom saat ini bernama `Pesanan` tetapi isinya `sort_order`; sebaiknya diganti menjadi `Urutan`.
- Empty state dengan icon `mdi:bank-outline`.

### 10. Dashboard Admin

File:

- `resources/views/admin/dashboard.blade.php`

List terkait:

- Recent orders
- Recent payments

Rencana:

- Tidak ada simbol `#` pada tabel dashboard saat ini.
- Tetap rapikan visual list agar konsisten dengan tabel lain:
  - header kecil
  - row hover
  - badge status
  - empty state lebih rapi
- Jangan mengubah metrik dashboard.

## Rencana Test

Tambah test baru, misalnya:

- `tests/Feature/Admin/AdminListUiTest.php`

Skenario minimal:

1. Admin users index menampilkan `No.` dan tidak menampilkan header `#`.
2. Admin orders index menampilkan `No.` dan tetap menampilkan `DAFTAR PESANAN` atau judul baru.
3. Admin payments index menampilkan `No.` dan label Indonesia seperti `Nominal`.
4. Admin payment methods index menampilkan `No.` dan `Urutan`.
5. Admin categories index menampilkan `No.`
6. Admin brands index menampilkan `No.`
7. Admin pending shipping costs index menampilkan `No.`
8. Admin order detail item table menampilkan `No.`
9. Existing ProductListTest tetap lolos.

Test existing yang perlu diperhatikan:

- `tests/Feature/Admin/OrderFilterTest.php`
  - Saat ini assert `DAFTAR PESANAN`.
  - Jika judul diubah menjadi title case `Daftar Pesanan`, test perlu disesuaikan atau pertahankan teks lama sebagai hidden/visible compatible.
- `tests/Feature/PaginationTablesTest.php`
  - Memastikan pagination tetap muncul untuk users, order detail, dashboard.
- `tests/Feature/Admin/ShippingCostConfirmationTest.php`
  - Memastikan flow ongkir manual tetap benar.
- `tests/Feature/Admin/ProductListTest.php`
  - Memastikan list produk tetap rapi dan tidak menampilkan gambar.

Command validasi:

```bash
php artisan test --filter=Admin
php artisan test --filter=PaginationTablesTest
npm run build
```

## Rencana Validasi Browser

Setelah implementasi:

1. Login sebagai admin.
2. Cek `/admin/dashboard`.
3. Cek `/admin/products`.
4. Cek `/admin/categories`.
5. Cek `/admin/brands`.
6. Cek `/admin/orders`.
7. Cek salah satu `/admin/orders/{order}`.
8. Cek `/admin/pending-shipping-costs`.
9. Cek `/admin/payments`.
10. Cek `/admin/payment-methods`.
11. Cek `/admin/users`.

Validasi visual:

- Semua header nomor tabel tampil `No.` atau secara visual `NO.` karena uppercase.
- Tidak ada header tabel yang masih `#`.
- Filter/search tetap bisa submit.
- Tombol detail/edit/verify/reject/input ongkir tetap tersedia.
- Pagination tetap tampil.
- Tidak ada console error.
- Table lebar tetap scroll horizontal pada viewport kecil.

## Urutan Implementasi Disarankan

1. Buat pola table/card konsisten berdasarkan `admin.products.index`.
2. Update `categories.index`.
3. Update `brands.index`.
4. Update `users.index`.
5. Update `payment-methods.index`.
6. Update `orders.index`.
7. Update `orders.show` pada tabel item.
8. Update `pending-shipping-costs.index`.
9. Update `payments.index`.
10. Rapikan list dashboard jika masih dalam scope waktu.
11. Tambahkan/update tests untuk `No.`.
12. Jalankan test dan build.
13. Validasi browser.

## Catatan Risiko

1. Banyak view admin akan berubah sekaligus; risiko typo Blade lebih tinggi.
2. Jangan mengubah route, name input, method form, atau CSRF/method spoofing.
3. Form update status pesanan berada di dalam row tabel; struktur HTML harus tetap valid.
4. Form verify/reject payment berada di dalam row tabel; jangan mengganti action/method.
5. Form input ongkir manual harus tetap mengirim `shipping_cost`.
6. Jangan mengganti `#` dalam class warna Tailwind.
7. Jangan menampilkan gambar produk di list produk karena user sudah meminta tidak perlu gambar.
8. Ada beberapa file modified di worktree dari perubahan sebelumnya atau user; jangan revert perubahan di luar scope.

## Rekomendasi Implementasi

Untuk menjaga risiko tetap rendah:

1. Kerjakan per halaman list, bukan semua sekaligus dalam satu patch besar.
2. Setelah 2-3 halaman, jalankan test cepat jika perubahan mulai luas.
3. Pertahankan data dan behavior existing; fokus pada markup, class, label, dan empty state.
4. Gunakan `No.` untuk semua header nomor tabel.
5. Pertahankan produk tanpa gambar.
6. Pastikan test existing tetap lolos sebelum browser check.
