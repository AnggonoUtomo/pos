# Dataset Latihan Operasional CCTV Dan Kelistrikan

**Status aktivasi: PENDING MySQL.** Dokumen ini menjelaskan target dataset
yang lulus test disposable; belum merupakan bukti database toko sudah diganti.

Dataset ini **bukan transaksi toko sungguhan**. Seluruh nama/kode berawalan
LAT- adalah fiktif. Pada 27 Oktober 2026 user mengizinkan penggantian seluruh
master dan transaksi lama **hanya satu kali**, setelah backup terverifikasi.
Akun, role/permission, pengaturan toko/operasional, backup dan audit lama
dipertahankan. Audit lama bukan bukti bahwa transaksi lamanya masih tersedia;
arsip lengkap sebelum penggantian berada pada backup.

Dokumen ini melengkapi [panduan penggunaan](README.md). Urutan di bawah
menjelaskan data yang **dibuat oleh seeder setelah aktivasi**, bukan instruksi mengulang
posting yang sama. Nomor dokumen aktual dicatat pada work item setelah aktivasi.

## 1. Master Yang Dipakai

- Gudang: LAT-UTAMA dan LAT-CADANGAN.
- Satuan: Unit, Dus, Meter, Rol, Buah dan Pak.
- Level: Eceran, Grosir dan Installer; satu pelanggan LAT- untuk setiap level.
- Supplier: LAT-SUP-CCTV dan LAT-SUP-LISTRIK.
- Akun: Kas Latihan dan Bank Latihan. Metode Tunai, Debit, Transfer.
- Tidak membuat atau mengganti akun login/role pengguna untuk dataset ini.

| SKU | Barang | Satuan Dasar | Isi Kemasan | Eceran / Dasar | Grosir / Dasar | Installer / Dasar |
| --- | --- | --- | --- | --- | --- | --- |
| LAT-KAMERA | Kamera IP 2MP | Unit | 1 Dus = 4 Unit | Rp500.000 | Rp475.000 | Rp450.000 |
| LAT-NVR | NVR 8 Kanal | Unit | 1 Dus = 2 Unit | Rp1.600.000 | Rp1.500.000 | Rp1.400.000 |
| LAT-UTP | Kabel UTP Cat6 | Meter | 1 Rol = 305 Meter | Rp6.000 | Rp5.500 | Rp5.000 |
| LAT-NYM | Kabel NYM 3x1.5 mm2 | Meter | 1 Rol = 100 Meter | Rp15.000 | Rp14.000 | Rp13.500 |
| LAT-MCB | MCB 1P 6A | Unit | 1 Dus = 12 Unit | Rp55.000 | Rp50.000 | Rp47.500 |
| LAT-RJ45 | Konektor RJ45 | Buah | 1 Pak = 100 Buah | Rp2.500 | Rp2.000 | Rp1.750 |

Harga kemasan pada dataset ini = harga dasar x isi kemasan. Seluruh 36
kombinasi harga (6 barang x 2 satuan jual x 3 level) tersimpan. Barcode latihan
berpola LAT-KAMERA-UNIT, LAT-KAMERA-BOX, LAT-UTP-M, LAT-UTP-ROLL dan seterusnya.
Tulisan Latin pada kode satuan adalah identifier teknis, bukan format angka.

## 2. Stok Awal: Barang Yang Sudah Ada

Buka Stok > Stok awal dan cari catatan **LATIHAN 01**. Dokumen sudah posted.

| Barang | Input Satuan | Harga Pokok / Satuan Pilihan | Stok Dasar | Nilai |
| --- | --- | --- | --- | --- |
| NVR | 2 Dus | Rp2.000.000 | 4 Unit | Rp4.000.000 |
| UTP | 1 Rol | Rp915.000 | 305 Meter | Rp915.000 |
| NYM | 1 Rol | Rp1.000.000 | 100 Meter | Rp1.000.000 |
| MCB | 2 Dus | Rp360.000 | 24 Unit | Rp720.000 |
| RJ45 | 2 Pak | Rp100.000 | 200 Buah | Rp200.000 |

Nilai awal total Rp6.835.000. Kamera belum mempunyai stok; sumbernya pembelian,
bukan stok awal. Dokumen stok awal tidak membuat pembayaran uang.

## 3. Pembelian Kamera: Terima Dan Bayar

Buka Order Pembelian, cari **LATIHAN 02** dari supplier CCTV. Sudah Diterima
dan Lunas: 2 Dus x Rp1.200.000 = Rp2.400.000, dibayar Transfer.

Kartu stok kamera menunjukkan masuk 8 Unit di LAT-UTAMA. Biaya tiap Unit
Rp300.000. FIFO penerimaan dan pengeluaran uang tercatat melalui alur aplikasi,
bukan mengisi saldo akhir secara langsung.

## 4. Tiga Contoh Penjualan

Buka Riwayat Penjualan, cocokkan pelanggan dan barang berikut. Seluruh invoice
sudah posted/lunas. User pembuat adalah administrator super-system existing;
kasir biasa tidak otomatis melihat transaksi orang lain.

| Pelanggan | Isi | Diskon / Pajak Latihan | Total | Pembayaran |
| --- | --- | --- | --- | --- |
| Installer | Kamera 2 Unit x Rp450.000 | Tidak ada | Rp900.000 | Transfer Rp600.000 + Tunai Rp350.000; kembalian Rp50.000 |
| Eceran | NYM 2,5 Meter x Rp15.000 | Nominal Rp2.500; pajak 11% atas Rp35.000 | Rp38.850 | Tunai Rp40.000; kembalian Rp1.150 |
| Grosir | RJ45 1 Pak x Rp200.000 | Diskon 10%; pajak 11% atas Rp180.000 | Rp199.800 | Transfer Rp199.800 |

