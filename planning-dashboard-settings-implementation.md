# Planning Implementasi Menu Settings Dashboard Marketing, GM, dan Direktur

## Ringkasan Kondisi Saat Ini

Pada dashboard non-admin, menu bawah sidebar masih memakai menu `PROFIL` yang mengarah ke route global:

- `resources/views/layouts/marketing/navigation.blade.php`
- `resources/views/layouts/gm/navigation.blade.php`
- `resources/views/layouts/direktur/navigation.blade.php`

Dashboard admin sudah memakai menu `PENGATURAN` dengan icon gear dan route:

- View menu: `resources/views/layouts/admin/navigation.blade.php`
- Route: `admin.settings.index`
- Controller: `app/Http/Controllers/Admin/SettingController.php`
- View halaman: `resources/views/admin/settings/index.blade.php`

Halaman settings admin berisi:

- Ringkasan profil user.
- Form update nama, email, telepon.
- Form ubah kata sandi.

Catatan penting:

- `ProfileController::update()` saat ini hanya mengenali redirect khusus ke `route('admin.settings.index')`.
- Route `/admin/settings` dibatasi middleware `role:admin`.
- Pada sidebar direktur saat ini sudah ada menu `PENGATURAN`, tetapi mengarah ke `admin.settings.index`. Karena route tersebut memakai middleware admin, direktur berpotensi tidak bisa membuka halaman itu.
- Direktur juga masih punya menu `PROFIL`, sehingga sidebar direktur menjadi duplikatif: profil dan pengaturan.

## Tujuan Perubahan

Ubah menu `PROFIL` pada dashboard marketing, GM, dan direktur menjadi menu `PENGATURAN` seperti dashboard admin.

Target perilaku:

1. Marketing melihat menu `PENGATURAN`, bukan `PROFIL`.
2. GM melihat menu `PENGATURAN`, bukan `PROFIL`.
3. Direktur hanya melihat satu menu `PENGATURAN`, bukan menu `PROFIL` plus menu pengaturan admin.
4. Setiap role membuka halaman settings miliknya sendiri, bukan route admin.
5. Halaman settings memiliki fungsi setara admin:
   - lihat data profil akun,
   - update profil,
   - ubah password.
6. Setelah update profil, user tetap kembali ke halaman settings sesuai role aktif.

## Scope Implementasi

Implementasi yang disarankan adalah membuat halaman settings per role dengan controller dan route masing-masing, tetapi tetap memakai satu partial/view reusable agar markup tidak diduplikasi terlalu banyak.

Role yang terdampak:

- `marketing`
- `gm`
- `direktur`

Role yang tidak diubah:

- `admin`, karena sudah punya settings.
- `pelanggan`, karena profil pelanggan memiliki kebutuhan alamat pengiriman dan berbeda dari dashboard internal.

## Rencana Route

Tambahkan route settings pada masing-masing group role di `routes/web.php`:

```php
Route::middleware(['auth', 'role:marketing'])->prefix('marketing')->name('marketing.')->group(function () {
    Route::get('/settings', [Marketing\SettingController::class, 'index'])->name('settings.index');
});

Route::middleware(['auth', 'role:gm'])->prefix('gm')->name('gm.')->group(function () {
    Route::get('/settings', [GM\SettingController::class, 'index'])->name('settings.index');
});

Route::middleware(['auth', 'role:direktur'])->prefix('direktur')->name('direktur.')->group(function () {
    Route::get('/settings', [Direktur\SettingController::class, 'index'])->name('settings.index');
});
```

Alasan:

- Menghindari marketing/GM/direktur memakai route admin.
- Active state sidebar bisa memakai namespace role masing-masing, misalnya `marketing.settings.*`.
- Middleware tetap eksplisit dan aman.

## Rencana Controller

Buat controller baru:

- `app/Http/Controllers/Marketing/SettingController.php`
- `app/Http/Controllers/GM/SettingController.php`
- `app/Http/Controllers/Direktur/SettingController.php`

Isi controller dapat mengikuti pola `Admin\SettingController`:

- Set `$pagePath` sesuai role:
  - `MARKETING/SETTINGS`
  - `GM/SETTINGS`
  - `DIREKTUR/SETTINGS`
- Set `$pageName = 'Pengaturan'`
- Load user dan role: `$request->user()->load('roles')`
- Return view role masing-masing atau shared view.

Contoh arah:

```php
return view('marketing.settings.index', compact('pagePath', 'pageName', 'user'));
```

## Rencana View Settings

Ada dua opsi:

### Opsi A: View per role dengan isi sederhana

Buat:

- `resources/views/marketing/settings/index.blade.php`
- `resources/views/gm/settings/index.blade.php`
- `resources/views/direktur/settings/index.blade.php`

Masing-masing memakai layout role:

- `<x-marketing-layout>` jika component tersedia, atau layout yang sudah dipakai dashboard marketing.
- `<x-gm-layout>` jika tersedia, atau layout yang sudah dipakai dashboard GM.
- `<x-direktur-layout>` jika tersedia, atau layout yang sudah dipakai dashboard direktur.

Jika komponen layout role belum ada, gunakan pola view dashboard existing pada role tersebut.

### Opsi B: Shared partial settings

Disarankan untuk mengurangi duplikasi:

Buat partial:

- `resources/views/settings/partials/account-settings.blade.php`

Partial menerima variabel:

- `$user`
- `$pagePath`
- `$pageName`
- `$redirectTo`

Lalu view per role hanya membungkus partial dengan layout role masing-masing.

