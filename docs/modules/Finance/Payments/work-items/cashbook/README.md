# Buku Kas

Status: selesai dan diaktifkan additive, bukti pada [checklist](tasks.md).
User menyetujui saldo awal/tanggal mulai per
akun serta saldo minus sebagai peringatan, bukan pengaman kecukupan dana.

Ownership Finance/Payments. Scope: saldo awal satu kali per akun kas/bank,
pemasukan/pengeluaran operasional, transfer antar-akun atomik, pembalik mutasi
manual, daftar mutasi per akun dengan saldo berjalan dan filter tanggal WIB.
Tidak ada shift, full accounting, bank integration, edit/hapus ledger posted,
atau perubahan pengaman stok, checkout, pembelian dan refund existing.

## Aturan

- Saldo awal adalah posisi sebelum transaksi tanggal mulai pukul 00.00 WIB.
  Riwayat sebelum batas tetap utuh dan tidak ikut saldo berjalan. Pembayaran
  existing sejak batas dibaca langsung, tidak disalin/backfill/dicatat ulang.
- Perhitungan per baris akun, bukan total dokumen multi-payment. Penjualan
  memakai applied_amount (setelah kembalian). Pembelian/refund supplier exact6,
  penjualan/refund pelanggan exact2; Buku Kas mempertahankan enam desimal.
- Akun belum diinisialisasi tidak dianggap bersaldo nol. Tidak ada inisialisasi
  atau saldo rekaan pada database toko. Admin mengisi lewat UI secara eksplisit.
- Saldo awal immutable setelah dibuat; salah posisi dikoreksi mutasi audited.
  Nominal saldo awal boleh bertanda untuk mencatat posisi aktual. Mutasi manual
  wajib positif, tanggal >= awal akun dan tidak melebihi tanggal hari ini WIB.
  Transfer memerlukan dua akun berbeda yang aktif dan sudah diinisialisasi.
- Mutasi manual langsung tercatat setelah konfirmasi; bukan CRUD draft. Reversal
  membuat baris lawan pada waktu pembalikan, tidak menulis ulang baris asal.
  Hanya mutasi manual yang dapat dibalik satu kali; ledger transaksi asal
  dikoreksi dari workflow bisnis pemiliknya, bukan dari Buku Kas.
- Saldo negatif ditandai; tidak menambah larangan saldo pada writer existing.
- Permission view/manage/open/reverse terpisah. Catalog additive tanpa grant
  diam-diam pada role/akun. Audit wajib dalam transaksi yang sama.
- Angka backend decimal string; input/tampilan Indonesia. Activity log bukan
  sumber saldo. Retry memakai idempotency key dan payload canonical.

QA fixture/demo hanya disposable/local/testing; tidak menghapus dataset toko,
menetapkan cutover produksi, atau mengubah snapshot pembayaran lama.

Produksi lokal: migration batch31 Ran; empat permission tersedia, opening dan
mutasi masih kosong. Administrator menetapkan akses role dan posisi awal nyata.
Panduan: [Buku Kas](../../../../../user-guide/BUKU-KAS.md).
