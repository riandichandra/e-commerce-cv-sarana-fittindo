# Planning Implementasi Penghapusan Sistem Slug

## Tujuan

Codebase tidak lagi menggunakan sistem `slug`. Semua bagian yang saat ini memakai `slug` harus dimigrasikan ke penggunaan `id` atau field non-slug yang sudah ada. Kolom `slug` pada tabel database harus dihapus, dan seluruh model, controller, view, seeder, serta test yang masih berhubungan dengan slug harus disesuaikan.

Dokumen ini dibuat berdasarkan:

- Analisis codebase langsung melalui pencarian pemakaian `slug`.
- Struktur migration database.
- Isi file `analisis-kolom-slug.md`.

## Ringkasan Kondisi Saat Ini

Tabel yang memiliki kolom `slug`:

1. `product_categories.slug`
2. `product_brands.slug`
3. `products.slug`

Status pemakaian saat ini:

- `product_categories.slug` aktif dipakai untuk filter kategori pelanggan.
- `product_brands.slug` hanya dibuat otomatis dan ditampilkan di admin.
- `products.slug` hanya dibuat otomatis, ditampilkan di admin, dan digunakan di seeder/test. Detail produk tetap memakai `id`.

Target akhir:

- Tidak ada kolom `slug` di `product_categories`, `product_brands`, dan `products`.
- Tidak ada `Str::slug()` untuk model produk/kategori/brand.
- Tidak ada `$model->slug` di view, seeder, test, atau controller.
- Filter kategori pelanggan memakai `category_id` melalui query string `category=<id>`.
- Route produk tetap memakai `id`, sesuai kondisi existing.

Catatan: `Str::slug()` di `Admin\PaymentMethodController` tidak termasuk sistem slug tabel karena dipakai untuk membentuk `payment_methods.code`. Bagian ini tidak perlu diubah kecuali ingin mengganti strategi kode payment method, yang berada di luar scope.

## Strategi Database

### Migration Baru

Buat migration baru, misalnya:

```text
database/migrations/YYYY_MM_DD_HHMMSS_drop_slug_columns_from_product_tables.php
```

Isi migration `up()`:

- Drop kolom `slug` dari `product_categories` jika kolom ada.
- Drop kolom `slug` dari `product_brands` jika kolom ada.
- Drop kolom `slug` dari `products` jika kolom ada.

Kolom `slug` di migration awal dibuat dengan `unique()`, sehingga saat drop column di MySQL biasanya index unique ikut terhapus. Namun implementasi tetap perlu memperhatikan compatibility database.

Rekomendasi implementasi aman:

```php
if (Schema::hasColumn('product_categories', 'slug')) {
    Schema::table('product_categories', function (Blueprint $table) {
        $table->dropColumn('slug');
    });
}
```

Lakukan pola yang sama untuk `product_brands` dan `products`.

Isi migration `down()`:

- Tambahkan kembali `slug` nullable terlebih dahulu agar rollback tidak gagal pada data existing.
- Isi nilai slug fallback berbasis `id` atau `name` jika rollback benar-benar dibutuhkan.
- Ubah kolom menjadi unique jika perlu.

Namun karena user sudah memutuskan tidak menggunakan slug, bagian `down()` bisa dibuat minimal untuk restore struktur saja. Pilihan paling stabil:

- Tambahkan kolom `slug` nullable dan unique nullable.
- Hindari regenerasi kompleks pada rollback.

## Strategi Codebase

## 1. Model

### `app/Models/ProductCategory.php`

Perubahan yang direncanakan:

- Hapus `slug` dari `$fillable`.
- Hapus `use Illuminate\Support\Str;`.
- Hapus method `boot()` yang membuat slug otomatis jika isinya hanya generate slug.

Sebelum:

```php
protected $fillable = ['name', 'slug', 'description', 'is_active'];
```

Sesudah:

```php
protected $fillable = ['name', 'description', 'is_active'];
```

### `app/Models/ProductBrand.php`

Perubahan yang direncanakan:

- Hapus `slug` dari `$fillable`.
- Hapus `use Illuminate\Support\Str;`.
- Hapus method `boot()` yang membuat slug otomatis jika isinya hanya generate slug.

