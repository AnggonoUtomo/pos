# ADR-018: Penerimaan Uang Retur Supplier Exact6

Status: Accepted. Tanggal: 22 Oktober 2026.

## Konteks

Retur supplier mengeluarkan stok dari layer receipt asal. Pengeluaran tersebut
tidak membuktikan uang kembali. Pembelian dibayar exact6; menyamakan nominal
dengan HPP FIFO atau ledger sale exact2 akan mengubah nilai historis.

## Keputusan

- Penyelesaian uang hanya retur posted dari pembelian yang sudah lunas,
  satu kali penuh, multi-metode aktif dan jumlah tepat tanpa kembalian.
- Nominal otomatis dari nilai beli snapshot per baris secara proporsional
  kuantitas, bukan nominal bebas/HPP/harga master. Partial round down6 dan
  penghabisan seluruh hak mengambil residual; total tidak melebihi paid asal.
- Ledger Finance kas masuk supplier terpisah exact6. Ledger sale/refund sale
  existing tetap exact2; pengeluaran pembelian asal exact6 immutable.
- Returns memiliki scope/revision/alokasi/settlement; Finance memiliki receipt
  dan proof pembayaran asal. Komunikasi memakai contract/DTOs publik.
- Quote hanya preview. Settlement mengunci scope per pembelian dan memvalidasi
  fingerprint; receipt/status/audit atomic. Replay identik mengembalikan hasil
  lama sebelum validasi master live; permintaan berbeda ditolak.
- Metode aktif boleh berbeda dari pembayaran asal. Nilai nol diselesaikan
  sebagai tanpa uang dengan audit, tanpa membuat ledger uang nol.

## Konsekuensi

Penerimaan uang tidak mengubah Stock/FIFO/purchase/payment asal. Kredit supplier,
pembelian belum lunas, penerimaan parsial, utang dan reversal memerlukan keputusan
terpisah. Demo/aktivasi tidak boleh otomatis menerima uang pada data bisnis.
UI memakai format Indonesia dengan pecahan koma dan tanpa nol trailing wajib.
