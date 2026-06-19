# Planning Seeder Data Dummy Pesanan

Dokumen ini berisi analisis database dan rencana implementasi seeder data dummy pesanan untuk website CV Sarana Fittindo. Tahap ini hanya berupa analisis dan perencanaan; tidak ada pembuatan Seeder, Factory, Migration, Model, atau kode PHP.

Prinsip revisi utama: seeder harus bersifat append-only. Seeder hanya boleh menambahkan data baru yang dibutuhkan, tidak boleh menghapus, truncate, atau mengubah data existing.

Revisi tambahan sebelum implementasi: tabel `products`, `users`, dan `user_addresses` bersifat read-only selama proses seeder. Seeder hanya boleh membaca data dari ketiga tabel tersebut sebagai referensi.

## Tahap 1 - Analisis Database

### Sumber Analisis

Analisis dilakukan berdasarkan migration, model Eloquent, dan metadata schema aktual dari koneksi Laravel untuk tabel:

- `orders`
- `order_items`
- `payments`
- `products`
- `users`
- `user_addresses`

Kondisi data existing saat dicek:

- `users`: 7 data, semuanya aktif.
- `products`: 22 data.
- `products` yang aktif, tersedia, dan stok lebih dari 0: 22 data.
- `user_addresses`: 1 data.
- `payment_methods`: 5 data.
- `orders`: 10 data.
- `payments`: 10 data.

Implikasi untuk rencana seeder:

- Target akhir bukan menambah 30 order baru, tetapi membuat total `orders` pada database menjadi sekitar 30 data.
- Dengan kondisi saat analisis terdapat 10 order existing, estimasi tambahan order yang dibutuhkan adalah sekitar 20 order baru.
- Seeder harus menghitung jumlah order existing saat dijalankan, lalu hanya menambahkan kekurangan data sampai target sekitar 30 order.
- Data existing tidak boleh dihapus, tidak boleh di-truncate, dan tidak boleh diubah.

### Ketentuan Tambahan Sebelum Implementasi

#### Role Pengguna

- Gunakan hanya user dengan role `pelanggan` sebagai pembeli pada tabel `orders`.
- Jangan menggunakan akun admin, super admin, owner, direktur, general manager, marketing, atau role internal lain sebagai pembeli.
- User admin atau role internal hanya boleh dipakai untuk field audit berikut:
  - `payments.verified_by`.
  - `orders.cancelled_by`.
  - `orders.stock_restored_by`.
- Jika tidak ditemukan user dengan role `pelanggan`, implementasi seeder harus menampilkan peringatan dan menghentikan proses.
- Seeder tidak boleh membuat user pelanggan baru untuk mengatasi kondisi pelanggan kosong.

#### Distribusi Transaksi Pelanggan

- Distribusikan order tambahan secara proporsional ke seluruh user dengan role `pelanggan` yang tersedia.
- Hindari seluruh transaksi berasal dari satu pelanggan.
- Usahakan setiap pelanggan memiliki minimal satu transaksi apabila jumlah pelanggan mencukupi dibanding jumlah order tambahan.
- Variasikan jumlah transaksi per pelanggan agar terlihat realistis, misalnya sebagian pelanggan memiliki satu order dan sebagian lain memiliki beberapa order.
- Perhitungan distribusi pelanggan harus memperhitungkan order existing agar hasil akhir tidak berat pada satu pelanggan jika data existing sudah tidak seimbang.

#### Strategi Alamat Pengiriman

- Gunakan alamat dari tabel `user_addresses` apabila tersedia untuk user pelanggan yang bersangkutan.
- Jika user pelanggan tidak memiliki alamat pada tabel `user_addresses`, gunakan data snapshot pengiriman langsung pada tabel `orders` tanpa membuat record baru pada `user_addresses`.
- Jangan menggunakan satu alamat yang sama untuk seluruh transaksi apabila terdapat alternatif data alamat yang lebih realistis.
- Jangan membuat, mengubah, atau menghapus data pada tabel `user_addresses`.
- Karena `orders` tidak memiliki `user_address_id`, penggunaan alamat dilakukan sebagai snapshot ke kolom `shipping_*` pada order baru.

#### Distribusi Status Order

- Distribusi status merupakan target akhir database, bukan jumlah data tambahan yang wajib dibuat.
- Seeder harus menghitung jumlah data existing terlebih dahulu sebelum menentukan jumlah order tambahan pada masing-masing status.
- Jangan mengubah status order existing untuk memaksa distribusi tertentu.
- Prioritaskan konsistensi data dibanding memaksakan distribusi status yang persis sama.

#### Proteksi Data Existing

Tabel berikut bersifat read-only selama proses seeder:

- `products`
- `users`
- `user_addresses`

Dilarang:

- Menambah produk baru.
- Mengubah produk existing.
- Menghapus produk existing.
- Mengubah harga produk.
- Mengubah stok produk.
- Mengubah status produk.
- Membuat user baru.
- Mengubah user existing.
- Menghapus user existing.
- Membuat alamat baru.
- Mengubah alamat existing.
- Menghapus alamat existing.

#### Prinsip Seeder