### `app/Models/Product.php`

Perubahan yang direncanakan:

- Hapus `slug` dari `$fillable`.
- Hapus `use Illuminate\Support\Str;` jika tidak lagi digunakan.
- Ubah komentar `// Generate slug and keep status in sync` menjadi komentar yang hanya menjelaskan sinkronisasi stok/status, atau hapus komentar.
- Di event `saving`, hapus baris:

  ```php
  $product->slug = Str::slug($product->name);
  ```

- Pertahankan logic:

  ```php
  $product->stock = max(0, (int) $product->stock);
  $product->syncStatusFromStock();
  ```

## 2. Controller Pelanggan

### `app/Http/Controllers/Pelanggan/ProductController.php`

Saat ini filter kategori memakai slug:

```php
->where('slug', $request->string('category')->toString())
```

Rencana perubahan:

- Query string tetap dapat bernama `category`, tetapi nilainya menjadi `id` kategori.
- Gunakan validasi ringan dengan integer agar input non-id tidak memicu query tidak jelas.
- Filter menjadi:

```php
->when($request->filled('category'), function ($query) use ($request) {
    $categoryId = $request->integer('category');

    if ($categoryId > 0) {
        $query->whereHas('category', fn ($query) => $query
            ->active()
            ->whereKey($categoryId));
    }
})
```

Alternatif lebih eksplisit:

- Ubah nama query string dari `category` menjadi `category_id`.
- Namun ini membutuhkan perubahan lebih banyak pada view dan test. Untuk menjaga dampak kecil, gunakan `category=<id>`.

## 3. View Pelanggan

### `resources/views/pelanggan/dashboard.blade.php`

Saat ini link kategori memakai slug:

```php
route('pelanggan.products.index', ['category' => $category->slug])
```

Rencana perubahan:

```php
route('pelanggan.products.index', ['category' => $category->id])
```

### `resources/views/pelanggan/products/index.blade.php`

Saat ini checkbox kategori membandingkan request category dengan slug:

```php
@checked(request('category') === $category->slug)
```

Rencana perubahan:

```php
@checked((int) request('category') === $category->id)
```

URL filter kategori juga diganti dari slug ke id:

```php
$filterUrl(['category' => $category->id])
```

### `resources/views/pelanggan/products/show.blade.php`

Saat ini link kategori produk terkait memakai slug:

```php
route('pelanggan.products.index', ['category' => $product->category?->slug])
```

Rencana perubahan:

```php
route('pelanggan.products.index', ['category' => $product->category_id])
```

atau lebih defensif:

```php
route('pelanggan.products.index', ['category' => $product->category?->id])
```

## 4. View Admin

Slug saat ini hanya ditampilkan sebagai informasi. Karena kolom akan dihapus, semua tampilan slug harus dihapus.

### `resources/views/admin/categories/index.blade.php`

Hapus baris tampilan:

```php
{{ $category->slug }}
```

Ganti dengan informasi lain jika perlu, misalnya `ID: {{ $category->id }}` atau cukup hapus tanpa pengganti.

Rekomendasi: hapus tampilan slug, tidak perlu menampilkan ID kecuali UI membutuhkan identifier teknis.

### `resources/views/admin/brands/index.blade.php`

Hapus baris tampilan:

```php
{{ $brand->slug }}
```

### `resources/views/admin/products/index.blade.php`

Hapus tampilan:

```php
{{ $product->slug }}
```

Jika baris tersebut berada di bawah nama produk, ganti dengan kategori/brand/ID jika layout perlu tetap seimbang. Namun jangan memperkenalkan istilah slug lagi.

## 5. Seeder

### `database/seeders/CategorySeeder.php`

Saat ini seeder memakai slug sebagai key `updateOrInsert` dan migrasi legacy slug:

- `$legacySlugs`
- `where('slug', ...)`
- data kategori memiliki key `slug`
- `updateOrInsert(['slug' => ...])`

Rencana perubahan:

- Hapus seluruh blok `$legacySlugs` karena slug tidak lagi ada.
- Hapus key `slug` dari setiap data kategori.
- Gunakan `name` sebagai key stabil untuk `updateOrInsert`.

