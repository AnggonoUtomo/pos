# UAT Operator Dan Printer

Status: UAT lengkap BELUM DISAHKAN. User sudah mengonfirmasi uji awal login
HTTPS dan backup manual berhasil pada PC produksi. Skenario transaksi/printer
di bawah tetap pending; QA otomatis tidak menggantikan lembar ini.
Target: PC Windows/Laragon dan tablet di jaringan toko.

## Persiapan

- [ ] Isi nama operator, penanggung jawab, tanggal, commit rilis dan perangkat.
- [ ] Pastikan backup terverifikasi dan layanan MySQL/Apache aktif.
- [ ] Gunakan database UAT terpisah atau data uji yang disetujui pemilik.
  Jangan menjalankan demo/test/reset pada database operasional. Transaksi posted
  tidak dibersihkan dengan DELETE; jangan mencampur bukti uji dengan omzet nyata.
- [ ] Catat saldo stok dan arus pembayaran SEBELUM uji untuk menghitung selisih.
- [ ] Pastikan izin operator bukan bypass super-system untuk pengujian batas akses.
- [ ] Konfirmasi hostname HTTPS dapat diakses PC/tablet tanpa warning sertifikat.

Operator: belum diisi. Penanggung jawab: belum diisi. Tanggal: belum diisi.
Commit/perangkat: belum diisi. Database UAT yang disetujui: belum diisi.
Jangan menulis password, APP_KEY, kontak pelanggan nyata atau credential di sini.

## Skenario Dengan Angka Pembanding

Gunakan kamera uji, satuan pcs, dua gudang dan pelanggan level uji. Contoh harga
berikut hanya fixture UAT, bukan penetapan tarif pajak atau harga toko.
Harga jual pcs pada level uji Rp200.000, harga beli Rp120.000/pcs.

| ID | Tindakan operator | Hasil yang diharapkan | Status / bukti |
| --- | --- | --- | --- |
| U01 | Login operator berizin; coba aksi yang tidak diberi permission | Aksi berizin berhasil; aksi terlarang ditolak backend, bukan hanya tombol disembunyikan | PARSIAL: login HTTPS PC dikonfirmasi user; uji larangan akses operator masih pending |
| U02 | Periksa barang CCTV, kabel, konversi meter/rol dan matriks harga | Nama/satuan sesuai; angka 1 dan 0,5 tidak tampil sebagai 1.000000/0.500000; harga mengikuti level dan satuan | PENDING |
| U03 | Beli 4 pcs kamera lalu Terima dan bayar Rp480.000 | Pembelian diterima/lunas; stok asal naik 4 pcs; uang keluar Rp480.000; nota pembayaran tersedia | PENDING |
| U04 | Jual 2 pcs, diskon 10%, pajak uji 11% | Subtotal Rp400.000, diskon Rp40.000, pajak Rp39.600, total Rp399.600 | PENDING |
| U05 | Bayar transfer Rp200.000 + tunai Rp200.000 | Kembalian Rp400; masuk bersih Rp399.600; stok turun 2 pcs; satu invoice lunas | PENDING |
| U06 | Cetak struk dan cetak ulang invoice yang sama | Identitas toko, barang, diskon/pajak, total, metode dan kembalian benar; invoice tidak digandakan | PENDING |
| U07 | Retur layak jual 1 pcs dari U04 lalu refund tepat | Stok asal naik 1 pcs; uang keluar Rp199.800; invoice asal tidak diedit | PENDING |
| U08 | Retur 1 pcs dari pembelian U03 lalu terima uang supplier | Stok asal turun 1 pcs; uang masuk Rp120.000; tidak mengubah payment pembelian asal | PENDING |
| U09 | Transfer 1 pcs ke gudang kedua, lalu buka lagi dokumen posted | Asal turun 1 pcs, tujuan naik 1 pcs, total kuantitas/nilai toko tetap; tidak bisa edit posted | PENDING |
| U10 | Koreksi kabel keluar 0,5 meter dengan alasan nyata pada data UAT | Berkurang tepat 0,5 meter; ada movement dan audit; permintaan melebihi stok ditolak | PENDING |
| U11 | Cocokkan laporan periode WIB dan kedua gudang | Sebelum U10, selisih stok kamera total +2 pcs/nilai +Rp240.000 dan arus uang -Rp160.200; pembayaran bersifat global, bukan saldo rekening | PENDING |
| U12 | Login dan gunakan alur kasir dari tablet | Hostname/HTTPS sama, tidak ada warning sertifikat, kontrol dapat dioperasikan, permission tetap berlaku | PENDING |

