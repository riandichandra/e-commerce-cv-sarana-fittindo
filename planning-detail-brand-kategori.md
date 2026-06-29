# Planning Fitur Detail Brand dan Detail Kategori Produk

## Tujuan

Menambahkan halaman detail brand dan detail kategori yang bisa diakses pelanggan dari halaman detail produk. Alur utama yang ditargetkan:

- Pelanggan membuka halaman detail produk.
- Pada detail produk, nama brand menjadi link menuju halaman detail brand produk tersebut.
- Pada detail produk, nama kategori menjadi link menuju halaman detail kategori produk tersebut.
- Halaman detail brand menampilkan informasi brand dan daftar produk aktif dari brand tersebut.
- Halaman detail kategori menampilkan informasi kategori dan daftar produk aktif dari kategori tersebut.

Fitur ini harus mengikuti aturan katalog pelanggan yang sudah ada: produk hanya boleh tampil jika produk aktif, kategori aktif, dan brand aktif jika produk memiliki brand.

## Analisis Struktur Database

### `product_categories`

Migration utama: `database/migrations/2026_04_30_141255_create_product_categories_table.php`

Kolom saat ini:

- `id`
- `name` maksimal 100 karakter
- `description` nullable
- `is_active` boolean default `true`
- `created_at`, `updated_at`

Relasi:

- Satu kategori memiliki banyak produk melalui `products.category_id`.
- Produk wajib memiliki kategori.
- Foreign key di `products.category_id` memakai `constrained('product_categories')->onDelete('restrict')`.

Implikasi untuk fitur:

- Halaman detail kategori bisa memakai route model binding berdasarkan `id`.
- Kategori nonaktif sebaiknya tidak bisa diakses pelanggan dan harus menghasilkan 404.
- Daftar produk pada halaman kategori harus memakai scope katalog pelanggan, bukan sekadar relasi `products()` biasa.

### `product_brands`

Migration utama: `database/migrations/2026_04_30_141257_create_product_brands_table.php`

Kolom saat ini:

- `id`
- `name` maksimal 100 karakter
- `description` nullable
- `logo` nullable
- `is_active` boolean default `true`
- `created_at`, `updated_at`

Relasi:

- Satu brand memiliki banyak produk melalui `products.brand_id`.
- Produk boleh tidak memiliki brand.
- Foreign key di `products.brand_id` nullable dan memakai `onDelete('set null')`.

Implikasi untuk fitur:

- Halaman detail brand bisa memakai route model binding berdasarkan `id`.
- Brand nonaktif sebaiknya tidak bisa diakses pelanggan dan harus menghasilkan 404.
- Produk tanpa brand tidak memiliki link detail brand.
- Kolom `logo` tersedia di database, tetapi `ProductBrand::$fillable` saat ini hanya memuat `name`, `description`, dan `is_active`. Jika logo ingin ditampilkan atau dikelola, perlu perubahan lanjutan di admin form/model. Untuk scope fitur awal, logo bisa diperlakukan opsional dan hanya ditampilkan jika data sudah ada.

### `products`

Migration utama: `database/migrations/2026_04_30_141259_create_products_table.php`

Kolom terkait fitur:

- `id`
- `category_id`
- `brand_id` nullable
- `name`
- `description`
- `price`
- `stock`
- `status`
- `weight`
- `thickness`
- `dimensions`
- `specifications`
- `is_featured`
- `is_active`
- `deleted_at`

Relasi model saat ini:

- `Product::category()` menuju `ProductCategory`.
- `Product::brand()` menuju `ProductBrand`.
- `Product::images()` menuju `ProductImage`.

Scope penting saat ini di `app/Models/Product.php`:

- `scopeActive()` memfilter `products.is_active`.
- `scopeVisibleToCustomers()` sudah memfilter produk aktif, kategori aktif, dan brand aktif jika ada.
- `scopeAvailable()` sudah berbasis produk visible dan stok/status tersedia.
- `isVisibleToCustomers()` menjadi guard instance untuk detail produk.