Admin settings juga bisa tetap dibiarkan apa adanya untuk scope kecil, atau ikut dirapikan memakai partial yang sama jika ingin konsisten.

## Rencana Update Redirect Profile

File:

- `app/Http/Controllers/ProfileController.php`

Saat ini redirect khusus hanya mengecek:

```php
$request->input('redirect_to') === route('admin.settings.index')
```

Perlu diperluas agar menerima route settings untuk role internal lain:

- `admin.settings.index`
- `marketing.settings.index`
- `gm.settings.index`
- `direktur.settings.index`

Pendekatan aman:

1. Buat daftar route settings yang boleh menjadi tujuan redirect.
2. Cocokkan `redirect_to` dengan URL route tersebut.
3. Jika cocok, redirect ke URL itu dengan status `profile-updated`.
4. Jika tidak cocok, fallback ke `profile.edit`.

Contoh konsep:

```php
$allowedRedirects = collect([
    'admin.settings.index',
    'marketing.settings.index',
    'gm.settings.index',
    'direktur.settings.index',
])->filter(fn ($routeName) => Route::has($routeName))
  ->map(fn ($routeName) => route($routeName))
  ->all();
```

Catatan:

- Import `Illuminate\Support\Facades\Route` jika memakai `Route::has`.
- Jangan redirect bebas ke input user tanpa whitelist.

## Rencana Update Sidebar

### Marketing

File:

- `resources/views/layouts/marketing/navigation.blade.php`

Ubah item bawah:

- Icon dari `mdi:account-circle` menjadi `mdi:gear`.
- Label dari `PROFIL` menjadi `PENGATURAN`.
- Link dari `route('profile.edit')` menjadi `route('marketing.settings.index')`.
- Active state dari `request()->routeIs('profile.*')` menjadi `request()->routeIs('marketing.settings.*')`.

### GM

File:

- `resources/views/layouts/gm/navigation.blade.php`

Ubah item bawah:

- Icon `mdi:gear`.
- Label `PENGATURAN`.
- Link `route('gm.settings.index')`.
- Active state `request()->routeIs('gm.settings.*')`.

### Direktur

File:

- `resources/views/layouts/direktur/navigation.blade.php`

Ubah:

- Hapus menu `PROFIL`.
- Hapus atau ganti menu `PENGATURAN` yang saat ini mengarah ke `admin.settings.index`.
- Sisakan satu menu `PENGATURAN`:
  - Icon `mdi:gear`.
  - Link `route('direktur.settings.index')`.
  - Active state `request()->routeIs('direktur.settings.*')`.

## Rencana Test

Tambahkan feature test baru, misalnya:

- `tests/Feature/DashboardSettingsNavigationTest.php`

Skenario minimal:

1. User marketing bisa membuka `route('marketing.settings.index')`.
2. User GM bisa membuka `route('gm.settings.index')`.
3. User direktur bisa membuka `route('direktur.settings.index')`.
4. User marketing melihat label `PENGATURAN` di layout dan tidak melihat menu `PROFIL`.
5. User GM melihat label `PENGATURAN` di layout dan tidak melihat menu `PROFIL`.
6. User direktur melihat satu menu `PENGATURAN` dan tidak melihat menu `PROFIL`.
7. Update profil dari settings marketing redirect kembali ke `marketing.settings.index`.
8. Update profil dari settings GM redirect kembali ke `gm.settings.index`.
9. Update profil dari settings direktur redirect kembali ke `direktur.settings.index`.
10. Marketing/GM/direktur tidak bisa membuka `admin.settings.index`.

Jika test layout sulit karena markup berada di component/layout, cukup assert response halaman settings:

- `assertOk()`
- `assertSee('Pengaturan')`
- `assertSee('UPDATE PROFILE')`
- `assertSee('UBAH KATA SANDI')`

Lalu tambahkan test navigation terpisah jika route dashboard masing-masing render sidebar penuh.

## Validasi Manual

Setelah implementasi, jalankan:

```bash
php artisan route:list --name=settings
php artisan test --filter=DashboardSettingsNavigationTest
php artisan test --filter=ProfileTest
```

Validasi browser:

1. Login sebagai marketing.
2. Buka dashboard marketing.
3. Pastikan sidebar bawah menampilkan `PENGATURAN`.
4. Klik `PENGATURAN`, pastikan masuk ke `/marketing/settings`.
5. Update nama/telepon, pastikan kembali ke halaman settings marketing.
6. Ulangi untuk GM dan direktur.

## Urutan Implementasi

1. Tambahkan route settings untuk marketing, GM, dan direktur.
2. Buat controller settings untuk marketing, GM, dan direktur.
3. Buat view settings per role, idealnya memakai partial bersama.
4. Update `ProfileController::update()` agar redirect settings role internal lain diizinkan.
5. Update sidebar marketing: `PROFIL` menjadi `PENGATURAN`.
6. Update sidebar GM: `PROFIL` menjadi `PENGATURAN`.
7. Update sidebar direktur: hapus duplikasi dan gunakan route direktur settings.
8. Tambahkan feature test akses halaman settings dan redirect update profile.
9. Jalankan test terkait.
10. Jalankan `php artisan route:list --name=settings` untuk memastikan route tersedia.

## Catatan Lanjutan

Jika ingin konsistensi penuh, admin settings bisa ikut dipindahkan ke partial shared yang sama. Namun untuk scope permintaan saat ini, admin settings cukup dijadikan referensi dan tidak wajib diubah.

Pastikan menu `PENGATURAN` untuk role non-admin tidak memakai `admin.settings.index`, karena itu mencampur konteks role dan berpotensi gagal karena middleware admin.
