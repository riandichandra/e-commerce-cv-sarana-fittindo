# Email Verification Bug - Planning Document

## Problem Summary
Saat ini, pengecekan email verification diterapkan ke **SEMUA user roles** (admin, marketing, gm, direktur, pelanggan). Seharusnya, email verification hanya berlaku untuk **akun pelanggan (customer)** saja. Akun internal (admin, marketing, gm, direktur) tidak perlu pengecekan email verification saat login.

## Impact
- Admin, marketing, gm, dan direktur tidak bisa login jika email mereka belum diverifikasi
- Registrasi user non-customer belum ada workflow, tetapi logic login akan memblokir mereka
- User experience tidak optimal karena alur verifikasi email bercampur untuk semua tipe user

---

## Root Cause Analysis

### 1. LoginRequest.php - Baris 54-59
**File:** `app/Http/Requests/Auth/LoginRequest.php`
```php
if (! Auth::user()?->hasVerifiedEmail()) {
    Auth::guard()->logout();
    throw ValidationException::withMessages([
        'email' => 'Your email address is not verified. Please check your inbox for a verification link.',
    ]);
}
```
**Masalah:** Pengecekan ini berlaku untuk semua user, tanpa mempertimbangkan role mereka.

### 2. Web Routes - Email Verification Middleware
**File:** `routes/web.php` - Baris 28, 38, 48, 55
```php
Route::middleware(['auth', 'role:pelanggan', 'verified'])->prefix('pelanggan')->name('pelanggan.')->group(function () {
    // Pelanggan routes
});

Route::middleware(['auth', 'role:admin', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    // Admin routes
});

Route::middleware(['auth', 'role:marketing', 'verified'])->prefix('marketing')->name('marketing.')->group(function () {
    // Marketing routes
});

Route::middleware(['auth', 'role:gm', 'verified'])->prefix('gm')->name('gm.')->group(function () {
    // GM routes
});

Route::middleware(['auth', 'role:direktur', 'verified'])->prefix('direktur')->name('direktur.')->group(function () {
    // Direktur routes
});
```
**Masalah:** Middleware `'verified'` diterapkan ke semua route groups, bukan hanya untuk pelanggan.

### 3. Database Structure
**File:** `database/migrations/0001_01_01_000000_create_users_table.php`
- Kolom `email_verified_at` nullable - sudah mendukung verified dan unverified users
- Menggunakan Spatie Permission untuk role management

### 4. User Model
**File:** `app/Models/User.php`
- User model sudah implement `MustVerifyEmail`
- Memiliki method `hasRole()` dari Spatie Permission untuk cek role

---

## Architecture Overview

### User Roles (dari Spatie Permission)
- `pelanggan` - Customer/Buyer (MEMBUTUHKAN email verification)
- `admin` - Administrator (TIDAK membutuhkan email verification)
- `marketing` - Marketing Manager (TIDAK membutuhkan email verification)
- `gm` - General Manager (TIDAK membutuhkan email verification)
- `direktur` - Director (TIDAK membutuhkan email verification)

### Email Verification Flow (Current State)
```
Registration → Registered Event → Email Sent → User Must Verify → Access Protected Routes
```

### Expected Flow (After Fix)
```
For Pelanggan:
    Registration → Registered Event → Email Sent → Must Verify → Access Pelanggan Routes

For Admin/Marketing/GM/Direktur:
    Registration/Creation → No Email Sent → No Verification Needed → Can Access Immediately
```

---

## Solution Architecture

### Phase 1: Modify LoginRequest Validation
**Objective:** Hanya cek email verification untuk role `pelanggan`

**Changes in `app/Http/Requests/Auth/LoginRequest.php`:**
```php
public function authenticate(): void
{
    $this->ensureIsNotRateLimited();

    if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
        RateLimiter::hit($this->throttleKey());
        throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);
    }

    $user = Auth::user();
    
    // Email verification check HANYA untuk pelanggan
    if ($user->hasRole('pelanggan') && ! $user->hasVerifiedEmail()) {
        Auth::guard()->logout();
        throw ValidationException::withMessages([
            'email' => 'Your email address is not verified. Please check your inbox for a verification link.',
        ]);
    }

    RateLimiter::clear($this->throttleKey());
}
```

**Explanation:**
- Setelah authentication berhasil, ambil user object
- Check apakah user memiliki role `pelanggan`
- HANYA jika role adalah `pelanggan`, lakukan pengecekan `hasVerifiedEmail()`
- Jika user adalah admin/marketing/gm/direktur, bypass pengecekan ini