Implikasi untuk fitur:

- Query daftar produk di detail brand/kategori harus memakai `Product::visibleToCustomers()` atau relasi yang diberi constraint setara.
- Detail brand/kategori tidak membutuhkan migration baru karena data yang dibutuhkan sudah tersedia.
- Tidak perlu mengembalikan sistem slug karena codebase saat ini sudah diarahkan memakai `id`.

## Analisis Codebase Saat Ini

### Route Pelanggan

File: `routes/web.php`

Route publik pelanggan saat ini:

```php
Route::prefix('pelanggan')->name('pelanggan.')->group(function () {
    Route::get('/', [Pelanggan\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('products', Pelanggan\ProductController::class)->only(['index', 'show']);
});
```

Kondisi:

- Katalog dan detail produk bisa diakses publik lewat prefix `/pelanggan`.
- Detail produk memakai `Route::resource('products', ...)->only(['index', 'show'])`, sehingga URL detail produk berbasis id: `/pelanggan/products/{product}`.
- Belum ada route publik pelanggan untuk detail brand dan detail kategori.

Rekomendasi:

- Tambahkan route publik di grup yang sama agar aksesnya konsisten dengan katalog produk.
- Gunakan route eksplisit, misalnya:

```php
Route::get('brands/{brand}', [Pelanggan\BrandController::class, 'show'])->name('brands.show');
Route::get('categories/{category}', [Pelanggan\CategoryController::class, 'show'])->name('categories.show');
```

Alternatif:

- Gunakan nama controller yang lebih spesifik seperti `ProductBrandController` dan `ProductCategoryController` pada namespace pelanggan.
- Namun karena namespace admin sudah punya controller dengan nama tersebut, nama `BrandController` dan `CategoryController` di `App\Http\Controllers\Pelanggan` lebih ringkas dan tetap jelas.

### Controller Produk Pelanggan

File: `app/Http/Controllers/Pelanggan/ProductController.php`

Kondisi:

- `index()` memakai `Product::visibleToCustomers()` dengan eager loading `category`, `brand`, dan `images`.
- Filter kategori sudah memakai `category=<id>` dan memastikan kategori aktif.
- `show(Product $product)` melakukan `$product->load(['category', 'brand', 'images'])` lalu `abort_unless($product->isVisibleToCustomers(), 404)`.
- Produk terkait memakai kategori produk saat ini dan `Product::visibleToCustomers()`.

Implikasi:

- Halaman detail produk sudah aman sebagai sumber link ke detail brand/kategori.
- Perubahan utama di controller produk hanya perlu menyiapkan URL di view; tidak perlu mengubah query `show()` kecuali ingin eager load tambahan.

### Model Brand dan Kategori

File: `app/Models/ProductBrand.php`

Kondisi:

- `$fillable = ['name', 'description', 'is_active']`.
- Cast `is_active` ke boolean.
- Relasi `products()` sudah tersedia.
- Scope `active()` sudah tersedia.

File: `app/Models/ProductCategory.php`

Kondisi:

- `$fillable = ['name', 'description', 'is_active']`.
- Cast `is_active` ke boolean.
- Relasi `products()` sudah tersedia.
- Scope `active()` sudah tersedia.

Implikasi:

- Detail brand/kategori bisa langsung memakai model existing.
- Tidak perlu perubahan database atau relasi dasar.
- Bisa menambahkan helper/scope khusus jika diperlukan, tetapi untuk fitur awal cukup gunakan scope existing dan query produk dari `Product::visibleToCustomers()`.

### View Detail Produk

File: `resources/views/pelanggan/products/show.blade.php`

Kondisi:

- Bagian badge atas menampilkan kategori dan brand sebagai teks biasa.
- Bagian `Detail Produk` menampilkan `Kategori` dan `Merek` dari array `$detailRows`, juga sebagai teks biasa.
- Link produk terkait ke daftar kategori masih memakai `route('pelanggan.products.index', ['category' => $product->category_id])`.