- Seeder harus bersifat append-only.
- Seeder hanya boleh menambahkan data baru pada:
  - `orders`.
  - `order_items`.
  - `payments`.
- Seeder dilarang menggunakan:
  - `truncate()`.
  - `delete()`.
  - `destroy()`.
  - `update()` terhadap data existing.
  - `migrate:fresh`.
  - `db:wipe`.
- Data existing harus tetap utuh setelah seeder selesai dijalankan.

### 1. Foreign Key dan Relasi Antar Tabel

#### `orders`

Foreign key:

- `orders.user_id` -> `users.id`, `ON DELETE RESTRICT`.
- `orders.payment_method_id` -> `payment_methods.id`, nullable, `ON DELETE SET NULL`.
- `orders.promotion_id` -> `promotions.id`, nullable, `ON DELETE SET NULL`.
- `orders.cancelled_by` -> `users.id`, nullable, `ON DELETE SET NULL`.
- `orders.stock_restored_by` -> `users.id`, nullable, `ON DELETE SET NULL`.

Relasi model:

- `Order belongsTo User` melalui `user_id`.
- `Order hasMany OrderItem` melalui `order_items.order_id`.
- `Order hasOne Payment` melalui `payments.order_id`.
- `Order belongsTo PaymentMethod` melalui `payment_method_id`.
- `Order belongsTo Promotion` melalui `promotion_id`.

Catatan penting: `orders` tidak memiliki kolom `user_address_id`. Alamat pengiriman pada order disimpan sebagai snapshot denormalized di kolom `shipping_name`, `shipping_phone`, `shipping_address`, `shipping_province`, `shipping_city`, `shipping_district`, `shipping_village`, dan `shipping_postal_code`.

#### `order_items`

Foreign key:

- `order_items.order_id` -> `orders.id`, `ON DELETE CASCADE`.
- `order_items.product_id` -> `products.id`, `ON DELETE RESTRICT`.

Relasi model:

- `OrderItem belongsTo Order`.
- `OrderItem belongsTo Product`.

#### `payments`

Foreign key:

- `payments.order_id` -> `orders.id`, `ON DELETE RESTRICT`.
- `payments.payment_method_id` -> `payment_methods.id`, `ON DELETE RESTRICT`.
- `payments.verified_by` -> `users.id`, nullable, `ON DELETE SET NULL`.

Constraint tambahan:

- `payments.order_id` memiliki unique index. Artinya satu order maksimal memiliki satu payment.

Relasi model:

- `Payment belongsTo Order`.
- `Payment belongsTo PaymentMethod`.
- `Payment belongsTo User` sebagai verifier melalui `verified_by`.

#### `products`

Foreign key:

- `products.category_id` -> `product_categories.id`, `ON DELETE RESTRICT`.
- `products.brand_id` -> `product_brands.id`, nullable, `ON DELETE SET NULL`.

Relasi model:

- `Product belongsTo ProductCategory`.
- `Product belongsTo ProductBrand`.
- `Product hasMany ProductImage`.

#### `users`

Tidak ada foreign key keluar dari `users` pada tabel target. Tabel ini direferensikan oleh:

- `orders.user_id`.
- `orders.cancelled_by`.
- `orders.stock_restored_by`.
- `payments.verified_by`.
- `user_addresses.user_id`.

Relasi model:

- `User hasMany UserAddress`.
- `User hasMany Wishlist`.
- `User hasOne Cart`.

#### `user_addresses`

Foreign key:

- `user_addresses.user_id` -> `users.id`, `ON DELETE CASCADE`.

Catatan schema akhir:

- Kolom region `province_id`, `regency_id`, `district_id`, dan `village_id` bertipe `varchar(32)` dan tidak lagi memiliki foreign key ke tabel region.
- Kolom snapshot nama region tersedia sebagai nullable: `province_name`, `city_name`, `district_name`, `village_name`.
- `orders` tidak mereferensikan `user_addresses` secara foreign key, sehingga seeder perlu menyalin data alamat ke kolom snapshot pengiriman pada `orders`.

### 2. Kolom Wajib NOT NULL yang Harus Diisi

#### `orders`

Kolom wajib tanpa default yang perlu diisi:

- `user_id`
- `order_number`
- `subtotal`
- `total_amount`
- `shipping_name`
- `shipping_phone`
- `shipping_address`
- `shipping_province`
- `shipping_city`
- `shipping_district`

Kolom wajib dengan default yang dapat tetap eksplisit diisi agar data konsisten:

- `status`, default `belum_dibayar`.
- `discount_amount`, default `0.00`.
- `shipping_cost`, default `0.00`.
- `shipping_cost_status`, default `fixed`.

Kolom nullable yang dapat diisi sesuai status:

- `payment_method_id`
- `shipping_village`
- `shipping_postal_code`
- `shipping_cost_source`
- `shipping_origin_district_id`
- `shipping_destination_district_id`
- `shipping_weight_gram`
- `shipping_courier_code`
- `shipping_courier_name`
- `shipping_service`
- `shipping_service_description`
- `shipping_etd`
- `shipping_rate_snapshot`
- `shipping_cost_confirmed_at`
- `cancelled_by`
- `cancellation_reason`
- `cancelled_at`
- `shipped_at`
- `completed_at`
- `auto_completed_at`
- `completion_source`
- `completion_notes`
- `stock_restored_at`
- `stock_restored_by`

