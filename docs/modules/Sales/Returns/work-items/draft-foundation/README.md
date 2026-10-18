# Fondasi Draft Retur Penjualan

Status: dalam pengerjaan. User menyetujui draft dahulu, tanpa efek ledger.

Tujuan: operator mencatat permintaan retur berdasarkan snapshot invoice asli
tanpa input ulang barang/harga dan tanpa mengubah transaksi posted.

Scope dan acceptance: [Spesifikasi module](../../specification.md).
Urutan kerja: [Rencana](plan.md). Bukti dan checklist: [Tasks](tasks.md).

Risiko utama: snapshot bocor HPP, kuantitas berlebih, lost update, audit gagal,
draft dianggap refund berhasil, dan seed mengubah transaksi user. Semua harus
dibuktikan melalui test dan fingerprint, bukan hanya struktur module.