Rekomendasi perubahan:

- Ubah badge kategori menjadi link ke `pelanggan.categories.show` jika kategori tersedia.
- Ubah badge brand menjadi link ke `pelanggan.brands.show` jika brand tersedia.
- Pada bagian `Detail Produk`, pertimbangkan render khusus untuk baris `Kategori` dan `Merek` agar nilainya juga menjadi link, bukan hanya badge.
- Jika ingin menjaga perubahan kecil, cukup jadikan badge kategori/brand sebagai link utama dan pertahankan detail rows sebagai teks.

Rekomendasi UX:

- Link kategori/brand harus terlihat sebagai elemen yang bisa diklik, misalnya hover color atau underline halus.
- Untuk produk tanpa brand, tampilkan `-` atau `Tanpa Merek` tanpa link.

## Rancangan Implementasi

## 1. Tambah Route Publik Pelanggan

File: `routes/web.php`

Tambahkan route sebelum/bersama route resource produk di grup publik pelanggan:

```php
Route::get('brands/{brand}', [Pelanggan\BrandController::class, 'show'])->name('brands.show');
Route::get('categories/{category}', [Pelanggan\CategoryController::class, 'show'])->name('categories.show');
```

Catatan urutan:

- Route `brands/{brand}` dan `categories/{category}` tidak konflik dengan `products/{product}` karena path prefix berbeda.
- Tetap letakkan di public customer product routes agar bisa diakses tanpa login seperti detail produk.

## 2. Buat Controller Detail Brand Pelanggan

File baru: `app/Http/Controllers/Pelanggan/BrandController.php`

Tanggung jawab:

- Menerima `ProductBrand $brand` lewat route model binding.
- Abort 404 jika brand tidak aktif.
- Mengambil daftar produk visible dari brand tersebut.
- Eager load `category`, `brand`, dan `images` untuk kebutuhan card produk.
- Paginate daftar produk agar halaman tetap ringan.
- Return view `pelanggan.brands.show`.

Contoh struktur query:

```php
public function show(ProductBrand $brand)
{
    abort_unless($brand->is_active, 404);

    $products = Product::visibleToCustomers()
        ->with(['category', 'brand', 'images'])
        ->where('brand_id', $brand->id)
        ->latest()
        ->paginate(12);

    return view('pelanggan.brands.show', compact('brand', 'products'));
}
```

Catatan:

- Query ini memakai `Product::visibleToCustomers()` agar produk dari kategori nonaktif tetap tidak tampil walaupun brand aktif.
- Karena brand sudah di-guard aktif, `visibleToCustomers()` tetap menjadi lapisan konsistensi tambahan.

## 3. Buat Controller Detail Kategori Pelanggan

File baru: `app/Http/Controllers/Pelanggan/CategoryController.php`

Tanggung jawab:

- Menerima `ProductCategory $category` lewat route model binding.
- Abort 404 jika kategori tidak aktif.
- Mengambil daftar produk visible dari kategori tersebut.
- Eager load `category`, `brand`, dan `images`.
- Paginate daftar produk.
- Return view `pelanggan.categories.show`.

Contoh struktur query:

```php
public function show(ProductCategory $category)
{
    abort_unless($category->is_active, 404);

    $products = Product::visibleToCustomers()
        ->with(['category', 'brand', 'images'])
        ->where('category_id', $category->id)
        ->latest()
        ->paginate(12);

    return view('pelanggan.categories.show', compact('category', 'products'));
}
```

Catatan:

- Query ini tetap memakai `visibleToCustomers()` agar produk dengan brand nonaktif tidak tampil di detail kategori.
- Produk tanpa brand tetap tampil selama produk dan kategorinya aktif.

## 4. Buat View Detail Brand

File baru: `resources/views/pelanggan/brands/show.blade.php`