#### `order_items`

Kolom wajib tanpa default yang perlu diisi:

- `order_id`
- `product_id`
- `product_name`
- `product_price`
- `quantity`
- `subtotal`

#### `payments`

Kolom wajib tanpa default yang perlu diisi:

- `order_id`
- `payment_method_id`
- `amount`

Kolom wajib dengan default:

- `status`, default `menunggu`.

Kolom nullable yang dapat diisi sesuai status:

- `proof_image`
- `transfer_date`
- `sender_name`
- `verified_by`
- `verified_at`
- `rejection_reason`
- `notes`

#### `products`

Untuk penggunaan sebagai referensi order item, kolom penting yang harus valid:

- `id`
- `name`
- `price`
- `stock`
- `status`
- `weight`
- `is_active`
- `deleted_at`

Kolom wajib di schema produk:

- `category_id`
- `name`
- `slug`
- `price`
- `stock`, default `0`.
- `status`, default `tidak tersedia`.
- `weight`
- `is_featured`, default `0`.
- `is_active`, default `1`.

Seeder pesanan tidak perlu membuat produk baru; produk harus diambil dari data existing.

#### `users`

Untuk penggunaan sebagai pembeli dan verifier, kolom penting yang harus valid:

- `id`
- `name`
- `email`
- `phone`
- `is_active`

Kolom wajib schema users:

- `name`
- `email`
- `password`
- `is_active`, default `1`.

Seeder pesanan tidak perlu membuat user baru; user harus diambil dari data existing.

#### `user_addresses`

Kolom wajib tanpa default yang perlu tersedia jika alamat existing dipakai:

- `user_id`
- `label`
- `receiver_name`
- `receiver_phone`
- `full_address`
- `province_id`
- `regency_id`
- `district_id`
- `village_id`
- `postal_code`

Kolom wajib dengan default:

- `region_source`, default `rajaongkir`.
- `is_main`, default `0`.

Kolom nullable tetapi sangat berguna untuk snapshot shipping order:

- `province_name`
- `city_name`
- `district_name`
- `village_name`

### 3. Kolom Nominal: Subtotal, Shipping Cost, Total Price, Grand Total

Kolom nominal yang tersedia pada schema target:

- Subtotal order item: `order_items.subtotal`.
- Subtotal order: `orders.subtotal`.
- Ongkos kirim: `orders.shipping_cost`.
- Diskon: `orders.discount_amount`.
- Total akhir order: `orders.total_amount`.
- Nominal pembayaran: `payments.amount`.

Tidak ditemukan kolom bernama persis:

- `total_price`
- `grand_total`

Karena database tidak memiliki kolom `total_price` atau `grand_total`, rencana implementasi harus memakai `orders.total_amount` sebagai total akhir/grand total transaksi. Jangan membuat atau mengisi kolom `total_price` dan `grand_total` karena kolom tersebut tidak ada.

Rumus yang direncanakan:

- `order_items.subtotal` = `order_items.product_price` x `order_items.quantity`.
- `orders.subtotal` = jumlah semua `order_items.subtotal` untuk order tersebut.
- `orders.shipping_cost` = ongkir final yang ditentukan berdasarkan bobot, tujuan, dan variasi realistis.
- `orders.total_amount` = `orders.subtotal` - `orders.discount_amount` + `orders.shipping_cost`.
- `payments.amount` = `orders.total_amount` untuk payment valid.

### 4. Field yang Memerlukan Data Valid Agar Transaksi Benar

Field yang harus valid untuk membuat transaksi dummy:

- `orders.user_id` harus mengarah ke user existing yang aktif dan memiliki role `pelanggan`.
- `orders.order_number` harus unik.
- `orders.status` harus memakai enum aktual: `menunggu_konfirmasi_ongkir`, `belum_dibayar`, `menunggu_verifikasi_pembayaran`, `pembayaran_dikonfirmasi`, `diproses`, `dikirim`, `selesai`, `dibatalkan`.
- `orders.payment_method_id` harus mengarah ke `payment_methods.id` existing jika order menggunakan metode pembayaran.
- `orders.subtotal`, `orders.shipping_cost`, `orders.discount_amount`, dan `orders.total_amount` harus konsisten.
- `orders.shipping_name`, `shipping_phone`, `shipping_address`, `shipping_province`, `shipping_city`, dan `shipping_district` wajib terisi.
- `order_items.order_id` harus mengarah ke order yang dibuat.
- `order_items.product_id` harus mengarah ke produk existing.
- `order_items.product_name` dan `product_price` harus disalin sebagai snapshot dari produk saat order dibuat.
- `order_items.quantity` harus lebih dari 0.
- `payments.order_id` harus unik dan mengarah ke order existing.
- `payments.payment_method_id` harus mengarah ke payment method existing.
- `payments.amount` harus sama dengan `orders.total_amount` untuk pembayaran normal.
- `payments.status` harus memakai enum aktual: `menunggu`, `terverifikasi`, atau `ditolak`.
- `payments.verified_by` harus user existing dengan role admin/internal jika payment terverifikasi.

