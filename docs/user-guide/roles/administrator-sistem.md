# Panduan Role: Administrator Sistem

**Untuk:** orang yang dipercaya mengelola akses dan kesiapan aplikasi.
Role sistem: `super-system`. Jangan gunakan akun ini bersama-sama atau sebagai
akun kasir harian. Panduan ini tidak membuat/mengubah role database.

## Tahap 1: Pastikan Aplikasi Dapat Dipakai

1. Buka alamat toko, pada PC ini `https://pos.test`, lalu login dengan akun sendiri.
2. Pastikan tidak ada peringatan sertifikat. Jangan menyarankan operator
   melewati peringatan HTTPS.
3. Buka Dasbor dan halaman yang dibutuhkan. Untuk tablet, alamat jaringan dan
   sertifikat harus disiapkan; pengujian tablet masih perlu dilakukan nyata.
4. Bila gagal, catat waktu, alamat dan pesan tanpa menyertakan password/secret.

**Lanjut jika:** login dan navigasi berfungsi, bukan sekadar server menyala.

## Tahap 2: Bagi Akses

1. Sepakati tugas setiap orang dengan pemilik.
2. Buka **Identitas & Akses**, periksa **Permission** yang terdaftar.
3. Buat/sesuaikan **Role**, kemudian kelola akun pada **Akses Pengguna**.
4. Akun baru memerlukan nama/email, password awal dan konfirmasi serta role.
5. Sampaikan akses secara pribadi. Minta petugas memeriksa menu sesuai tugas.
6. Nonaktifkan akun petugas yang keluar sesuai aturan sistem; jangan menghapus
   akun super-system aktif terakhir atau membagikan password administrator.

**Periksa:** kasir perlu izin membuka POS dan posting, bukan salah satunya saja.
Role demo tidak menjamin tugas operasional lengkap. Lihat
[matriks akses](../03-role-dan-akses.md) sebelum memberi izin tambahan.

## Tahap 3: Identitas, Cetak Dan Backup

1. Isi **Pengaturan toko** berdasarkan identitas yang disahkan pemilik.
2. Di **Operasional**, atur A4/58 mm/80 mm dan gunakan **Uji cetak**.
3. Pilih printer melalui dialog browser/driver, bukan opsi raw USB/LAN aplikasi.
4. Gunakan **Backup sekarang** > **Buat backup**, periksa status dan berkas
   di `C:\laragon\www\pos\backups`.
5. Opsi backup harian dan jamnya dapat disimpan melalui UI; jadwal permintaan
   toko 22.00 WIB. Simpan pengaturan dan periksa hasil terakhir/sinyal runner.
6. Pemicu Laravel Scheduler disiapkan admin saat deployment sesuai Windows
   atau Linux. Menyimpan jam belum membuktikan pemicu berjalan.

Jangan memasang task/service/runner OS tanpa persetujuan. Backup pada PC yang
sama bukan salinan terpisah yang aman dari kerusakan perangkat. Pemulihan hanya
oleh admin dengan prosedur terkendali; jangan menimpa database toko untuk latihan.

## Tahap 4: Dampingi Persiapan Data

Serahkan pengisian satuan/gudang/level/barang/pelanggan/supplier ke admin master,
akun/metode bayar ke keuangan, dan pemeriksaan stok awal ke gudang/pemilik.
Master barang belum berarti stok siap. Stok awal dan pembelian tidak boleh
menghitung barang fisik yang sama dua kali.

## Tahap 5: Tanggapi Masalah

- **403 / menu hilang:** periksa akun aktif, role, permission dan status dokumen.
  Jangan menyelesaikannya dengan memberi super-system ke semua petugas.
- **Posting belum pasti:** telusuri nomor/status transaksi dahulu; jangan meminta
  operator mengulang pembayaran atau membuat transaksi baru.
- **Cetak gagal:** cetak ulang transaksi yang sama, bukan mengulang penjualan.
- **Backup gagal:** periksa hasil terakhir dan pemicu jadwal. Jangan menganggap
  flag aktif sebagai bukti backup sudah dibuat.

## Checklist Serah Terima

- [ ] Setiap petugas mempunyai akun pribadi dan role yang disepakati.
- [ ] Menu/aksi penting serta larangan akses sudah dicoba di lingkungan pelatihan.
- [ ] Identitas dan format cetak benar; uji fisik ditandai pending bila belum ada printer.
- [ ] Backup manual berhasil; status jadwal dan salinan terpisah dicatat jujur.
- [ ] Petugas tahu siapa yang dihubungi ketika transaksi bermasalah.

**Jangan disamakan:** checklist ini bukan bukti UAT operator, printer fisik,
tablet atau backup terpisah sudah disahkan. Catat hasil nyata sebelum mencentang.

Lanjut baca [alur global](../01-alur-global.md),
[modul](../02-panduan-modul.md) dan [indeks panduan](../README.md).