---

### Phase 2: Update Web Routes
**Objective:** Hapus middleware `'verified'` dari routes non-pelanggan

**File:** `routes/web.php`

**Changes:**
1. **Pelanggan routes** - KEEP middleware `'verified'`
   ```php
   Route::middleware(['auth', 'role:pelanggan', 'verified'])->prefix('pelanggan')->name('pelanggan.')->group(function () {
       // Keep as is
   });
   ```

2. **Admin routes** - REMOVE middleware `'verified'`
   ```php
   // BEFORE
   Route::middleware(['auth', 'role:admin', 'verified'])->prefix('admin')->name('admin.')->group(function () {
   
   // AFTER
   Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
   ```

3. **Marketing routes** - REMOVE middleware `'verified'`
   ```php
   // BEFORE
   Route::middleware(['auth', 'role:marketing', 'verified'])->prefix('marketing')->name('marketing.')->group(function () {
   
   // AFTER
   Route::middleware(['auth', 'role:marketing'])->prefix('marketing')->name('marketing.')->group(function () {
   ```

4. **GM routes** - REMOVE middleware `'verified'`
   ```php
   // BEFORE
   Route::middleware(['auth', 'role:gm', 'verified'])->prefix('gm')->name('gm.')->group(function () {
   
   // AFTER
   Route::middleware(['auth', 'role:gm'])->prefix('gm')->name('gm.')->group(function () {
   ```

5. **Direktur routes** - REMOVE middleware `'verified'`
   ```php
   // BEFORE
   Route::middleware(['auth', 'role:direktur', 'verified'])->prefix('direktur')->name('direktur.')->group(function () {
   
   // AFTER
   Route::middleware(['auth', 'role:direktur'])->prefix('direktur')->name('direktur.')->group(function () {
   ```

---

### Phase 3: Update Registration Logic (Optional Enhancement)
**Objective:** Berbeda flow untuk pelanggan vs admin/staff

**File:** `app/Http/Controllers/Auth/RegisteredUserController.php`

**Current behavior:** Semua user di-assign role `'pelanggan'`

**Optional improvement:**
- Tambah parameter role saat registration (jika ada admin panel untuk create users)
- Atau tetap keep flow registrasi hanya untuk pelanggan, dan buat separate admin panel untuk create staff

---

## Implementation Steps

### Step 1: Fix LoginRequest (Priority: HIGH)
- [ ] Update `app/Http/Requests/Auth/LoginRequest.php` method `authenticate()`
- [ ] Add role check untuk email verification
- [ ] Test: Admin/marketing/gm/direktur dapat login tanpa verifikasi email
- [ ] Test: Pelanggan tanpa verifikasi email tidak dapat login

### Step 2: Update Web Routes (Priority: HIGH)
- [ ] Remove `'verified'` middleware dari admin routes
- [ ] Remove `'verified'` middleware dari marketing routes  
- [ ] Remove `'verified'` middleware dari gm routes
- [ ] Remove `'verified'` middleware dari direktur routes
- [ ] Keep `'verified'` middleware untuk pelanggan routes
- [ ] Test: Setiap role dapat akses dashboard mereka sendiri

### Step 3: Test Email Verification Workflow (Priority: MEDIUM)
- [ ] Test pelanggan baru dapat register
- [ ] Test verification email dikirim ke pelanggan
- [ ] Test unverified pelanggan tidak dapat login
- [ ] Test unverified pelanggan tidak dapat akses pelanggan dashboard
- [ ] Test setelah verifikasi, pelanggan dapat login dan akses dashboard

### Step 4: Test Non-Pelanggan Login (Priority: MEDIUM)
- [ ] Create test users dengan role admin, marketing, gm, direktur
- [ ] Pastikan mereka dapat login tanpa email verification
- [ ] Pastikan email mereka TIDAK verified (email_verified_at = null)
- [ ] Pastikan mereka dapat akses dashboard mereka

### Step 5: Admin Panel for User Management (Priority: LOW - Future)
- [ ] Create feature untuk admin create/manage users
- [ ] Option untuk assign role saat create user
- [ ] Option untuk skip email verification untuk non-pelanggan
- [ ] Option untuk manually verify email jika diperlukan

---

## Code Files to Modify

### 1. `app/Http/Requests/Auth/LoginRequest.php`
**Current:** Email verification check untuk semua user
**To Do:** Add role check, hanya untuk pelanggan