### 5. Kemungkinan Kendala yang Dapat Menyebabkan Seeder Gagal

- Menggunakan status instruksi secara literal `menunggu_pembayaran` atau `menunggu_verifikasi` akan gagal karena bukan enum aktual di `orders.status`.
- Status yang valid untuk kebutuhan instruksi harus dipetakan menjadi `belum_dibayar` dan `menunggu_verifikasi_pembayaran`.
- Mengisi kolom `total_price` atau `grand_total` akan gagal karena kolom tersebut tidak ada.
- Membuat lebih dari satu payment untuk order yang sama akan gagal karena `payments.order_id` unique.
- `payments.payment_method_id` wajib dan `ON DELETE RESTRICT`; payment tidak bisa dibuat jika payment method tidak tersedia.
- `orders.user_id` wajib dan `ON DELETE RESTRICT`; order tidak bisa dibuat jika user tidak tersedia.
- Jika tidak ada user dengan role `pelanggan`, seeder harus berhenti dan menampilkan peringatan, bukan memakai user admin/internal sebagai pembeli.
- `order_items.product_id` wajib dan `ON DELETE RESTRICT`; order item tidak bisa dibuat jika produk tidak tersedia.
- Produk yang soft-deleted, tidak aktif, status `tidak tersedia`, atau stok `0` dapat membuat data transaksi tidak realistis dan berpotensi tidak cocok dengan logika aplikasi.
- Jumlah `user_addresses` hanya 1 data, sehingga variasi alamat pengiriman sangat terbatas jika hanya memakai alamat existing tanpa membuat alamat baru.
- Jika user address tidak memiliki `province_name`, `city_name`, atau `district_name`, snapshot shipping order tetap wajib terisi dan perlu fallback dari field yang valid tanpa mengasumsikan kolom lain.
- `order_number` unique; format nomor harus menghindari bentrok dengan 10 order existing.
- Seeder tidak boleh mengurangi atau mengubah `products.stock`. Produk existing hanya dipakai sebagai referensi snapshot `order_items`.
- Seeder tidak boleh mengubah `products.price`. Harga produk hanya dibaca lalu disalin ke `order_items.product_price` sebagai snapshot transaksi.
- Seeder tidak boleh mengubah order, payment, item, user, produk, atau alamat existing.
- `payments.verified_by` nullable, tetapi untuk payment `terverifikasi` sebaiknya memakai user admin/pegawai existing. User internal hanya boleh digunakan untuk `verified_by`, `cancelled_by`, dan `stock_restored_by`, bukan sebagai pembeli order.

## Tahap 2 - Rencana Implementasi Seeder

### 1. Struktur Relasi

Seeder pesanan akan menggunakan struktur berikut:

- Ambil user existing dari `users` yang memiliki role `pelanggan` sebagai pembeli order.
- Ambil alamat existing dari `user_addresses` milik user jika tersedia.
- Salin data alamat ke kolom snapshot pengiriman pada `orders` karena tidak ada foreign key dari `orders` ke `user_addresses`.
- Ambil produk existing dari `products` sebagai sumber `order_items`.
- Simpan snapshot produk ke `order_items.product_name` dan `order_items.product_price`.
- Tentukan produk, quantity, subtotal item, subtotal order, ongkir, diskon, dan total order sebelum menyimpan order baru.
- Buat `orders` baru dengan nominal yang sudah final untuk data baru tersebut.
- Buat `order_items` baru berdasarkan `orders.id` dari order yang baru dibuat.
- Buat `payments` baru hanya untuk order baru yang membutuhkan payment, dengan `payments.order_id` mengarah ke order baru terkait.
- Jangan melakukan update pada order, order item, payment, user, produk, atau alamat yang sudah ada sebelum seeder dijalankan.

Relasi utama yang dipakai:

- `users.id` -> `orders.user_id`.
- `orders.id` -> `order_items.order_id`.
- `products.id` -> `order_items.product_id`.
- `orders.id` -> `payments.order_id`.
- `payment_methods.id` -> `orders.payment_method_id` dan `payments.payment_method_id`.

### 2. Strategi Pembuatan Orders

Target akhir: total `orders` pada database sekitar 30 data.

Aturan utama:

- Jangan menambah 30 order baru secara langsung.
- Hitung jumlah order existing terlebih dahulu.
- Tambahkan hanya kekurangan order sampai total mendekati 30.
- Jika jumlah order existing sudah 30 atau lebih, seeder tidak perlu menambahkan order baru.
- Jangan menghapus atau mengubah order existing untuk menyesuaikan distribusi status.
- Jika tidak ada user role `pelanggan`, hentikan proses dan tampilkan peringatan.

Berdasarkan kondisi saat analisis:

- Order existing: 10 data.
- Target total: sekitar 30 data.
- Estimasi order tambahan: sekitar 20 data.

Distribusi status ideal untuk total sekitar 30 order tetap menjadi acuan akhir, bukan angka tambahan mutlak:

| Instruksi | Enum aktual database | Jumlah |
| --- | --- | ---: |
| `menunggu_pembayaran` | `belum_dibayar` | 3 |
| `menunggu_verifikasi` | `menunggu_verifikasi_pembayaran` | 2 |
| `diproses` | `diproses` | 5 |
| `dikirim` | `dikirim` | 5 |
| `selesai` | `selesai` | 13 |
| `dibatalkan` | `dibatalkan` | 2 |

Strategi distribusi saat ada data existing:

- Hitung jumlah existing per status terlebih dahulu.
- Bandingkan dengan distribusi target di atas.
- Tambahkan order baru pada status yang masih kurang.
- Jika status tertentu pada data existing sudah melebihi target, jangan ubah atau hapus data existing; cukup kurangi prioritas pembuatan status tersebut pada data tambahan.
- Jika total akhir tidak bisa persis mengikuti distribusi karena data existing sudah tidak seimbang, prioritaskan total sekitar 30 dan konsistensi data dibanding memaksa distribusi ideal.

Rencana order number:

- Gunakan format yang konsisten dengan model, misalnya `SFYYYYMMDDNNNN`, tetapi sequence harus dihitung agar tidak bentrok dengan order existing.
- Karena akan membuat data historis dengan variasi tanggal, order number dapat memakai tanggal `created_at` order dan sequence per tanggal.
- Pastikan uniqueness terhadap seluruh `orders.order_number` existing sebelum insert.

Rencana waktu transaksi:

- Order tambahan dibuat menyebar dalam rentang beberapa bulan terakhir agar dashboard dan laporan terlihat natural.
- `selesai` dibuat lebih lama dari `dikirim` dan `diproses`.
- `dikirim` memiliki `shipped_at` terisi, sebagian sudah mendekati masa auto-completion.
- `selesai` memiliki `shipped_at` dan `completed_at` terisi dengan urutan waktu masuk akal.
- `dibatalkan` memiliki `cancelled_at` dan `cancellation_reason`.
- `belum_dibayar` dibuat paling baru atau belum melewati pola pembayaran.
- `menunggu_verifikasi_pembayaran` memiliki payment pending dan transfer date relatif baru.

Rencana field shipping:

- `shipping_name` dari `user_addresses.receiver_name` jika tersedia, fallback ke `users.name`.
- `shipping_phone` dari `user_addresses.receiver_phone` jika tersedia, fallback ke `users.phone` jika tidak null.
- `shipping_address` dari `user_addresses.full_address`.
- `shipping_province` dari `user_addresses.province_name` jika tersedia.
- `shipping_city` dari `user_addresses.city_name` jika tersedia.
- `shipping_district` dari `user_addresses.district_name` jika tersedia.
- `shipping_village` dari `user_addresses.village_name` jika tersedia.
- `shipping_postal_code` dari `user_addresses.postal_code`.

Karena saat analisis hanya ada 1 `user_addresses`, implementasi nanti perlu menangani keterbatasan alamat. Jika user pelanggan memiliki alamat sendiri, gunakan alamat milik user tersebut sebagai snapshot. Jika user pelanggan tidak memiliki alamat, isi snapshot pengiriman langsung pada `orders` tanpa membuat record baru di `user_addresses`. Jangan memakai satu alamat yang sama untuk seluruh transaksi apabila terdapat alternatif data alamat yang lebih realistis.

### 3. Strategi Pembuatan Order Items

Target:

- Setiap order memiliki 1 sampai 4 order item.
- Total `order_items` database ditargetkan minimal 70 data setelah seeder selesai, dengan memperhitungkan order item existing.

Strategi jumlah item:

- Hitung jumlah `order_items` existing terlebih dahulu.
- Hitung kekurangan menuju minimal 70 order item total.
- Buat 1 sampai 4 item untuk setiap order tambahan.
- Jika dengan sekitar 20 order tambahan total item belum mencapai 70, prioritaskan order tambahan dengan 3-4 item agar target total item tercapai tanpa menambah order melebihi target secara berlebihan.

Distribusi contoh jika database awal berisi 10 order dan perlu menambah sekitar 20 order:

- 3 order tambahan memiliki 1 item = 3 item.
- 5 order tambahan memiliki 2 item = 10 item.
- 6 order tambahan memiliki 3 item = 18 item.
- 6 order tambahan memiliki 4 item = 24 item.
- Total item tambahan = 55 item.

Distribusi lama untuk 30 order penuh hanya menjadi referensi jika database kosong:

- 5 order memiliki 1 item = 5 item.
- 8 order memiliki 2 item = 16 item.
- 9 order memiliki 3 item = 27 item.
- 8 order memiliki 4 item = 32 item.
- Total = 80 order item.

Strategi pemilihan produk:

- Gunakan hanya produk existing yang memenuhi kondisi: `is_active = 1`, `status = 'tersedia'`, `stock > 0`, dan `deleted_at IS NULL`.
- Produk existing hanya dipakai sebagai referensi order item.
- Jangan membuat produk baru.
- Jangan mengubah harga produk existing.
- Jangan mengubah stok produk existing.
- Rotasi 22 produk existing agar tidak semua order memakai produk yang sama.
- Produk dengan harga rendah-menengah lebih sering muncul untuk transaksi reguler.
- Produk dengan harga tinggi muncul pada sebagian kecil order agar variasi nominal terasa natural.
- Hindari duplikasi produk yang sama dalam satu order jika memungkinkan.
- Quantity dibuat bervariasi 1 sampai 3, dengan mayoritas quantity 1 untuk produk bernilai tinggi dan quantity 2-3 untuk produk bernilai lebih rendah.
- Simpan snapshot `product_name` dan `product_price` dari produk saat seeder berjalan.
- Hitung `order_items.subtotal` dari snapshot harga, bukan dari total order.

Catatan stok:

- Seeder tidak boleh mengurangi stok produk.
- Seeder tidak boleh menambah stok produk.
- Seeder tidak boleh mengubah status ketersediaan produk.
- Quantity pada order item hanya merepresentasikan snapshot transaksi dummy, bukan operasi inventory.

### 4. Strategi Pembuatan Payments

Tabel `payments` tersedia dan memiliki unique index pada `order_id`, sehingga maksimal satu payment per order.

Seeder hanya boleh membuat payment untuk order baru yang dibuat oleh seeder. Payment existing tidak boleh diubah atau dihapus.

Hubungan payment dengan status order:

- Order baru dengan status `belum_dibayar` tidak memiliki payment.
- Order baru dengan status `menunggu_verifikasi_pembayaran` memiliki payment dengan `payments.status = 'menunggu'`, `transfer_date` terisi, `proof_image` dapat terisi placeholder path jika format aplikasi mendukung, `verified_by` null, dan `verified_at` null.
- Order baru dengan status `diproses` memiliki payment valid dengan `payments.status = 'terverifikasi'`, `verified_by` user existing, dan `verified_at` terisi sebelum atau sekitar waktu perubahan status order.
- Order baru dengan status `dikirim` memiliki payment valid dengan `payments.status = 'terverifikasi'`.
- Order baru dengan status `selesai` memiliki payment valid dengan `payments.status = 'terverifikasi'`.
- Order baru dengan status `dibatalkan` perlu skenario realistis: sebagian dapat tanpa payment jika batal sebelum bayar, atau memiliki payment `ditolak` jika batal karena bukti pembayaran bermasalah.

Payment method:

- Gunakan `payment_methods` existing.
- Untuk order yang memiliki payment, isi `orders.payment_method_id` sama dengan `payments.payment_method_id`.
- Untuk order `belum_dibayar`, `orders.payment_method_id` boleh nullable, tetapi lebih realistis jika sebagian sudah memilih metode pembayaran walaupun belum transfer. Karena kolom nullable, pilihan implementasi harus konsisten dengan alur checkout aplikasi.

### 5. Strategi Perhitungan Nominal

Perhitungan item:

- Ambil `products.price` sebagai `order_items.product_price`.
- Jangan mengubah `products.price`.
- Tentukan `quantity` per item.
- Hitung `order_items.subtotal = product_price * quantity`.

Perhitungan order:

- `orders.subtotal = SUM(order_items.subtotal)`.
- `orders.discount_amount = 0` sebagai baseline aman, kecuali nanti diputuskan memakai promosi existing.
- `orders.shipping_weight_gram = SUM(products.weight * quantity)` jika data berat tersedia.
- `orders.shipping_cost` ditentukan sebagai ongkir final, bukan asal acak total. Strategi realistis:
  - Untuk tujuan Palembang atau sekitar, ongkir lebih rendah atau bisa memakai pengiriman internal.
  - Untuk luar kota/provinsi, ongkir lebih tinggi mengikuti berat total.
  - Gunakan variasi nominal seperti 15000, 25000, 35000, 50000, 75000, atau kelipatan realistis berdasarkan bobot.
- `orders.total_amount = orders.subtotal - orders.discount_amount + orders.shipping_cost`.
- `payments.amount = orders.total_amount` untuk payment `menunggu` dan `terverifikasi`.
- Untuk payment `ditolak`, `payments.amount` tetap dapat sama dengan `orders.total_amount` jika skenarionya bukti transfer salah/ditolak, bukan nominal kurang.

Kolom yang tidak ada:

- Jangan mengisi `total_price`.
- Jangan mengisi `grand_total`.
- Gunakan `orders.total_amount` sebagai total akhir transaksi.

### 6. Strategi Penggunaan Data Existing

#### Users

- Gunakan hanya user existing yang aktif (`is_active = 1`) dan memiliki role `pelanggan` sebagai pembeli order.
- Jangan membuat user baru.
- Jangan mengubah data user existing.
- Jangan memakai akun admin, super admin, owner, direktur, general manager, marketing, atau role internal lain sebagai pembeli.
- Jika tidak ditemukan user dengan role `pelanggan`, tampilkan peringatan dan hentikan proses implementasi.
- Distribusikan order tambahan secara proporsional ke seluruh user pelanggan yang tersedia.
- Usahakan setiap pelanggan memiliki minimal satu transaksi apabila jumlah pelanggan mencukupi.
- Variasikan jumlah transaksi per pelanggan agar tidak semua order berasal dari satu pelanggan.
- Untuk `payments.verified_by`, gunakan user existing yang berperan admin/pegawai jika tersedia.
- User admin/internal hanya boleh digunakan untuk `payments.verified_by`, `orders.cancelled_by`, dan `orders.stock_restored_by`.

