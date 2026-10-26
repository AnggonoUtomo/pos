# Panduan Role: Staf Keuangan

**Tujuan:** memastikan akun/metode dan penyelesaian uang sesuai bukti nyata.
Usulan role `finance-staff`, belum otomatis dibuat. Hak keuangan tidak otomatis
berarti hak menerima fisik stok atau mengoreksi transaksi posted.

## Tahap 1: Siapkan Akun Dan Metode

1. Dengan izin yang sesuai, buka **Kas dan bank** dan buat akun yang disahkan.
2. Buka **Metode pembayaran**, pilih jenis Tunai/Debit/Transfer dan akun tujuan.
3. Periksa rekening/kas nyata serta status aktif sebelum dipakai kasir.
4. Cocokkan kembali setelah perubahan master; pembayaran lama tetap memakai snapshot.

Membuat akun tidak memasukkan saldo awal. Jangan menyebut riwayat pembayaran
sebagai saldo rekening bank yang sudah terbukti.

## Tahap 2: Pembayaran Pembelian

1. Minta nomor draft, nota serta hasil cek fisik dari gudang.
2. Untuk draft baru, **Terima dan bayar** membutuhkan izin penerimaan stok
   (post) dan pembayaran (pay) sekaligus.
3. Jika hanya punya pay, serahkan ke petugas yang ditunjuk memegang keduanya.
   Jangan meminta izin post hanya untuk memunculkan tombol tanpa tanggung jawab fisik.
4. Petugas berwenang memilih metode/nominal dengan total tepat tanpa kembalian,
   lalu mengonfirmasi **Catat pembayaran**.
5. Periksa Diterima/Lunas dan riwayat pembayaran.

Pembelian lama yang sudah Diterima tetapi **Belum dicatat** pembayarannya dapat
dibayar melalui **Bayar pembelian**, tanpa memasukkan stok lagi. Tidak ada
pembayaran parsial/utang pada tahap ini. Jangan mencatat pembayaran yang uangnya
belum benar dikeluarkan hanya agar status terlihat Lunas.

## Tahap 3: Refund Pelanggan

1. Minta invoice dan nomor **Retur Penjualan** yang sudah diterima barangnya.
2. Pilih **Refund**, cocokkan nominal otomatis dengan transaksi asal.
3. Pilih metode aktif/akun keluar; boleh berbeda dari metode invoice asal.
4. Isi total tepat, lalu **Konfirmasi refund** setelah uang benar diserahkan.
5. Periksa status dan riwayat. Jika hasil tidak pasti, gunakan **Periksa ulang
   hasil**, jangan menyerahkan uang dua kali.

Nominal memakai nilai bersih penjualan asal dengan dua desimal; pembulatan
bagian terakhir dijaga sistem. Tidak ada input nominal bebas, kembalian atau
refund parsial. Nilai nol diselesaikan **Tanpa pengembalian uang**, bukan ledger nol.

## Tahap 4: Uang Kembali Dari Supplier

1. Pastikan **Retur Pembelian** sudah diposting serah barang dan pembelian asal lunas.
2. Setelah uang benar diterima, buka **Terima uang supplier**.
3. Periksa nilai otomatis dari nilai beli snapshot; bukan HPP FIFO/harga jual.
4. Pilih metode/akun masuk, isi total tepat, lalu **Konfirmasi penerimaan** sekali.
5. Pastikan **Uang diterima penuh** dan cocokkan bukti.

Presisi perhitungan enam desimal; bagian akhir menjaga sisa pembulatan. Nilai
nol diselesaikan tanpa pembayaran nol. Belum ada penerimaan uang parsial atau
pengurangan utang supplier. Tahap uang tidak mengurangi stok lagi.

## Tahap 5: Rekonsiliasi Akhir Hari

1. Buka **Pembayaran**, gunakan tanggal/arah/akun/metode/nomor transaksi.
2. Cocokkan empat sumber: penjualan masuk, pembelian keluar, refund pelanggan
   keluar dan refund supplier masuk.
3. Untuk tunai POS, yang dicatat bersih setelah kembalian. Tagihan Rp100.000,
   uang tunai Rp150.000, kembalian Rp50.000 berarti masuk bersih Rp100.000.
4. Buka detail snapshot dan bukti bank/kas. Catat selisih dengan nomor sumber.
5. Sampaikan ringkasan kepada pemilik; laporan operasional memerlukan izin tersendiri.

Riwayat readonly, tanpa tambah manual/edit/hapus uang. Selisih masuk-keluar
bukan laba atau saldo berjalan; saldo awal dan aktivitas luar aplikasi belum
otomatis dihitung. Koreksi lewat penanggung jawab transaksi, bukan edit database.

## Checklist Keuangan

- [ ] Akun/metode sesuai kas/rekening yang disahkan.
- [ ] Fisik pembelian telah diperiksa sebelum Terima dan bayar.
- [ ] Refund/penerimaan supplier mengacu retur posted dan belum diselesaikan.
- [ ] Nominal otomatis dan jumlah metode tepat; bukti uang benar tersedia.
- [ ] Empat sumber riwayat sesuai arah, periode dan akun.
- [ ] Hasil yang belum pasti ditelusuri tanpa pembayaran ganda.

Rincian: [panduan modul](../02-panduan-modul.md).
Paket izin: [role dan akses](../03-role-dan-akses.md).
