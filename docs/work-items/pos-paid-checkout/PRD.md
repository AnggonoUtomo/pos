# PRD: Checkout POS Lunas

## Status

Disetujui untuk scope lunas/multi-payment dan satu baris tunai dengan kembalian.
Implementasi bertahap; fitur belum aktif pada Increment 1.

## Masalah Dan Tujuan

Kasir toko CCTV/kelistrikan sudah dapat memilih gudang, pelanggan, barang dan
satuan, tetapi belum dapat menyelesaikan penjualan nyata. Tujuan berikutnya
adalah satu checkout yang menyimpan penjualan, menerima pembayaran lunas,
mengurangi stok dan menghitung HPP FIFO tanpa transaksi setengah jadi.

## Pengguna

Kasir desktop/tablet; admin mengatur master metode/akun dan hak akses. Kasir
tidak harus mendapatkan permission CRUD Finance untuk menerima pembayaran.

## Requirement

- Harga berasal dari matrix pelanggan/satuan; nominal bukan hasil input harga
  bebas client. Kabel per meter/rol serta barang per pcs/dus tetap memakai
  konversi per barang dan stok base unit.
- Multi-payment mendukung cash/debit/transfer aktif. Satu metode tidak berulang,
  maksimal satu cash, lunas wajib; kembalian hanya dari cash.
- Tampilkan total, dibayar, sisa dan kembalian dengan angka Indonesia.
  Success hanya sesudah backend commit, bukan setelah menekan tombol.
- Perubahan stok/harga/context sesudah quote wajib terdeteksi saat posting.
  Total berubah memerlukan pemeriksaan dan persetujuan kasir ulang, bukan
  menerima nominal lama atau mengubah harga diam-diam.
- Retry/double click tidak membuat invoice, payment atau stok keluar ganda.
- Bukti transaksi berasal dari snapshot posted; gagal posting mempertahankan
  keranjang untuk koreksi/retry. Cetak memakai kemampuan browser, bukan driver
  printer/gateway baru.
- Kartu stok menampilkan pengeluaran beserta referensi dokumen sumber.

## Di Luar Scope

Kredit/piutang, input diskon/pajak, retur/void, shift, gateway, fee, full
accounting dan mobile. Snapshot diskon/pajak nol tidak dianggap engine aktif.

## Acceptance Criteria

- [ ] Contoh transfer Rp60.000 + tunai Rp50.000 untuk tagihan Rp100.000 menghasilkan
  kembalian Rp10.000 dan total penerimaan bersih Rp100.000.
- [ ] Tunai ganda, metode unavailable, non-tunai berlebih dan kurang bayar ditolak.
- [ ] Stok tidak minus, seluruh uang/stok/audit rollback bila satu tahap gagal.
- [ ] Retry identik memberikan nomor/snapshot sama tanpa pengeluaran ulang.
- [ ] Desktop/tablet Light/Dark, permission dan network error terverifikasi.

## Pertanyaan Terbuka

Tidak ada blocker aturan pelunasan. Detail DDL/lock order dan contract HTTP
harus diverifikasi terhadap source pada increment integrasi sebelum dibuat.