**Affected lines:** 54-59

### 2. `routes/web.php`
**Current:** `'verified'` middleware di semua route groups
**To Do:** Hapus `'verified'` dari admin, marketing, gm, direktur routes

**Affected lines:** 
- Line 28 (pelanggan) - KEEP
- Line 38 (admin) - REMOVE
- Line 48 (marketing) - REMOVE
- Line 55 (gm) - REMOVE
- Line 62 (direktur) - REMOVE

---

## Testing Strategy

### Unit Test
- [ ] LoginRequest accepts verified pelanggan
- [ ] LoginRequest rejects unverified pelanggan
- [ ] LoginRequest accepts unverified admin/marketing/gm/direktur
- [ ] User model hasVerifiedEmail() returns correct value

### Feature Test
- [ ] Pelanggan registration flow
- [ ] Pelanggan email verification flow
- [ ] Admin login without verification
- [ ] Marketing login without verification
- [ ] GM login without verification
- [ ] Direktur login without verification
- [ ] Route access control per role

### Manual Test Checklist
- [ ] Register sebagai pelanggan
- [ ] Verifikasi email
- [ ] Login sebagai pelanggan
- [ ] Access pelanggan dashboard
- [ ] Login sebagai admin (unverified)
- [ ] Access admin dashboard
- [ ] Check email tidak di-send ke admin saat login
- [ ] Check email_verified_at NULL untuk admin

---

## Risk Assessment

### Risk: Breaking Changes
**Severity:** Low
**Mitigation:** 
- Test existing admin/marketing/gm/direktur accounts
- Ensure backward compatibility
- Consider data migration jika diperlukan

### Risk: Existing Unverified Users
**Severity:** Medium
**Mitigation:**
- Check existing admin/marketing/gm/direktur users
- Jika ada yang unverified, consider verifying them manually
- Or accept mereka tidak bisa login sampai verified (jika sudah implement)

### Risk: Incomplete Testing
**Severity:** High
**Mitigation:**
- Test di development environment terlebih dahulu
- Test all role combinations
- Test edge cases (suspended users, etc)

---

## Success Criteria

✓ Admin, marketing, gm, direktur dapat login tanpa email verification  
✓ Pelanggan harus verify email sebelum login  
✓ Email verification hanya di-send ke pelanggan saat registrasi  
✓ Admin routes accessible tanpa email verification  
✓ Marketing routes accessible tanpa email verification  
✓ GM routes accessible tanpa email verification  
✓ Direktur routes accessible tanpa email verification  
✓ Pelanggan routes require email verification  

---

## Additional Considerations

### Email Notification for Non-Pelanggan
- Admin/marketing/gm/direktur: Email verification NOT sent
- Consider alternative way to notify them (manual notification, etc)

### Account Creation for Non-Pelanggan
- Decide: Self-registration vs Admin-only creation
- Current: Only pelanggan can self-register
- Future: Build admin panel to create admin/marketing/gm/direktur users

### User Verification Status Display
- Consider adding verification status indicator di admin panel
- Useful untuk tracking verified vs unverified users

### Migration Strategy
- Jika sudah ada existing users di database
- Run script untuk set `email_verified_at` untuk all non-pelanggan
- Or mark them as verified manually

---

## Timeline Estimate

- **Phase 1 (LoginRequest):** 15 minutes
- **Phase 2 (Web Routes):** 10 minutes
- **Phase 3 (Testing):** 30-45 minutes
- **Total:** ~1-2 hours

---

## References

### Files Modified
1. `app/Http/Requests/Auth/LoginRequest.php`
2. `routes/web.php`

### Files to Review
1. `app/Models/User.php` - Role checks, MustVerifyEmail
2. `app/Http/Controllers/Auth/AuthenticatedSessionController.php` - Login redirect
3. `app/Http/Controllers/Auth/RegisteredUserController.php` - Registration flow
4. `routes/auth.php` - Auth routes configuration

### Related Features
- Spatie Permission for role management
- Laravel email verification
- Laravel authentication

---

## Notes

- Email verification infrastructure sudah ada (migrations, controllers, routes, views)
- Role system sudah implemented dengan Spatie Permission
- Hanya perlu conditional logic berdasarkan role
- No database migration needed
- No new migrations required

---

## Status
- **Document Created:** [Current Date]
- **Status:** Ready for Implementation
- **Next Step:** Start Phase 1 - Fix LoginRequest
