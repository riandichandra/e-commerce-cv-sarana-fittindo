# Hasil Pengujian Black Box E-Commerce CV Sarana Fittindo Palembang

## Tujuan Pengujian

Dokumen ini berisi hasil pengujian black box untuk sistem e-commerce produk interior berbasis web pada CV Sarana Fittindo Palembang. Pengujian dilakukan dari sisi perilaku sistem, yaitu dengan menjalankan aksi pengguna melalui route, form, validasi, perubahan data, dan halaman yang tersedia pada aplikasi.

## Informasi Eksekusi

- Tanggal pengujian: 8 Juni 2026.
- Lingkungan pengujian: Laravel testing environment dengan SQLite in-memory sesuai `phpunit.xml`.
- Metode pengujian: automated feature test berbasis HTTP request, session assertion, view assertion, dan database assertion.
- File hasil: `planning-pengujian-blackbox-fittindo.md`.

## Ringkasan Hasil Test

| No | Perintah Pengujian | Hasil |
| --- | --- | --- |
| 1 | `php artisan test --compact` | 94 test berhasil, 2 test gagal, 402 assertion. Kegagalan terjadi pada assertion teks harga karena HTML memisahkan teks `Rp` dan nominal ke baris berbeda. |
| 2 | `php artisan test tests/Feature/Auth tests/Feature/ProfileTest.php tests/Feature/Pelanggan/CheckoutTest.php tests/Feature/Pelanggan/OrderListTest.php tests/Feature/Admin/ShippingCostConfirmationTest.php tests/Feature/Admin/OrderFilterTest.php tests/Feature/Admin/OrderDetailEvidenceTest.php tests/Feature/Admin/DashboardTest.php tests/Feature/Admin/AdminListUiTest.php tests/Feature/Admin/PromotionListTest.php tests/Feature/Marketing tests/Feature/GM/GmListUiTest.php tests/Feature/Direktur/DirekturListUiTest.php tests/Feature/DashboardSettingsNavigationTest.php --compact` | 83 test berhasil, 370 assertion. |
| 3 | Test tambahan sementara untuk login role, katalog, wishlist, keranjang, checkout validation, verifikasi pembayaran, dan update status order | 7 test berhasil, 48 assertion. File test sementara sudah dihapus setelah pengujian. |
| 4 | Test tambahan sementara untuk validasi login kosong, register invalid, dan upload bukti pembayaran invalid | 3 test berhasil, 8 assertion. File test sementara sudah dihapus setelah pengujian. |

## Catatan Temuan

- Secara fungsional alur utama berhasil diuji: autentikasi, katalog, wishlist, keranjang, alamat, checkout, ongkir, upload bukti pembayaran, verifikasi pembayaran, status order, laporan GM, dan laporan direktur.
- Full test suite masih memiliki 2 kegagalan pada `ProductListTest` dan `ProductDetailPageTest`. Penyebabnya adalah assertion mencari teks harga satu baris seperti `Rp 325.000` dan `Rp 250.000`, sedangkan HTML merender `Rp` dan nominal dengan jeda baris. Halaman tetap memuat data produk dan harga, tetapi assertion test perlu disesuaikan memakai `assertSeeText` atau pola yang toleran terhadap whitespace.

## Tabel 4.X Hasil Pengujian Black Box Sistem E-Commerce Produk Interior Berbasis Web Pada CV Sarana Fittindo Palembang

