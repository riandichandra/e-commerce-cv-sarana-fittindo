# Planning Notifikasi Hapus Alamat Pelanggan

## Ringkasan Codebase

Proyek ini adalah aplikasi e-commerce berbasis Laravel dengan Blade, Tailwind CSS, Vite, dan Alpine.js. Bagian alamat pelanggan berada di fitur profil pelanggan:

- Route hapus alamat: `DELETE /profile/addresses/{address}` dengan nama route `profile.addresses.destroy`.
- Controller utama: `app/Http/Controllers/ProfileController.php`.
- View daftar/form alamat: `resources/views/profile/partials/user-addresses-form.blade.php`.
- Halaman profil wrapper: `resources/views/profile/edit.blade.php`.
- Alpine sudah tersedia dari `resources/js/app.js`, sehingga notifikasi/modal interaktif bisa dibuat tanpa menambah library baru.

Saat ini tombol hapus alamat memakai konfirmasi browser bawaan:

```blade
onsubmit="return confirm('Hapus alamat ini?')"
```

Konfirmasi ini fungsional, tetapi tampilannya tidak menyatu dengan desain aplikasi, tidak menampilkan detail alamat yang akan dihapus, dan tidak memberi konteks saat alamat tersebut adalah alamat utama.

## Struktur Database Terkait

### `user_addresses`

Tabel utama alamat pelanggan dibuat oleh migration `2026_04_30_141340_create_user_addresses_table.php`.

Kolom penting:

- `id`
- `user_id`, foreign key ke `users`, `onDelete('cascade')`
- `label`, contoh: Rumah, Kantor
- `receiver_name`
- `receiver_phone`
- `full_address`
- `province_id`, foreign key ke `provinces`
- `regency_id`, foreign key ke `regencies`
- `district_id`, foreign key ke `districts`
- `village_id`, foreign key ke `villages`
- `postal_code`
- `is_main`
- `created_at`, `updated_at`

Relasi model `UserAddress`:

- belongs to `User`
- belongs to `Province`
- belongs to `Regency`
- belongs to `District`
- belongs to `Village`

Relasi model `User`:

- has many `addresses`

### `orders`

Tabel `orders` menyimpan alamat pengiriman secara denormalized:

- `shipping_name`
- `shipping_phone`
- `shipping_address`
- `shipping_province`
- `shipping_city`
- `shipping_district`
- `shipping_village`
- `shipping_postal_code`

Artinya, saat pelanggan menghapus alamat profil, riwayat alamat pada order yang sudah dibuat tetap aman karena order tidak bergantung langsung pada `user_addresses`.

### `deliveries`

Tabel `deliveries` memiliki `address_id` yang foreign key ke `user_addresses` dengan `onDelete('restrict')`. Ini penting untuk dicek sebelum implementasi penuh, karena penghapusan alamat yang sudah dipakai delivery bisa gagal di database.

Catatan teknis: model `Delivery` saat ini memakai fillable `address` dan relasi `belongsTo(UserAddress::class, 'address', 'id')`, sementara migration memakai kolom `address_id`. Ini perlu dirapikan pada task terpisah atau minimal dipertimbangkan saat validasi penghapusan alamat.

## Tujuan UX

Membuat pengalaman hapus alamat yang lebih bagus, rapi, dan cantik untuk pelanggan:

- Mengganti `confirm()` bawaan browser dengan modal konfirmasi custom.
- Menampilkan detail alamat yang akan dihapus agar pelanggan yakin.
- Memberi peringatan khusus bila alamat adalah alamat utama.
- Memberi feedback sukses/error yang terlihat elegan dan konsisten dengan desain profil.
- Mencegah submit ganda dengan state loading.
- Tetap menjaga route, controller, dan database yang sudah ada.

## Rencana Implementasi

### 1. Ubah daftar alamat agar memakai modal Alpine

File target:

- `resources/views/profile/partials/user-addresses-form.blade.php`

Tambahkan state Alpine di section daftar alamat, misalnya:

- `deleteModalOpen`
- `deleteAddress`
- `deleteFormAction`
- `deleteSubmitting`
- fungsi `openDeleteModal(address)`
- fungsi `closeDeleteModal()`

Tombol `Hapus` tidak lagi langsung submit form. Tombol akan membuka modal dan mengisi data alamat terpilih:

- label alamat
- nama penerima
- nomor HP
- alamat lengkap
- status utama atau bukan
- URL action delete

### 2. Desain modal konfirmasi

Modal dibuat dengan Tailwind dan Alpine, mengikuti gaya aplikasi yang sudah dominan merah, navy, putih, dan abu-abu.

Komponen modal:

- Overlay gelap transparan.
- Panel putih dengan shadow, maksimal lebar sekitar `max-w-lg`.
- Ikon peringatan/hapus memakai `iconify-icon`, karena layout profil sudah memakai Iconify.
- Judul: `Hapus alamat ini?`
- Deskripsi: alamat akan dihapus dari daftar alamat tersimpan.
- Preview alamat dalam panel kecil:
  - label alamat
  - badge `Utama` bila `is_main`
  - penerima dan nomor HP
  - alamat lengkap
  - wilayah dan kode pos
- Peringatan khusus:
  - Jika alamat utama dihapus, sistem akan menjadikan alamat terbaru lainnya sebagai alamat utama.
  - Jika ini satu-satunya alamat, checkout tetap bisa memakai input alamat manual.
