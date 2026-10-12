# BRILIaN CENTER - Sistem Peminjaman Ruangan Berbasis Web

Aplikasi berbasis web untuk mengelola peminjaman ruangan, dengan dua role:

* **User:** mengajukan peminjaman, melihat status dan riwayat pengajuan.
* **Admin:** mengelola ruangan, memproses pengajuan, serta mengelola akun pengguna.

Dibangun menggunakan **PHP Native, PDO, dan MySQL**, serta dapat dijalankan secara lokal menggunakan XAMPP atau Laragon.

## Fitur

### User

* Registrasi dan login.
* Mengajukan peminjaman ruangan berdasarkan tanggal, waktu, dan keperluan.
* Melihat status dan riwayat pengajuan.
* Membatalkan pengajuan yang masih menunggu persetujuan.

### Admin

* Dashboard ringkasan data.
* Mengelola data ruangan dan status ketersediaannya.
* Menyetujui atau menolak pengajuan peminjaman.
* Mendeteksi bentrok jadwal peminjaman.
* Mengelola akun pengguna.

### Foto Ruangan

* Menampilkan foto ruangan pada halaman pengelolaan dan peminjaman.
* Mendukung format JPG, PNG, dan WEBP.
* Ukuran file maksimal 3 MB.

## Teknologi

* PHP Native
* MySQL
* PDO
* HTML, CSS, dan JavaScript
* Composer untuk dependensi PHP

## Instalasi Lokal

1. Salin folder proyek ke direktori `htdocs` XAMPP atau direktori web Laragon.
2. Jalankan Apache dan MySQL.
3. Buat database bernama `sipeminjaman` melalui phpMyAdmin.
4. Impor skema database yang sesuai dengan versi proyek.
5. Konfigurasikan koneksi database secara lokal. Jangan mengunggah kredensial database ke repository.
6. Pastikan dependensi Composer tersedia sebelum menjalankan fitur yang membutuhkannya.
7. Buat akun administrator melalui mekanisme setup yang aman. Jangan gunakan atau memublikasikan kredensial default.
8. Hapus atau nonaktifkan skrip setup setelah akun berhasil dibuat.
9. Buka `http://localhost/sipeminjaman/` di browser.

## Keamanan

* Password pengguna disimpan menggunakan mekanisme hashing.
* Jangan mengunggah password, token, kredensial database, atau file konfigurasi lokal ke repository.
* Gunakan HTTPS dan kata sandi yang kuat saat aplikasi diterapkan di server.
* Untuk penggunaan produksi, terapkan perlindungan CSRF, pembatasan percobaan login, validasi input, dan kontrol akses yang sesuai.

## Pengembangan Berikutnya

* Notifikasi status pengajuan.
* Ekspor laporan ke PDF atau Excel.
* Peningkatan pengalaman penggunaan pada perangkat seluler.

---

**BRILIaN CENTER** — Sistem Peminjaman Ruangan Berbasis Web.