Contoh:

```php
DB::table('product_categories')->updateOrInsert(
    ['name' => $category['name']],
    $category
);
```

Catatan:

- Tidak ada unique index pada `name` di migration, tetapi controller admin memvalidasi nama kategori unique.
- Untuk seeder, `name` cukup masuk akal sebagai natural key.

### `database/seeders/DummyDataSeeder.php`

Saat ini kategori dipetakan menggunakan slug:

```php
$categories = ProductCategory::pluck('id', 'slug')->toArray();
```

Rencana perubahan:

- Ubah mapping kategori berdasarkan `name`.

```php
$categories = ProductCategory::pluck('id', 'name')->toArray();
```

- Hapus `slug` dari array `$items`.
- Ubah key `category` dari slug menjadi nama kategori sesuai data seeder, misalnya `HPL`, `Plywood`, `Pelapis`, `Perekat`.
- Ubah validasi `$missingCategories` agar pesan tetap jelas.
- Ubah `Product::updateOrCreate()` dari key slug ke key name.

Sebelum:

```php
Product::updateOrCreate([
    'slug' => $item['slug'],
], [...])
```

Sesudah:

```php
Product::updateOrCreate([
    'name' => $item['name'],
], [...])
```

Catatan:

- Controller admin produk sudah memvalidasi `products.name` unique.
- Migration `products.name` tidak unique, tetapi secara aplikasi nama produk dianggap unique.

## 6. Test

### `tests/Feature/PaginationTablesTest.php`

Saat ini test membuat kategori dan produk dengan field `slug`.

Rencana perubahan:

- Hapus `slug` saat create `ProductCategory`.
- Hapus `slug` saat create `Product`.
- Pastikan data test tetap unik melalui `name` jika diperlukan.

### `tests/Feature/Pelanggan/ProductIndexFilterTest.php`

Saat ini test memakai:

```php
'category' => $category->slug
$response->assertSee('category=' . $category->slug, false)
```

Rencana perubahan:

```php
'category' => $category->id
$response->assertSee('category=' . $category->id, false)
```

## 7. Migration Awal vs Migration Baru

Ada dua pendekatan:

### Opsi A: Buat Migration Baru Saja

Kelebihan:

- Aman untuk project yang database-nya sudah pernah migrate.
- Riwayat migration tetap append-only.

Kekurangan:

- Migration awal masih mencantumkan `slug`, lalu migration baru menghapusnya.

### Opsi B: Ubah Migration Awal dan Tambah Migration Baru

Kelebihan:

- Fresh install langsung tidak membuat kolom slug.
- Struktur migration awal selaras dengan keputusan final.

Kekurangan:

- Mengubah migration lama bisa berisiko jika migration tersebut sudah dianggap histori stabil.

Rekomendasi untuk codebase ini:

- Buat migration baru untuk menghapus kolom slug.
- Opsional: jika project masih tahap lokal dan belum ada database production, migration awal juga dapat dirapikan agar fresh migration tidak membuat lalu menghapus kolom slug. Namun keputusan ini sebaiknya eksplisit sebelum implementasi.

## 8. File yang Direncanakan Berubah

File produksi:

- `app/Models/Product.php`
- `app/Models/ProductBrand.php`
- `app/Models/ProductCategory.php`
- `app/Http/Controllers/Pelanggan/ProductController.php`
- `resources/views/pelanggan/dashboard.blade.php`
- `resources/views/pelanggan/products/index.blade.php`
- `resources/views/pelanggan/products/show.blade.php`
- `resources/views/admin/categories/index.blade.php`
- `resources/views/admin/brands/index.blade.php`
- `resources/views/admin/products/index.blade.php`
- `database/seeders/CategorySeeder.php`
- `database/seeders/DummyDataSeeder.php`
- migration baru drop slug columns

File test:

- `tests/Feature/PaginationTablesTest.php`
- `tests/Feature/Pelanggan/ProductIndexFilterTest.php`

Dokumentasi yang perlu diperbarui setelah implementasi:

- `analisis-kolom-slug.md` jika ingin mencerminkan kondisi setelah slug dihapus.

## 9. Urutan Implementasi yang Disarankan

1. Buat migration baru untuk drop `slug` dari `product_categories`, `product_brands`, dan `products`.
2. Hapus `slug` dan generator slug dari model `ProductCategory`, `ProductBrand`, dan `Product`.
3. Ubah filter kategori pelanggan dari slug ke id di `Pelanggan/ProductController`.
4. Ubah semua link/filter kategori pelanggan agar mengirim `category=<id>`.
5. Hapus tampilan slug di halaman admin kategori, brand, dan produk.
6. Ubah `CategorySeeder` agar memakai `name` sebagai key, bukan slug.
7. Ubah `DummyDataSeeder` agar mapping kategori memakai `name`, dan `Product::updateOrCreate()` memakai `name`.
8. Ubah test yang masih membuat atau mengecek slug.
9. Jalankan `rg "slug" app database resources routes tests -n` untuk memastikan tidak ada pemakaian slug tersisa, kecuali `Str::slug()` di `PaymentMethodController` jika tetap dipertahankan karena bukan kolom slug.
10. Jalankan `php -l` untuk file PHP yang berubah.
11. Jalankan test relevan:
    - `php artisan test tests\Feature\Pelanggan\ProductIndexFilterTest.php`
    - `php artisan test tests\Feature\Pelanggan\ProductDetailPageTest.php`
    - `php artisan test tests\Feature\Pelanggan\CheckoutTest.php`
    - `php artisan test tests\Feature\PaginationTablesTest.php`
12. Jika memungkinkan, jalankan full `php artisan test`. Catat jika ada failure existing yang tidak terkait.

## 10. Risiko dan Mitigasi

### Risiko: URL filter kategori lama tidak berlaku

Sebelumnya URL seperti ini valid:

```text
/pelanggan/products?category=hpl
```

Setelah perubahan, URL menjadi:

```text
/pelanggan/products?category=1
```

Mitigasi:

- Karena user meminta tidak menggunakan slug, perubahan ini memang diharapkan.
- Jika ingin transisi kompatibel sementara, controller bisa menerima nilai numeric saja dan mengabaikan nilai string lama.

### Risiko: Seeder duplikat jika nama berubah

Seeder akan memakai `name` sebagai key. Jika nama kategori/produk diubah manual di database, seeder bisa membuat data baru.

Mitigasi:

- Gunakan nama kategori/produk sebagai data master yang stabil.
- Jika perlu stabilitas lebih tinggi, gunakan daftar id eksplisit di seeder, tetapi itu lebih berisiko terhadap auto increment existing.

### Risiko: Migration rollback tidak mengembalikan slug seperti semula

Karena slug dihapus sebagai keputusan desain, rollback tidak perlu menjaga nilai slug lama.

Mitigasi:

- `down()` cukup mengembalikan kolom nullable jika dibutuhkan struktur rollback.
- Tidak perlu mengisi ulang slug lama.

### Risiko: Pencarian `slug` masih menemukan `Str::slug()` di PaymentMethodController

Itu bukan sistem slug tabel. Nama method memang `Str::slug()`, tetapi outputnya adalah `payment_methods.code`.

Mitigasi:

- Biarkan jika scope hanya menghapus kolom slug produk/kategori/brand.
- Jika user ingin benar-benar tidak ada kata `slug` sama sekali di codebase, bagian payment method perlu desain kode baru. Saat ini tidak direkomendasikan karena tidak terkait kolom slug.

## 11. Definition of Done

Implementasi dianggap selesai jika:

- Kolom `slug` tidak ada lagi di tabel `product_categories`, `product_brands`, dan `products` setelah migration dijalankan.
- Model produk/kategori/brand tidak mengisi slug otomatis.
- View admin tidak menampilkan slug.
- Filter kategori pelanggan memakai id.
- Seeder tidak memakai slug sebagai key atau data.
- Test tidak membuat atau mengecek field slug.
- Pencarian `rg "slug" app database resources routes tests -n` tidak menemukan pemakaian slug terkait produk/kategori/brand.
- Test relevan lulus.