#### Products

- Gunakan produk existing dari `products`.
- Filter produk: `is_active = 1`, `status = 'tersedia'`, `stock > 0`, `deleted_at IS NULL`.
- Jangan membuat produk baru.
- Jangan mengubah produk existing.
- Jangan mengubah harga produk existing.
- Jangan mengubah stok produk existing.
- Jangan memakai produk soft-deleted.
- Simpan snapshot nama dan harga produk ke order item agar data historis tidak berubah jika harga produk berubah di masa depan.

#### User Addresses

- Gunakan `user_addresses` existing sebagai sumber snapshot pengiriman.
- Jangan membuat alamat baru pada rencana ini.
- Jangan mengubah alamat existing.
- Jangan menghapus alamat existing.
- Karena `orders` tidak punya `user_address_id`, tidak ada relasi foreign key yang perlu diisi dari order ke address.
- Jika user yang dipilih punya address, gunakan address milik user tersebut.
- Jika user tidak punya address, gunakan data snapshot pengiriman langsung pada tabel `orders` tanpa membuat record baru pada `user_addresses`.
- Jangan menggunakan satu alamat yang sama untuk seluruh transaksi apabila terdapat alternatif data yang lebih realistis.
- Saat ini hanya ada 1 data `user_addresses`, sehingga variasi alamat pengiriman terbatas. Seeder tetap tidak boleh membuat, mengubah, atau menghapus alamat.

### 7. Strategi Realisme Data

Rencana agar transaksi terlihat seperti pelanggan sungguhan CV Sarana Fittindo:

- Variasikan jumlah produk per order, dengan target total `orders` sekitar 30 data dan total `order_items` minimal 70 data setelah memperhitungkan data existing.
- Distribusikan transaksi ke pelanggan secara proporsional dan hindari semua transaksi berasal dari satu pelanggan.
- Usahakan setiap pelanggan memiliki minimal satu order tambahan jika jumlah pelanggan mencukupi.
- Gabungkan produk berbeda dalam satu order, misalnya kombinasi produk utama dan pelengkap, bukan pemilihan produk yang sepenuhnya acak.
- Buat quantity rendah untuk barang mahal dan quantity sedikit lebih tinggi untuk barang yang lebih murah.
- Sebarkan order ke beberapa waktu transaksi, tidak semua dibuat pada tanggal yang sama.
- Buat status historis natural: order selesai lebih tua, dikirim lebih baru, diproses lebih baru lagi, menunggu pembayaran/verifikasi paling baru.
- Isi `shipped_at` hanya untuk status `dikirim` dan `selesai`.
- Isi `completed_at` hanya untuk status `selesai`.
- Isi `cancelled_at` dan `cancellation_reason` hanya untuk status `dibatalkan`.
- Gunakan variasi ongkir berdasarkan bobot dan kota, bukan angka yang sama untuk semua order.
- Isi shipping courier snapshot untuk order yang sudah diproses/dikirim/selesai, misalnya `shipping_courier_code`, `shipping_courier_name`, `shipping_service`, dan `shipping_etd`.
- Untuk order `belum_dibayar`, hindari data payment agar alur transaksi terlihat benar.
- Untuk order `menunggu_verifikasi_pembayaran`, payment ada tetapi belum diverifikasi.
- Untuk order `diproses`, `dikirim`, dan `selesai`, payment sudah terverifikasi.
- Hindari pattern berulang seperti order 1 selalu 1 item, order 2 selalu 2 item, atau status dikelompokkan seluruhnya tanpa variasi tanggal.

Keterbatasan realisme:

- Variasi alamat tidak bisa maksimal karena hanya ada 1 data `user_addresses` existing.
- Jika tidak membuat user/address tambahan, beberapa user order mungkin memakai snapshot alamat yang sama.

### 8. Risiko Implementasi

