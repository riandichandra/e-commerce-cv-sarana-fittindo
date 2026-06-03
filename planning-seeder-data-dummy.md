# Planning Seeder Data Dummy

## Tujuan
Membuat seeder dummy yang menghasilkan data seolah-olah sistem sudah berjalan selama beberapa bulan, dengan:
- multiple pelanggan aktif
- produk, kategori, brand, dan payment method lengkap
- order dan order item yang tersebar selama 3-6 bulan terakhir
- payment dan verifikasi pembayaran yang realistis
- variasi status order (`pending_payment`, `payment_confirmed`, `processing`, `shipped`, `completed`, `cancelled`)

## Analisis Codebase dan Database

### Struktur seeders saat ini
- `database/seeders/DatabaseSeeder.php` memanggil:
  - `RoleSeeder`
  - `CategorySeeder`
  - `UserSeeder`
- `UserSeeder` hanya membuat user fixed untuk 5 role.
- Tidak ada seeder untuk products, brands, payment methods, orders, order_items, payments.

### Model & tabel penting
- `App\Models\User` -> `users`
- `App\Models\ProductCategory` -> `product_categories`
- `App\Models\ProductBrand` -> `product_brands`
- `App\Models\Product` -> `products`
- `App\Models\ProductImage` -> `product_images`
- `App\Models\PaymentMethod` -> `payment_methods`
- `App\Models\Order` -> `orders`
- `App\Models\OrderItem` -> `order_items`
- `App\Models\Payment` -> `payments`

### Kolom penting untuk dummy data
- `orders`: `user_id`, `order_number`, `status`, `subtotal`, `discount_amount`, `shipping_cost`, `total_amount`, `payment_method_id`, shipping fields, `created_at`
- `order_items`: `order_id`, `product_id`, `product_name`, `product_price`, `quantity`, `subtotal`
- `payments`: `order_id`, `payment_method_id`, `amount`, `status`, `verified_by`, `verified_at`, `created_at`
- `products`: `category_id`, `brand_id`, `name`, `slug`, `description`, `price`, `stock`, `status`, `weight`, `is_featured`, `is_active`

### Aturan bisnis yang perlu disimulasikan
- Order `payment_confirmed` / `processing` / `shipped` / `completed` harus memiliki payment record dan `verified_at` jika status payment `verified`.
- Order `pending_payment` dapat memiliki payment status `pending` atau `rejected`.
- Order `cancelled` dapat terjadi lewat buyer atau admin, mungkin dengan `cancelled_at`.
- `top product`, `total revenue`, dan `monthly revenue` bergantung pada `orders.created_at` dan `payments.verified_at`.

## Scope Implementasi Seeder

### Seeder baru yang disarankan
- `database/seeders/DummyDataSeeder.php`
  - `seedBrands()`
  - `seedPaymentMethods()`
  - `seedProducts()`
  - `seedCustomers()`
  - `seedOrdersAndPayments()`
- Update `database/seeders/DatabaseSeeder.php` untuk memanggil `DummyDataSeeder::class` setelah seeding roles/categories/users.

### Factory yang disarankan
Karena saat ini hanya ada `UserFactory`, perlu tambahan factory jika ingin implementasi elegan:
- `database/factories/ProductFactory.php`
- `database/factories/OrderFactory.php`
- `database/factories/OrderItemFactory.php`
- `database/factories/PaymentFactory.php`

Jika tidak ingin membuat semua factory, seeder bisa membuat data manual dengan array faker.

## Desain Dummy Data

### Timeline dan distribution
- Rentang waktu: 3-6 bulan terakhir.
- Data paling aktif:
  - 15-20 order per minggu untuk kondisi normal
  - 30-40 order per minggu saat peak "promosi"
- Metode pembayaran yang digunakan: bank transfer, e-wallet, virtual account.
- Pelanggan: 80-120 akun aktif.
- Produk: 20-30 produk aktif, dengan variasi kategori dan brand.
- Produk unggulan (`is_featured`) sekitar 20%.

### Volume contoh
- `users`: 80-120 pelanggan + 5 staf default
- `products`: 20-30
- `orders`: 400-600 total order
- `order_items`: 800-1200 items
- `payments`: 350-500 payments

### Variasi order status
- `completed`: 50%
- `shipped` / `processing`: 20%
- `payment_confirmed`: 10%
- `waiting_payment_confirmation` / `pending_payment`: 15%
- `cancelled`: 5%