- Tombol:
  - `Batal`
  - `Ya, Hapus Alamat`

### 3. Tambahkan state loading pada submit delete

Saat user menekan tombol konfirmasi:

- Tombol disable.
- Label berubah menjadi `Menghapus...`.
- Bisa tampil spinner kecil berbasis CSS/Tailwind.

Tujuannya mencegah pelanggan klik berulang dan memberi rasa bahwa aksi sedang diproses.

### 4. Perbaiki notifikasi hasil aksi

Saat ini notifikasi sukses hanya berupa teks hijau sederhana. Ubah menjadi alert/toast inline yang lebih rapi di area alamat.

Status yang sudah ada:

- `address-created`
- `address-updated`
- `address-deleted`

Rencana tampilan:

- Container `rounded` atau border ringan sesuai style halaman.
- Ikon status.
- Judul singkat, misalnya:
  - `Alamat berhasil ditambahkan`
  - `Alamat berhasil diperbarui`
  - `Alamat berhasil dihapus`
- Pesan pendukung singkat.
- Warna sukses: hijau/emerald dengan background lembut.

Jika ingin lebih interaktif, alert bisa memakai Alpine agar dapat ditutup manual dan otomatis hilang setelah beberapa detik.

### 5. Validasi backend untuk kasus alamat terkait delivery

File target:

- `app/Http/Controllers/ProfileController.php`
- Opsional: `app/Models/UserAddress.php`
- Opsional: `app/Models/Delivery.php`

Karena tabel `deliveries.address_id` memakai `onDelete('restrict')`, rencana backend yang lebih aman:

- Tambahkan relasi `deliveries()` di `UserAddress`.
- Sebelum delete, cek apakah alamat pernah dipakai delivery.
- Jika masih terkait delivery, jangan hapus dan redirect dengan status error, misalnya `address-delete-blocked`.
- Tampilkan notifikasi error yang ramah:
  - `Alamat tidak dapat dihapus`
  - `Alamat ini sudah terhubung dengan data pengiriman. Anda bisa menambahkan alamat baru atau menjadikan alamat lain sebagai utama.`

Namun, karena checkout saat ini menyimpan alamat ke `orders` sebagai snapshot, validasi ini hanya perlu untuk data delivery yang benar-benar menggunakan `address_id`.

### 6. Pertahankan perilaku alamat utama

Perilaku saat ini di `destroyAddress()`:

- Jika alamat yang dihapus adalah alamat utama, sistem mencari alamat terbaru lain dan menjadikannya utama.
- Jika tidak ada alamat lain, tidak ada alamat utama.

Perilaku ini sudah baik dan tetap dipertahankan. Modal hanya perlu mengomunikasikan efek ini kepada pelanggan.

### 7. Testing

Update atau tambah test di `tests/Feature/ProfileTest.php`:

- User tetap bisa menghapus alamat miliknya.
- User tidak bisa menghapus alamat user lain.
- Jika alamat utama dihapus, alamat lain otomatis menjadi utama.
- Jika backend menambahkan proteksi delivery:
  - alamat yang terkait delivery tidak terhapus
  - session status menjadi `address-delete-blocked`

Untuk UI Blade, minimal cek render:

- halaman profil menampilkan tombol hapus
- markup modal tersedia
- status `address-deleted` menampilkan alert sukses

## Sketsa Copywriting

### Modal

Judul:

`Hapus alamat ini?`

Deskripsi:

`Alamat ini akan dihapus dari daftar alamat tersimpan Anda. Riwayat pesanan yang sudah dibuat tidak akan berubah.`

Peringatan alamat utama:

`Alamat ini adalah alamat utama. Setelah dihapus, sistem akan memilih alamat terbaru lainnya sebagai alamat utama.`

Tombol:

- `Batal`
- `Ya, Hapus Alamat`

Loading:

`Menghapus...`

### Notifikasi Sukses

Judul:

`Alamat berhasil dihapus`

Pesan:

`Daftar alamat tersimpan Anda sudah diperbarui.`

### Notifikasi Gagal

Judul:

`Alamat tidak dapat dihapus`

Pesan:

`Alamat ini masih terhubung dengan data pengiriman. Tambahkan alamat baru atau pilih alamat lain sebagai alamat utama.`

## Prioritas Pengerjaan

1. Ganti `confirm()` dengan modal custom di `user-addresses-form.blade.php`.
2. Rapikan notifikasi status alamat di partial yang sama.
3. Tambahkan loading state pada form delete.
4. Tambahkan proteksi backend untuk alamat yang terkait delivery bila fitur delivery aktif memakai `address_id`.
5. Tambah/update test feature.

## Catatan Risiko

- Jangan mengubah schema `orders`, karena alamat order sudah berupa snapshot dan aman dari penghapusan alamat profil.
- Hati-hati dengan tabel `deliveries`: migration memakai `address_id`, tetapi model `Delivery` memakai field `address`. Ini bisa membuat proteksi backend salah membaca relasi bila tidak dirapikan.
- Modal harus tetap submit form `DELETE` biasa agar tetap kompatibel dengan CSRF, method spoofing Laravel, dan test yang sudah ada.
- Pastikan modal tetap nyaman di mobile: panel tidak melebihi viewport, tombol tersusun rapi, dan alamat panjang tidak merusak layout.