Konten yang disarankan:

- Breadcrumb: Beranda / Produk / Brand / Nama Brand.
- Header brand:
  - Nama brand.
  - Deskripsi brand jika ada.
  - Logo jika tersedia dan file benar-benar bisa dirender, jika tidak tampilkan fallback sederhana.
- Ringkasan jumlah produk: gunakan `$products->total()`.
- Grid produk dengan pola card yang konsisten dengan `resources/views/pelanggan/products/index.blade.php` atau card produk terkait di `show.blade.php`.
- Empty state jika belum ada produk aktif untuk brand tersebut.
- Pagination `$products->links()`.

Pertimbangan reuse:

- Saat ini card produk pelanggan berulang di index produk dan detail produk terkait.
- Untuk mengurangi duplikasi, bisa dibuat partial seperti `resources/views/pelanggan/products/partials/product-card.blade.php`.
- Namun jika ingin scope kecil untuk tahap pertama, view detail brand bisa menyalin pola card existing dengan penyesuaian minimal.

## 5. Buat View Detail Kategori

File baru: `resources/views/pelanggan/categories/show.blade.php`

Konten yang disarankan:

- Breadcrumb: Beranda / Produk / Kategori / Nama Kategori.
- Header kategori:
  - Nama kategori.
  - Deskripsi kategori jika ada.
- Ringkasan jumlah produk: gunakan `$products->total()`.
- Grid produk dari kategori tersebut.
- Empty state jika belum ada produk aktif untuk kategori tersebut.
- Pagination `$products->links()`.

Pertimbangan UX:

- Detail kategori dapat menyediakan link kembali ke katalog semua produk.
- Bisa juga menyediakan link ke katalog dengan filter kategori existing: `route('pelanggan.products.index', ['category' => $category->id])`. Namun halaman detail kategori sudah berfungsi sebagai landing untuk kategori tersebut, jadi link ini opsional.

## 6. Ubah Link pada Detail Produk

File: `resources/views/pelanggan/products/show.blade.php`

Perubahan utama:

- Badge kategori:

```php
@if ($product->category)
    <a href="{{ route('pelanggan.categories.show', $product->category) }}" ...>
        {{ $product->category->name }}
    </a>
@else
    <span ...>Tanpa Kategori</span>
@endif
```

- Badge brand:

```php
@if ($product->brand)
    <a href="{{ route('pelanggan.brands.show', $product->brand) }}" ...>
        {{ $product->brand->name }}
    </a>
@endif
```

Opsional:

- Ubah bagian `Detail Produk` agar baris `Kategori` dan `Merek` menjadi link juga.
- Karena `$detailRows` saat ini menyimpan string sederhana, implementasi link di sana lebih mudah jika baris kategori/merek dirender terpisah atau `$detailRows` diubah menjadi struktur array dengan `label`, `value`, dan `url`.

Rekomendasi praktis:

- Tahap pertama: link pada badge sudah memenuhi alur user dari detail produk ke detail brand/kategori.
- Tahap kedua jika diminta: rapikan `Detail Produk` agar kategori/merek juga clickable.

## 7. Pertimbangan Route Model Binding dan Data Nonaktif

Aturan akses pelanggan:

- Brand nonaktif: `GET /pelanggan/brands/{brand}` harus 404.
- Kategori nonaktif: `GET /pelanggan/categories/{category}` harus 404.
- Produk nonaktif atau produk dari kategori/brand nonaktif tidak boleh tampil di daftar detail brand/kategori.
- Produk tanpa brand tidak punya halaman brand dan tidak perlu link brand.

Alasan memakai 404:

- Konsisten dengan `Pelanggan\ProductController::show()` yang memakai 404 untuk produk yang tidak visible.
- Mencegah pelanggan melihat entitas katalog yang sengaja dinonaktifkan admin.

## 8. Pengujian yang Perlu Ditambahkan

Rekomendasi file test baru:

- `tests/Feature/Pelanggan/BrandDetailPageTest.php`
- `tests/Feature/Pelanggan/CategoryDetailPageTest.php`

Skenario brand:

- Halaman detail brand aktif menampilkan nama brand, deskripsi brand, dan produk dari brand tersebut.
- Halaman detail brand tidak menampilkan produk nonaktif.
- Halaman detail brand tidak menampilkan produk dari kategori nonaktif.
- Brand nonaktif menghasilkan 404.
- Link detail brand tampil di halaman detail produk jika produk punya brand.
- Link detail brand tidak tampil untuk produk tanpa brand.

Skenario kategori:

- Halaman detail kategori aktif menampilkan nama kategori, deskripsi kategori, dan produk dari kategori tersebut.
- Halaman detail kategori tidak menampilkan produk nonaktif.
- Halaman detail kategori tidak menampilkan produk dengan brand nonaktif.
- Kategori nonaktif menghasilkan 404.
- Link detail kategori tampil di halaman detail produk.

Skenario regresi detail produk:

- Detail produk tetap 200 untuk produk visible.
- Response detail produk mengandung URL `route('pelanggan.categories.show', $product->category)`.
- Response detail produk mengandung URL `route('pelanggan.brands.show', $product->brand)` jika brand ada.

Command verifikasi yang disarankan:

```bash
php artisan test --filter=ProductDetailPageTest
php artisan test --filter=BrandDetailPageTest
php artisan test --filter=CategoryDetailPageTest
```

Jika perubahan menyentuh card produk yang dipakai luas, jalankan juga:

```bash
php artisan test --filter=ProductIndexFilterTest
```

## Rencana Urutan Implementasi

1. Tambahkan route publik pelanggan untuk detail brand dan detail kategori.
2. Buat `App\Http\Controllers\Pelanggan\BrandController`.
3. Buat `App\Http\Controllers\Pelanggan\CategoryController`.
4. Buat view `resources/views/pelanggan/brands/show.blade.php`.
5. Buat view `resources/views/pelanggan/categories/show.blade.php`.
6. Ubah badge kategori dan brand di `resources/views/pelanggan/products/show.blade.php` menjadi link.
7. Tambahkan feature test untuk halaman detail brand dan kategori.
8. Perbarui test detail produk agar memverifikasi link baru.
9. Jalankan test terkait dan perbaiki regresi jika ada.

## Risiko dan Catatan Implementasi

- Duplikasi card produk bisa bertambah jika view brand/kategori membuat markup sendiri. Jika waktu cukup, buat partial card produk pelanggan agar index, detail brand, dan detail kategori lebih mudah dirawat.
- Kolom `product_brands.logo` ada di database tetapi belum dikelola oleh admin controller saat ini. Jangan menjadikan logo sebagai kebutuhan wajib untuk fitur ini.
- Jangan memakai relasi `$brand->products()` atau `$category->products()` tanpa constraint `visibleToCustomers()`, karena itu bisa membocorkan produk nonaktif atau produk dari relasi nonaktif.
- Jangan menambahkan migration slug baru. Codebase saat ini sudah bergerak ke route berbasis `id`.
- Detail kategori dan brand sebaiknya publik seperti katalog produk, bukan di dalam middleware auth pelanggan.

## Kriteria Selesai

- Dari detail produk, pelanggan bisa klik kategori menuju halaman detail kategori.
- Dari detail produk, pelanggan bisa klik brand menuju halaman detail brand jika produk punya brand.
- Halaman detail kategori menampilkan info kategori dan daftar produk visible dari kategori tersebut.
- Halaman detail brand menampilkan info brand dan daftar produk visible dari brand tersebut.
- Entitas nonaktif menghasilkan 404 untuk pelanggan.
- Produk nonaktif, produk dari kategori nonaktif, dan produk dengan brand nonaktif tidak tampil pada halaman detail brand/kategori.
- Test fitur terkait lulus.
