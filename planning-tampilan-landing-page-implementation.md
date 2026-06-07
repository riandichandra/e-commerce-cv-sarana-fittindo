# Planning Implementasi Perbaikan Tampilan Landing Page Promo

## Ringkasan Permintaan

Bagian promo paling atas pada beranda pelanggan saat ini ingin dikembalikan ke model tampilan sebelumnya karena terasa lebih enak dilihat dan bisa bergeser secara bergantian.

Target utama:

- Area promo/hero atas kembali menjadi slider visual besar.
- Promo bisa berganti otomatis dan manual.
- Tetap memakai data promosi aktif dari database.
- Section lain yang sudah dirapikan, seperti shortcut, kategori, produk unggulan, dan produk terbaru, tidak perlu diubah besar-besaran.

## File Terkait

File utama yang akan disentuh:

- `resources/views/pelanggan/dashboard.blade.php`

File pendukung yang kemungkinan cukup dipertahankan:

- `app/Http/Controllers/Pelanggan/DashboardController.php`
- `resources/views/layouts/pelanggan/navigation.blade.php`

Controller saat ini sudah cukup mendukung slider karena sudah mengirim:

- `$heroPromotions`
- `$latestProducts`
- `$featuredProducts`
- `$categories`
- `$cartItemsCount`

Query promo aktif juga sudah memakai:

```php
Promotion::activeNow()
    ->whereNotNull('banner_image')
    ->latest()
    ->limit(3)
    ->get();
```

Jadi perubahan utama cukup pada markup dan style hero.

## Kondisi Tampilan Saat Ini

Pada `resources/views/pelanggan/dashboard.blade.php`, hero saat ini memakai layout dua kolom:

- Kolom kiri berisi teks promo:
  - eyebrow promo
  - judul promo
  - deskripsi
  - periode
  - tombol `Belanja Sekarang`
  - tombol `Lihat Pesanan`
- Kolom kanan berisi gambar promo dalam card.

Masalah dari tampilan sekarang:

1. Gambar promo terasa seperti card/banner kecil di samping, bukan fokus utama.
2. Promo kurang terasa hidup meskipun data slider sudah ada.
3. Kontrol slide hanya berupa dot di kanan atas gambar.
4. Area atas terlalu kosong di sisi kiri saat gambar promosi sebenarnya kuat secara visual.
5. User ingin feel seperti sebelumnya, yaitu hero besar yang bisa bergeser bergantian.

## Target Tampilan Baru

Kembalikan hero promo menjadi full-width visual slider seperti versi lama, tetapi tetap menjaga beberapa perbaikan dari versi baru.

Target visual:

1. Hero memenuhi lebar halaman, bukan dua kolom card.
2. Gambar promo menjadi background utama.
3. Teks berada di atas overlay gelap agar tetap terbaca.
4. Tinggi hero desktop sekitar `560px` sampai `650px`.
5. Tinggi mobile lebih pendek dan adaptif, sekitar `460px` atau auto dengan padding besar.
6. Slider otomatis berganti setiap 5 sampai 6 detik.
7. Tersedia tombol manual:
   - previous
   - next
   - dot indicator
8. Tombol utama tetap:
   - `Belanja Sekarang`
   - `Lihat Pesanan`
9. Bahasa tetap Indonesia.

## Rencana Struktur Hero

Gunakan ulang konsep Alpine slider yang sudah ada, tetapi bentuk visualnya dikembalikan seperti hero lama.

Struktur:

```blade
<section
    x-data="{ active: 0, slides: @js($heroSlides), ... }"
    x-init="start()"
    class="relative overflow-hidden bg-[#0c1d38]"
>
    <div class="absolute inset-0">
        <template x-for="(slide, index) in slides">
            <div
                x-show="active === index"
                x-transition.opacity.duration.700ms
                class="absolute inset-0 bg-cover bg-center"
                :style="..."
            ></div>
        </template>
        <div class="absolute inset-0 overlay"></div>
    </div>

    <div class="relative mx-auto flex max-w-7xl items-center">
        teks hero
    </div>

    kontrol previous/next
    dot indicator
</section>
```

## Rencana Data `$heroSlides`

Data `$heroSlides` di bagian `@php` saat ini sudah bagus dan bisa dipakai.

Pertahankan field:

- `eyebrow`
- `title`
- `description`
- `image`
- `url`
- `period`

Tambahkan fallback yang lebih cocok untuk full background:

- Jika ada promo aktif dengan banner, gunakan banner sebagai background.
- Jika tidak ada promo tapi ada produk terbaru dengan gambar, gunakan gambar produk sebagai background.
- Jika tidak ada gambar sama sekali, tampilkan gradient gelap rapi tanpa broken image.

Catatan:

- Jangan mengembalikan array gambar hardcoded dari `storage/products/...`.
- Tetap pakai data database dan fallback aman.

## Rencana Styling Desktop

Hero desktop:

- `class="relative min-h-[600px] overflow-hidden bg-[#0c1d38]"`
- Content wrapper:
  - `max-w-7xl`
  - `px-4 sm:px-6 lg:px-8`
  - `min-h-[600px]`
  - `flex items-center`
