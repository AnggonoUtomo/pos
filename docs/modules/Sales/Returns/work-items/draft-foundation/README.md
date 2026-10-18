# Fondasi Draft Retur Penjualan

Status: implementasi/QA dan aktivasi lokal selesai; delivery remote diverifikasi
pada checkpoint akhir. User menyetujui draft dahulu, tanpa efek ledger.

Tujuan: operator mencatat permintaan retur berdasarkan snapshot invoice asli
tanpa input ulang barang/harga dan tanpa mengubah transaksi posted.

Scope dan acceptance: [Spesifikasi module](../../specification.md).
Urutan kerja: [Rencana](plan.md). Bukti dan checklist: [Tasks](tasks.md).

Risiko utama: snapshot bocor HPP, kuantitas berlebih, lost update, audit gagal,
draft dianggap refund berhasil, dan seed mengubah transaksi user. Semua harus
dibuktikan melalui test dan fingerprint, bukan hanya struktur module.

Hasil: draft tersedia di `/sales/returns`, migration Ran batch20, katalog
permission additive tanpa grant/demo/reset. Full regression 504 test/3970
assertion PASS; DevTools MCP, Playwright desktop/tablet dan native MySQL PASS.
Tidak ada posting/refund/reservasi atau perubahan invoice/stock/finance asal.