Perhitungan U11: -480.000 +399.600 -199.800 +120.000 = -160.200.
Nilai laporan dibandingkan dengan baseline sebelum uji dan data uji yang sama;
angka bukan total mutlak jika database memiliki transaksi lain.

## Printer

Saat inventaris awal hanya printer virtual tersedia. PDF boleh untuk memeriksa
layout, tetapi bukan bukti printer fisik, pemotong kertas atau koneksi berfungsi.

Model: belum dikonfirmasi. Kertas: belum dikonfirmasi. USB/LAN: belum dikonfirmasi.

Konfirmasi user25Oktober2026: printer fisik **belum ada**. Tidak ada cetak
fisik yang diklaim PASS. Konfigurasi produksi/HTTPS PC dan backup/restore
lokal sudah terverifikasi agent, bukan pengganti UAT manusia di tabel ini.
User memilih backup root project dan jam22:00WIB; ini belum merupakan media
terpisah atau bukti pemicu otomatis sudah dijalankan admin.

Uji operator awal25Oktober2026: user menyatakan login dan tombol backup manual
berhasil di server produksi. Audit last_manual succeeded cocok dengan berkas
pos-20261025-115343-9d118eb8.sql,205869byte di folder backups. Agent tidak
membuat transaksi penjualan/pembelian uji pada database toko untuk bukti ini.
Ini belum menyetujui keseluruhan UAT atau go-live, dan bukan bukti off-device.

Pilihan A4/58/80 tersedia di Operasional beserta Uji cetak tanpa transaksi.
Preview otomatis dan PDF ketiga ukuran sudah PASS; hasil fisik tetap pending.
Driver/dialog printer harus memakai kertas yang sama. Area thermal 54/76 mm
memperhitungkan margin; opsi aplikasi tidak memilih driver USB/LAN secara diam-diam.
Tablet fisik belum tersedia menurut konfirmasi user.

- [ ] Driver Windows terpasang dan test page fisik berhasil.
- [ ] Pilih printer serta lebar kertas sesuai perangkat; matikan header/footer
  browser dan atur skala/margin berdasarkan hasil cetak nyata.
- [ ] Cetak struk berisi nama barang panjang dan beberapa baris pembayaran.
- [ ] Teks/angka tidak terpotong, total terbaca dan barcode tidak diasumsikan ada.
- [ ] Cetak ulang cocok dengan invoice asal dan tidak membuat penjualan baru.
- [ ] Cabut/offline printer saat mencetak: invoice tetap tersimpan; operator
  dapat cetak ulang setelah printer pulih tanpa membayar ulang.
- [ ] Uji cetak dari tablet jika memang menjadi perangkat cetak toko.
  Dialog cetak browser tidak menjamin tablet mendukung printer USB di PC.

## Keputusan

Hasil: PENDING. Daftar masalah: belum diisi. Bukti non-sensitif: belum diisi.
Persetujuan operator/pemilik: BELUM. Tanggal go-live: BELUM DITETAPKAN.

Agent tidak menandai skenario manusia PASS berdasarkan automated test.
Jika gagal, catat ID skenario, expected/actual dan nomor dokumen uji; jangan
menulis payload sensitif atau menghapus ledger untuk memperbaiki hasil.