- Text width:
  - `max-w-2xl`
- Judul:
  - `text-4xl sm:text-5xl lg:text-6xl`
  - font tebal
  - warna putih
- Deskripsi:
  - warna `text-slate-200`
  - line-height nyaman
- Overlay:
  - `linear-gradient(90deg, rgba(4,17,45,.92), rgba(7,25,60,.65), rgba(9,22,45,.12))`
  - tambahan bottom gradient agar konten terbaca.

## Rencana Styling Mobile

Hero mobile:

- Jangan memaksa tinggi terlalu ekstrem.
- Gunakan `min-h-[460px]`.
- Padding top dan bottom cukup besar agar teks lega.
- Judul maksimal sekitar `text-3xl`.
- Tombol dibuat stack jika layar sempit.
- Dot indicator tetap terlihat di bawah.
- Tombol previous/next bisa tetap di kanan bawah atau disederhanakan agar tidak menutup teks.

## Rencana Kontrol Slider

Pertahankan Alpine methods:

- `next()`
- `prev()`
- `go(index)`
- `start()`
- `reset()`

Perbaikan kecil:

1. Jika hanya ada satu slide, tidak perlu interval.
2. Tombol prev/next hanya tampil jika jumlah slide lebih dari satu.
3. Dot indicator tetap tampil, tetapi aman jika hanya satu slide.
4. Pastikan `clearInterval(this.timer)` aman saat `timer` null.

Contoh konsep:

```js
start() {
    if (this.slides.length > 1) {
        this.timer = setInterval(() => this.next(), 5500);
    }
},
reset() {
    if (this.timer) {
        clearInterval(this.timer);
    }
    this.start();
}
```

## Rencana Urutan Implementasi

1. Buka `resources/views/pelanggan/dashboard.blade.php`.
2. Pertahankan bagian `@php` yang membuat `$heroSlides`, `$displayFeaturedProducts`, dan `$quickLinks`.
3. Ganti markup hero dua kolom saat ini dengan hero full-width slider.
4. Tambahkan tombol previous dan next seperti versi lama.
5. Tambahkan dot indicator di bagian bawah hero.
6. Pastikan link `Belanja Sekarang` memakai `slides[active].url`.
7. Pastikan `Lihat Pesanan` tetap mengarah ke:
   - `pelanggan.orders.index` jika login,
   - `login` jika guest.
8. Biarkan section shortcut, kategori, produk unggulan, produk terbaru, dan CTA bantuan tetap seperti sekarang.
9. Jalankan build dan test.
10. Cek tampilan desktop dan mobile di browser.

## Rencana Validasi

Command:

```bash
npm run build
php artisan test --filter=Pelanggan
php artisan test --filter=Dashboard
```

Validasi browser:

1. Buka halaman `/pelanggan`.
2. Login sebagai pelanggan jika route mengarah ke login.
3. Pastikan area promo atas tampil sebagai background hero besar.
4. Pastikan promo aktif muncul sebagai slide pertama.
5. Pastikan tombol next dan previous mengganti slide jika ada lebih dari satu promo.
6. Pastikan dot indicator mengganti slide saat diklik.
7. Tunggu 5 sampai 6 detik untuk memastikan slide berganti otomatis.
8. Pastikan tidak ada horizontal overflow di desktop.
9. Set viewport mobile, pastikan teks dan tombol tidak saling menimpa.
10. Pastikan console browser tidak ada error Alpine atau asset.

## Catatan Risiko

1. Jika banner promo memiliki teks di dalam gambar, overlay gelap bisa membuat bagian gambar tertutup. Solusinya gunakan overlay kiri-ke-kanan, bukan overlay gelap penuh.
2. Jika gambar promo terlalu kecil, background full-width bisa terlihat pecah. Solusinya tetap `object-cover`/`bg-cover`, tetapi jaga overlay agar tampilan masih rapi.
3. Jika hanya ada satu promo aktif, tombol prev/next sebaiknya tidak tampil agar tidak membingungkan.
4. Jika Alpine tidak aktif, konten awal harus tetap punya fallback teks yang terlihat.
5. Jika banner URL kosong, tombol tetap harus mengarah ke katalog produk.

## Batasan Perubahan

Perubahan ini hanya fokus pada bagian landing/hero promo atas.

Tidak perlu mengubah:

- Struktur database.
- Query controller, kecuali nanti ditemukan kebutuhan limit promo lebih banyak.
- Section kategori.
- Section produk unggulan.
- Section produk terbaru.
- Flow login, cart, checkout, dan pesanan.

## Rekomendasi Implementasi

Gunakan kembali gaya hero lama sebagai dasar karena sesuai feedback user, tetapi kombinasikan dengan data dan copywriting versi baru yang sudah lebih bersih.

Keputusan yang disarankan:

1. Hero full-width dengan background promo.
2. Tinggi desktop `min-h-[600px]`.
3. Overlay gelap dari kiri agar teks terbaca.
4. Tombol prev/next dan dot indicator dikembalikan.
5. Section lain tetap dipertahankan agar keseluruhan beranda tetap rapi.