- Seeder yang memakai `truncate`, `delete`, `update`, atau sinkronisasi paksa akan melanggar prinsip append-only dan tidak boleh digunakan.
- Seeder yang memakai `destroy()`, `migrate:fresh`, atau `db:wipe` akan merusak data existing dan tidak boleh digunakan.
- Jika implementasi tidak menghitung data existing terlebih dahulu, seeder dapat menambah terlalu banyak order dan melewati target sekitar 30 data.
- Jika data existing sudah lebih dari 30 order, seeder harus berhenti tanpa menambah order baru.
- Jika tidak ada user role `pelanggan`, seeder harus berhenti; memakai user internal sebagai pembeli akan membuat data transaksi tidak valid secara bisnis.
- Jika distribusi pelanggan tidak diperhitungkan, semua order tambahan bisa terkumpul pada satu pelanggan dan terlihat tidak realistis.
- Status instruksi tidak sama persis dengan enum database. Wajib mapping `menunggu_pembayaran` -> `belum_dibayar` dan `menunggu_verifikasi` -> `menunggu_verifikasi_pembayaran`.
- Kolom `total_price` dan `grand_total` tidak ada. Wajib memakai `orders.total_amount`.
- Unique constraint `payments.order_id` membuat seeder gagal jika membuat payment ganda untuk satu order.
- `order_number` harus unik terhadap 10 order existing.
- Produk existing harus cukup untuk menghasilkan minimal 70 order item. Saat ini 22 produk tersedia, cukup untuk rotasi, tetapi implementasi harus tetap validasi sebelum insert.
- Jika filtering produk terlalu ketat dan hasilnya kosong, order item tidak bisa dibuat.
- Jika `payment_methods` kosong di environment lain, payment tidak bisa dibuat karena `payments.payment_method_id` wajib.
- Jika user role `pelanggan` kosong di environment lain, order tidak bisa dibuat karena `orders.user_id` wajib dan pembeli harus pelanggan.
- Jika alamat user kosong di environment lain, kolom shipping wajib pada orders tetap harus diisi; perlu strategi fallback yang disetujui.
- Jika alamat existing tidak memiliki `province_name`, `city_name`, atau `district_name`, field wajib shipping snapshot bisa kosong bila tidak ditangani.
- Jika seeder memakai role untuk mencari pelanggan/admin, environment tanpa data role yang lengkap dapat menyebabkan hasil query kosong.
- Jika menggunakan `Order::generateOrderNumber()` untuk data historis, nomor order bisa berbasis tanggal hari ini semua. Untuk data realistis, perlu strategi nomor order historis yang tetap unik.
- Jika discount/promotion diisi tanpa memastikan `promotion_id` valid, foreign key dapat gagal atau nominal menjadi tidak konsisten.
- Jika stok produk dikurangi, harga produk diubah, atau status produk diubah, implementasi melanggar batasan revisi. Produk harus read-only.
- Jika order `dibatalkan` tetap memiliki payment terverifikasi tanpa catatan refund/reason, data terlihat tidak sinkron.
- Jika `payments.amount` tidak sama dengan `orders.total_amount`, laporan pembayaran bisa terlihat salah.
- Jika `shipped_at`, `completed_at`, dan status order tidak sinkron, fitur auto-completion/laporan dapat salah membaca data.

## Tahap 3 - Validasi Sebelum Implementasi

Sebelum menulis kode seeder, keputusan yang perlu disetujui:

1. Gunakan mapping status instruksi ke enum aktual database:
   - `menunggu_pembayaran` menjadi `belum_dibayar`.
   - `menunggu_verifikasi` menjadi `menunggu_verifikasi_pembayaran`.
2. Gunakan `orders.total_amount` sebagai pengganti konsep total akhir/grand total karena tidak ada kolom `total_price` dan `grand_total`.
3. Seeder bersifat append-only: tidak menghapus, tidak truncate, dan tidak mengubah data existing.
4. Targetnya total `orders` database sekitar 30 data, bukan menambah 30 order baru.
5. Hitung order existing saat seeder berjalan, lalu tambahkan hanya kekurangannya.
6. Target total `order_items` minimal 70 data dengan memperhitungkan order item existing.
7. Gunakan produk existing hanya sebagai referensi `order_items`.
8. Jangan membuat produk baru.
9. Jangan membuat user baru.
10. Jangan mengubah harga produk existing.
11. Jangan mengubah stok produk existing.
12. Jangan mengubah status produk existing.
13. Tabel `products`, `users`, dan `user_addresses` bersifat read-only.
14. Gunakan hanya user existing dengan role `pelanggan` sebagai pembeli order.
15. Jangan memakai akun admin, super admin, owner, direktur, general manager, marketing, atau role internal lain sebagai pembeli.
16. User admin/internal hanya boleh digunakan untuk `payments.verified_by`, `orders.cancelled_by`, dan `orders.stock_restored_by`.
17. Jika tidak ditemukan user dengan role `pelanggan`, tampilkan peringatan dan hentikan proses implementasi.
18. Distribusikan transaksi secara proporsional ke seluruh user pelanggan yang tersedia.
19. Hindari seluruh transaksi berasal dari satu pelanggan.
20. Gunakan alamat dari `user_addresses` apabila tersedia untuk user pelanggan yang bersangkutan.
21. Jika user tidak memiliki alamat, isi snapshot pengiriman langsung pada `orders` tanpa membuat record baru di `user_addresses`.
22. Jangan membuat, mengubah, atau menghapus data `user_addresses`.
23. Buat payment hanya untuk order baru sesuai status order, maksimal satu payment per order.
24. Seeder hanya boleh menambahkan data baru pada `orders`, `order_items`, dan `payments`.
25. Dilarang menggunakan `truncate()`, `delete()`, `destroy()`, `update()` terhadap data existing, `migrate:fresh`, dan `db:wipe`.
26. Tidak mengubah struktur tabel.

Implementasi seeder baru boleh dilakukan setelah rencana ini disetujui.