Tarif pajak di atas hanya contoh pembelajaran perhitungan, bukan arahan tarif
yang wajib digunakan toko. Penerimaan penjualan bersih total Rp1.138.650,
bukan jumlah uang tunai sebelum kembalian. NYM keluar 2,5 Meter dan RJ45
keluar 100 Buah walaupun input penjualan memakai 1 Pak.

## 5. Retur Pelanggan Dan Refund

Buka Retur Penjualan, cari **LATIHAN 03**. Invoice asal adalah penjualan
Installer di atas. Barang kembali 1 Unit kamera layak jual ke LAT-UTAMA,
kemudian refund Rp450.000 melalui Tunai sudah selesai.

Stok kamera sesudah penjualan 6 Unit menjadi 7 Unit. Harga jual memakai snapshot
invoice asal; biaya stok kembali Rp300.000 dari FIFO asal. Layer retur baru
dibuat saat penerimaan, bukan menulis ulang layer/penjualan lama.

## 6. Retur Supplier Dan Penerimaan Uang

Buka Retur Pembelian, cari **LATIHAN 04**. Jumlah 0,25 Dus dari pembelian
kamera berarti 1 Unit, bukan 0,25 Unit. Serah barang sudah posted dan uang
Rp300.000 diterima melalui Transfer. Stok kamera menjadi 6 Unit.

Kuantitas/satuan dan nominal berasal dari snapshot pembelian lunas. Mengonfirmasi
uang kembali tidak mengurangi stok untuk kedua kali.

## 7. Transfer Dan Koreksi

Transfer **LATIHAN 05** memindahkan 2 Unit kamera dari Utama ke Cadangan.
Sesudah transfer: Utama 4 Unit, Cadangan 2 Unit; total tetap 6 Unit.

Koreksi **LATIHAN 06** mengurangi 1 Unit di Utama sebagai contoh selisih fisik
yang disahkan. Sesudahnya Utama 3 Unit, Cadangan 2 Unit. Alasannya tercatat;
ini bukan edit saldo/ledger manual dan tidak membuat pembayaran.

## 8. Cocokkan Hasil Akhir Sebelum Latihan Lanjutan

| Barang | LAT-UTAMA | LAT-CADANGAN | Nilai Total Stok |
| --- | --- | --- | --- |
| Kamera | 3 Unit | 2 Unit | Rp1.500.000 |
| NVR | 4 Unit | 0 | Rp4.000.000 |
| UTP | 305 Meter | 0 | Rp915.000 |
| NYM | 97,5 Meter | 0 | Rp975.000 |
| MCB | 24 Unit | 0 | Rp720.000 |
| RJ45 | 100 Buah | 0 | Rp100.000 |

Nilai stok keseluruhan **Rp8.210.000**. Bandingkan saldo, kartu stok dan laporan
pada tanggal pembuatan dataset dengan cakupan seluruh gudang.

| Arus Uang | Nilai |
| --- | --- |
| Penjualan masuk | Rp1.138.650 |
| Pembelian keluar | Rp2.400.000 |
| Refund pelanggan keluar | Rp450.000 |
| Refund supplier masuk | Rp300.000 |
| Selisih arus | Keluar bersih Rp1.411.350 |

Selisih ini bukan laba atau saldo bank/kas aktual, karena saldo awal dan
aktivitas di luar aplikasi tidak dihitung sebagai saldo berjalan.

## 9. Draft Yang Sengaja Belum Selesai

Empat dokumen bertuliskan **LATIHAN DRAFT** disiapkan untuk dipelajari:

1. Pembelian 1 Dus MCB x Rp360.000 dari supplier listrik.
2. Retur penjualan 1 Unit kamera dari invoice Installer.
3. Retur pembelian 0,25 Dus kamera dari pembelian yang sama.
4. Transfer 50 Meter UTP dari Utama ke Cadangan.

Draft belum mengubah saldo/uang. Pilih satu alur sesuai panduan/role untuk
latihan lanjutan. **Setelah draft diposting atau Anda membuat transaksi baru,
angka tabel hasil akhir di atas tidak lagi menjadi saldo terkini.** Jangan
mengulang seeder dengan tujuan mengembalikan angka: rerun adalah no-op, bukan reset.

## 10. Batas Dan Pemeliharaan

- Penggantian dataset lama hanya atas instruksi khusus ini; tidak ada reset
  otomatis pada startup, migration, DatabaseSeeder umum atau Laravel Scheduler.
- Helper penggantian sekali terikat fingerprint data lama. Jangan mengubah
  fingerprint untuk mengizinkan penghapusan kedua.
- Marker audit menjaga seeder tetap no-op meskipun data latihan kemudian diedit
  atau draft diselesaikan. Seeder tidak menimpa latihan lanjutan.
- Tidak ada akun/password demo baru. Role produksi tidak diubah dan assignment
  dilakukan administrator sesuai [matriks produksi](../modules/Platform/Identity/production-roles.md).
- Belajar di dataset ini tidak mengesahkan UAT operator, printer fisik,
  sertifikat tablet, jadwal scheduler atau salinan backup terpisah.
- Bukti penggantian dan nomor dokumen: [work item](../work-items/operational-lifecycle/README.md).