### Distribusi revenue per bulan
- Pastikan ada order di setiap bulan dalam rentang 3-6 bulan
- Gunakan `created_at` dan `verified_at` pada pembayaran untuk memetakan revenue bulan yang benar
- Gunakan range harga produk realistis (mis. Rp 50.000 - Rp 1.500.000)

## Implementasi Teknis

### 1. Seed peran dan master data minimal
- `RoleSeeder` sudah ada.
- Tambahkan `BrandSeeder` (jika belum ada) untuk `product_brands`.
- Tambahkan `PaymentMethodSeeder` untuk `payment_methods`.
- Gunakan `CategorySeeder` saat ini.

### 2. Seed produk
- Buat data produk stub: nama, kategori, brand, harga, stock, status aktif.
- Simulasikan `product_images` jika diperlukan.

### 3. Seed pelanggan realistik
- Buat 80-120 pelanggan dengan `User::factory()`.
- Atur beberapa akun `email_verified_at` null bila ingin menampilkan unverified users.
- Pastikan semua pelanggan diberi role `pelanggan`.

### 4. Seed order dan payment
- Untuk setiap bulan dalam rentang historis, buat `n` order per pelanggan acak.
- Kalkulasi subtotal berdasarkan produk dan quantity.
- Tentukan `shipping_cost` acak (misalnya 15k-50k).
- Tentukan `discount_amount` acak untuk beberapa order.
- Set `total_amount` = subtotal - discount + shipping.
- Order statuses:
  - Jika order `completed`/`shipped`/`processing`, buat payment `verified` dengan `verified_at` beberapa hari setelah `created_at`.
  - Jika `pending_payment`, buat payment `pending` atau `rejected`.
  - Jika `cancelled`, tambahkan `cancelled_at` dan `cancellation_reason`.
- Gunakan helper `Order::generateOrderNumber()` bila ada.

### 5. Sinkronisasi relasi
- `order_items.product_name` sebaiknya disalin dari `product.name`.
- `order_items.product_price` dari `product.price`.
- `payment.payment_method_id` pilih dari seeded payment methods.
- `payment.verified_by` bisa diisi dengan ID user admin/marketing sebagai verifier untuk `verified` status.

### 6. Data analitik yang baik
- Pastikan `payments.verified_at` berada dalam bulan yang sama atau setelah `orders.created_at`.
- Pastikan `orders.created_at` tersebar di beberapa bulan.
- Pastikan produk terlaris muncul dengan jumlah order item yang wajar.
- Berikan variasi `product_name` yang sama untuk produk dengan berbeda `product_id` jika pengguna beli product sama.

## File yang Disarankan untuk Ditambahkan / Diubah
- `database/seeders/DummyDataSeeder.php`
- `database/seeders/DatabaseSeeder.php`
- `database/seeders/BrandSeeder.php` (jika belum ada)
- `database/seeders/PaymentMethodSeeder.php`
- `database/factories/ProductFactory.php`
- `database/factories/OrderFactory.php`
- `database/factories/OrderItemFactory.php`
- `database/factories/PaymentFactory.php`

## Testing & Validasi
- Jalankan `php artisan migrate:fresh --seed` di environment development.
- Pastikan tidak ada error seeder.
- Buka halaman dashboard dan pastikan:
  - revenue dan monthly revenue muncul untuk beberapa bulan
  - top product berubah per tahun
  - ada order dengan berbagai status
- Validasi data manual di tabel `orders`, `order_items`, `payments`.

## Notes
- Seeder ini tidak memerlukan perubahan skema database.
- Jika ada model factory yang belum tersedia, sebaiknya tambahkan untuk menstandardisasi dummy data.
- Gunakan `now()->subMonths()` dan `faker->dateTimeBetween()` untuk mendistribusikan data secara natural.
- Perlu hati-hati dengan foreign key constraints: buat produk, payment methods, users sebelum order.

## Rekomendasi Prioritas
1. Buat `DummyDataSeeder` dan update `DatabaseSeeder`.
2. Tambahkan seeders master untuk brands dan payment methods.
3. Tambah factory untuk products/orders/order_items/payments.
4. Isi order/order_item/payment dengan distribusi 3-6 bulan.
5. Uji seeding dan dashboard display.
