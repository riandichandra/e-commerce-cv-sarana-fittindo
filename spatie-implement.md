# Spatie Implementation Plan

## 1. Current mismatch analysis

### 1.1. Model inconsistencies
- `app/Models/User.php`
  - `use Spatie\Permission\Traits\HasRoles;` sudah dipakai.
  - Namun ada property `role_id` di `fillable`.
  - Ada custom relation `role()` ke `App\Models\Role`.
  - Ada custom method `hasRole($role)` yang menimpa logika Spatie.
  - Ada helper `isAdmin()` dan `redirectBasedOnRole()` yang bergantung pada `role_id`.

- `app/Models/Role.php`
  - Model ini adalah `Illuminate\Database\Eloquent\Model` biasa.
  - Tidak meng-extend Spatie `Role` model, sehingga tidak konsisten dengan package.

### 1.2. Database/schema mismatches
- `database/migrations/0001_01_01_000000_create_users_table.php` tidak membuat kolom `role_id`.
- `config/permission.php` mengonfigurasi Spatie tables (`roles`, `model_has_roles`, dsb.).
- Ada migrasi Spatie `create_permission_tables` namun app masih menggunakan `users.role_id` di banyak tempat.

### 1.3. Controller / input / views yang masih manual
- `app/Http/Controllers/Auth/RegisteredUserController.php`
  - membuat user dengan `role_id => 1`.

- `app/Http/Controllers/Admin/UserController.php`
  - validasi `role_id`.
  - membuat / mengupdate user dengan `role_id`.
  - menampilkan role via `Role::with(['users' => fn ($query) => $query->latest()])`.

- `resources/views/...`
  - banyak menggunakan `Auth::user()->role_id`.
  - form create/edit user menggunakan `role_id` selector.

- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
  - `dd($user->hasRole('admin'));`
  - login redirect masih memeriksa role via sama.

- `app/Http/Middleware/CheckRole.php`
  - mungkin menggunakan `hasRole()` custom, tapi masih belum konsisten.

### 1.4. Seeder / factories
- `database/seeders/UserSeeder.php` sudah menggunakan `assignRole('admin')` dll.
- Namun tidak mensinkronkan `role_id`.
- `database/factories/UserFactory.php` masih mengisi `role_id`.

## 2. Objective

Beralih penuh ke Laravel Spatie role management dengan:
- satu source of truth isi role di pivot `model_has_roles`.
- tidak lagi memakai `users.role_id` sebagai penentu role.
- memastikan `User::hasRole()` dan `assignRole()` memakai Spatie built-in.
- menggunakan Spatie `Role` model atau extend custom `App\Models\Role` jika ingin menambah field.

## 3. Recommended implementation plan

### 3.1. Phase 1: Konsolidasi model
1. Ubah `app/Models/Role.php` menjadi alias / extend Spatie Role jika ingin tetap gunakan `App\Models\Role`.
   - Contoh:
     ```php
     namespace App\Models;

     use Spatie\Permission\Models\Role as SpatieRole;

     class Role extends SpatieRole
     {
         protected $fillable = [...];
     }
     ```
2. Hapus property `role_id` dari `User::$fillable` dan hapus relasi `role()` jika tidak diperlukan.
3. Hapus custom `hasRole($role)` dari `User.php`.
4. Ganti `isAdmin()` dan `redirectBasedOnRole()` agar menggunakan Spatie role methods atau `getRoleNames()`.

### 3.2. Phase 2: Update database and seeder behavior
1. Jika ingin sepenuhnya pakai Spatie, hapus penggunaan `role_id` pada tabel users.
   - Bila sudah ada kolom, hapus dari migration / migration baru.
2. Ubah `database/seeders/UserSeeder.php` untuk memastikan setiap user mendapat role via `assignRole()`.
3. Hapus `role_id` dari `database/factories/UserFactory.php`.
4. Jika masih ingin data existing, tambahkan migration untuk memigrasi nilai `role_id` lama ke role pivot, atau hapus kolom setelah migrasi selesai.

### 3.3. Phase 3: Update controller logic
1. `app/Http/Controllers/Auth/RegisteredUserController.php`
   - buat user tanpa `role_id`.
   - beri role default dengan `$user->assignRole('pelanggan')` setelah `User::create(...)`.
2. `app/Http/Controllers/Admin/UserController.php`
   - validasi input role dengan `role` / `role_name` bukan `role_id`.
   - jika ingin tetap menampilkan dropdown role, gunakan role name sebagai value.
   - simpan role dengan `$user->syncRoles($request->role)` atau `$user->assignRole(...)`.
   - hapus query `Role::with(['users' => ...])` jika relasi user-role harus via pivot. Gunakan Spatie Role model relasi `users` jika extend Spatie.

### 3.4. Phase 4: Update views
1. Ganti semua `Auth::user()->role_id` ke `Auth::user()->getRoleNames()->first()` atau helper `->hasRole()`.
2. Form `admin.users.partials.form` gunakan `role`/`role_name` sebagai input.
3. Jika listing user perlu role, tampilkan `{{ $user->getRoleNames()->first() }}`.

### 3.5. Phase 5: Routes / middleware
1. Pastikan middleware role Spatie dipakai di route group.
2. Jika masih pakai `CheckRole` custom, verifikasi bahwa method `hasRole()` yang dipanggil adalah Spatie dan tidak override.
3. Pastikan route redirect login memakai Spatie-compatible role checks.

### 3.6. Phase 6: Testing dan verifikasi
1. Jalankan unit/feature tests yang berkaitan role.
2. Tambahkan test untuk:
   - register user otomatis diberi role `pelanggan`.
   - admin create user dengan role Spatie.
   - login redirect berdasarkan Spatie role.
   - middleware `role:admin` bekerja.
3. Verifikasi database tabel Spatie (`roles`, `model_has_roles`, `permissions`, `role_has_permissions`) ada dan terisi.

## 4. Specific files to update
- `app/Models/User.php`
- `app/Models/Role.php`
- `app/Http/Controllers/Auth/RegisteredUserController.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `app/Http/Controllers/Admin/UserController.php`
- `app/Http/Middleware/CheckRole.php`
- `resources/views/admin/users/partials/form.blade.php`
- `resources/views/layouts/admin/navigation.blade.php` dan view lain yang memakai `role_id`
- `database/factories/UserFactory.php`
- `database/seeders/UserSeeder.php`
- Migrations terkait `users` / `roles` / `model_has_roles`

## 5. High-level target
- Hilangkan `users.role_id` dependency.
- Gunakan Spatie role pivot semata.
- Pastikan `User::hasRole()` dan `assignRole()` bekerja konsisten.
- Bikin model `App\Models\Role` atau gunakan `Spatie\Permission\Models\Role` secara konsisten.
- Update UI dan form input agar tidak lagi melempar `role_id`.