| No | Nama Form | Pengujian | Hasil yang Diharapkan | Hasil Pengujian |
| --- | --- | --- | --- | --- |
| 1 | Halaman Login | Memasukkan email dan password admin yang benar, kemudian menekan tombol Login. | Sistem menampilkan dashboard admin sesuai hak akses pengguna. | Berhasil. Login role admin diuji dan redirect ke dashboard admin sesuai hak akses. |
| 2 | Halaman Login | Memasukkan email pelanggan dan password yang benar, kemudian menekan tombol Login. | Sistem menampilkan dashboard pelanggan apabila email pelanggan sudah terverifikasi. | Berhasil. Login role pelanggan terverifikasi diuji dan redirect ke dashboard pelanggan. |
| 3 | Halaman Login | Memasukkan email yang benar dan password yang salah, kemudian menekan tombol Login. | Sistem menampilkan pesan bahwa email atau password tidak sesuai dan pengguna tetap berada di halaman login. | Berhasil. Sistem menolak password salah dan pengguna tetap berstatus guest. |
| 4 | Halaman Login | Mengosongkan email atau password, kemudian menekan tombol Login. | Sistem menampilkan pesan validasi bahwa email dan password wajib diisi. | Berhasil. Sistem menampilkan error validasi untuk field email dan password. |
| 5 | Halaman Register | Mengisi seluruh data registrasi pelanggan dengan benar, kemudian menekan tombol Daftar. | Sistem menyimpan data pelanggan baru dan mengarahkan pengguna ke proses verifikasi email atau halaman sesuai alur registrasi. | Berhasil. Registrasi user baru diuji dan data pengguna berhasil dibuat. |
| 6 | Halaman Register | Mengisi email dengan format tidak valid, kemudian menekan tombol Daftar. | Sistem menampilkan pesan validasi bahwa format email tidak valid. | Berhasil. Sistem menolak format email tidak valid. |
| 7 | Halaman Register | Mengisi password dan konfirmasi password yang berbeda, kemudian menekan tombol Daftar. | Sistem menampilkan pesan validasi bahwa konfirmasi password tidak sesuai. | Berhasil. Sistem menolak konfirmasi password yang tidak sama. |
| 8 | Lupa Password | Memasukkan email yang terdaftar pada halaman lupa password, kemudian menekan tombol Kirim Link Reset Password. | Sistem mengirimkan link reset password atau menampilkan pesan bahwa link reset password telah dikirim. | Berhasil. Request reset password diuji dan sistem menghasilkan status link reset terkirim. |
| 9 | Logout | Pengguna yang sudah login menekan tombol Logout. | Sistem menghapus session pengguna dan mengarahkan pengguna kembali ke halaman utama atau login. | Berhasil. Logout diuji, session terhapus, dan pengguna diarahkan ke halaman utama. |
| 10 | Katalog Produk | Membuka halaman daftar produk pelanggan. | Sistem menampilkan daftar produk aktif beserta informasi nama, harga, gambar, kategori, dan status ketersediaan. | Berhasil dengan catatan. Halaman katalog dan data produk berhasil tampil; full suite memiliki catatan assertion harga karena whitespace HTML. |
| 11 | Katalog Produk | Melakukan pencarian produk dengan kata kunci yang tersedia. | Sistem menampilkan produk yang sesuai dengan kata kunci pencarian. | Berhasil. Pencarian produk dengan kata kunci diuji dan hanya produk yang sesuai tampil. |
| 12 | Katalog Produk | Menggunakan filter kategori pada daftar produk. | Sistem menampilkan produk sesuai filter yang dipilih. | Berhasil. Filter kategori diuji dan produk di luar kategori tidak tampil. |
| 13 | Detail Produk | Klik salah satu produk pada katalog. | Sistem menampilkan halaman detail produk berisi informasi produk, harga, spesifikasi, status, tombol wishlist, dan tombol tambah ke keranjang. | Berhasil dengan catatan. Detail produk, spesifikasi, wishlist, dan tombol keranjang tampil; full suite memiliki catatan assertion harga karena whitespace HTML. |
| 14 | Wishlist | Pelanggan menekan tombol Wishlist pada produk yang belum ada di wishlist. | Sistem menambahkan produk ke wishlist dan menampilkan pesan berhasil. | Berhasil. Produk berhasil masuk ke tabel wishlist dan pesan sukses tampil. |
| 15 | Wishlist | Pelanggan menekan kembali tombol Wishlist pada produk yang sudah ada di wishlist. | Sistem menghapus produk dari wishlist dan menampilkan pesan berhasil. | Berhasil. Produk berhasil dihapus dari tabel wishlist dan pesan sukses tampil. |
| 16 | Keranjang Belanja | Pelanggan memilih produk tersedia, memasukkan jumlah yang valid, kemudian menekan tombol Tambah ke Keranjang. | Sistem menyimpan produk ke keranjang belanja pelanggan dan menampilkan pesan berhasil. | Berhasil. Produk tersedia berhasil ditambahkan ke keranjang dengan jumlah valid. |
| 17 | Keranjang Belanja | Pelanggan mengubah jumlah produk pada keranjang belanja. | Sistem memperbarui jumlah produk dan menghitung ulang subtotal serta total belanja. | Berhasil. Quantity item keranjang berhasil diperbarui dan tersimpan di database. |
| 18 | Keranjang Belanja | Pelanggan menghapus salah satu item dari keranjang belanja. | Sistem menghapus item dari keranjang dan memperbarui daftar serta total belanja. | Berhasil. Item keranjang berhasil dihapus dari database. |
| 19 | Alamat Pengiriman | Pelanggan menambah alamat pengiriman dengan data lengkap dan valid. | Sistem menyimpan alamat baru dan menampilkan alamat pada daftar alamat pelanggan. | Berhasil. Alamat berhasil dibuat dan otomatis menjadi alamat utama jika belum ada alamat lain. |
| 20 | Alamat Pengiriman | Pelanggan mengosongkan salah satu data wajib pada form alamat, kemudian menekan tombol Simpan. | Sistem menampilkan pesan validasi bahwa data alamat wajib diisi. | Berhasil. Validasi alamat wajib sudah tercakup pada pengujian create/update alamat dan validasi request. |
| 21 | Alamat Pengiriman | Pelanggan mengubah data alamat pengiriman yang sudah tersimpan. | Sistem memperbarui data alamat dan menampilkan pesan berhasil. | Berhasil. Data alamat berhasil diperbarui dan perubahan tersimpan di database. |
| 22 | Alamat Pengiriman | Pelanggan menghapus alamat pengiriman dan mengonfirmasi penghapusan. | Sistem menghapus alamat dari daftar alamat pelanggan atau menampilkan pesan gagal apabila alamat tidak dapat dihapus. | Berhasil. Alamat dapat dihapus, alamat utama dapat dipromosikan ke alamat lain, dan alamat yang terkait delivery ditolak untuk dihapus. |
| 23 | Checkout | Pelanggan memilih alamat pengiriman, metode pembayaran, dan promosi yang valid, kemudian menekan tombol Checkout. | Sistem membuat pesanan baru, menyimpan snapshot alamat dan promosi, serta menampilkan detail pesanan. | Berhasil. Checkout membuat order, order item, payment, snapshot alamat, snapshot promosi, diskon, ongkir, dan mengosongkan keranjang. |
| 24 | Checkout | Pelanggan menekan tombol Checkout tanpa memilih alamat atau metode pembayaran. | Sistem menampilkan pesan validasi bahwa alamat dan metode pembayaran wajib dipilih. | Berhasil. Sistem menampilkan error validasi untuk data pengiriman dan metode pembayaran. |
| 25 | Konfirmasi Ongkir | Admin menginput biaya ongkir untuk pesanan yang menunggu konfirmasi ongkir. | Sistem menyimpan biaya ongkir, memperbarui total pembayaran, dan mengubah status pesanan menjadi belum dibayar. | Berhasil. Admin dapat mengonfirmasi ongkir manual, total order dan nominal payment ikut diperbarui. |
| 26 | Upload Bukti Pembayaran | Pelanggan mengunggah bukti pembayaran dengan file gambar yang valid. | Sistem menyimpan bukti pembayaran dan mengubah status pembayaran menjadi menunggu verifikasi. | Berhasil. File gambar bukti pembayaran tersimpan dan status order berubah menjadi menunggu verifikasi pembayaran. |
| 27 | Upload Bukti Pembayaran | Pelanggan mengunggah bukti pembayaran dengan format file tidak valid. | Sistem menolak file dan menampilkan pesan validasi format bukti pembayaran. | Berhasil. File `txt` ditolak dan sistem menampilkan error validasi pada field bukti pembayaran. |
| 28 | Verifikasi Pembayaran | Admin menyetujui bukti pembayaran yang valid. | Sistem mengubah status pembayaran menjadi terverifikasi dan status pesanan menjadi pembayaran dikonfirmasi atau diproses sesuai alur sistem. | Berhasil. Status pembayaran berubah menjadi terverifikasi dan status order menjadi pembayaran dikonfirmasi. |
| 29 | Verifikasi Pembayaran | Admin menolak bukti pembayaran yang tidak valid dan mengisi catatan penolakan. | Sistem mengubah status pembayaran menjadi ditolak dan menyimpan catatan penolakan. | Berhasil. Status pembayaran berubah menjadi ditolak, alasan penolakan tersimpan, dan status order menjadi dibatalkan. |
| 30 | Kelola Order dan Laporan | Admin mengubah status order menjadi diproses lalu dikirim, pelanggan mengonfirmasi pesanan diterima, lalu GM atau direktur membuka laporan. | Sistem menyimpan perubahan status order menjadi dikirim lalu selesai, dan data order muncul pada laporan sesuai filter yang digunakan. | Berhasil. Admin dapat mengubah status ke diproses dan dikirim, pelanggan dapat menyelesaikan pesanan, laporan GM dan direktur berhasil menampilkan data order. |

## Kesimpulan

Berdasarkan hasil pengujian, 30 skenario black box utama berhasil dijalankan. Fitur inti e-commerce CV Sarana Fittindo Palembang berjalan sesuai hasil yang diharapkan, dengan satu catatan perbaikan pada test otomatis lama yang masih sensitif terhadap format whitespace teks harga pada halaman produk.

## Rekomendasi Perbaikan

1. Perbarui assertion harga pada `tests/Feature/Admin/ProductListTest.php` dan `tests/Feature/Pelanggan/ProductDetailPageTest.php` agar menggunakan assertion yang toleran terhadap whitespace HTML.
2. Tambahkan test permanen untuk wishlist, update/hapus keranjang, verifikasi/tolak pembayaran, dan redirect login per role karena skenario tersebut sudah terbukti penting pada pengujian black box.
3. Tambahkan dokumentasi bukti pengujian manual berupa screenshot apabila dokumen ini akan dipakai sebagai lampiran laporan tugas akhir.
