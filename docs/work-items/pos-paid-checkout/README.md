# Work Item: Checkout POS Lunas Dan Multi-Payment

## Status

In Progress. Scope dan aturan kembalian disetujui user 15 Oktober 2026.
Increment 1 mengunci dokumen dan perhitungan Domain saja; checkout belum aktif.

## Owner Dan Lokasi

- Sales/POS: orkestrasi checkout, dokumen penjualan/snapshot, idempotency,
  permission, UI kasir dan bukti transaksi.
- Inventory/Stock: pengeluaran saldo, movement, alokasi layer dan nilai FIFO.
- Finance/Payments: pembayaran penjualan, snapshot metode/akun dan kas bersih.
- Kode: app/Modules/{Sales/POS,Inventory/Stock,Finance/Payments}.
- UI tetap resources/js/pages/sales/pos; tidak membuat design system lain.
- Requirement: [PRD](PRD.md). Urutan: [plan](plan.md). Bukti: [tasks](tasks.md).
- Keputusan presisi: [ADR-012](../../decisions/ADR-012-PAID-CHECKOUT-AND-FIFO-RESIDUAL.md).

## Kondisi Awal

Katalog/barcode, keranjang dan quote POS nyata sudah tersedia. Quote tidak
mereservasi stok atau menjamin harga posting. Master kas/bank dan metode bayar
aktif, tetapi belum ada payment record. Stock memiliki saldo dan layer masuk;
FIFO keluar belum tersedia. Basis sebelum pekerjaan: commit 12b510b.

## Scope Dan Aturan

- Satu gudang dan pelanggan aktif; level pelanggan/satuan menentukan harga.
  Posting membaca dan mengunci konfigurasi yang diperlukan, bukan percaya quote.
- Penjualan wajib lunas; satu sampai sepuluh metode unik per checkout. Jenis dan
  akun berasal dari master aktif, bukan field yang ditentukan kasir.
- Maksimal satu baris cash. Debit/transfer dijumlah dahulu dan tidak boleh
  melebihi tagihan, terlepas urutan input. Sisa harus ditutup tunai bila ada.
- Tunai boleh lebih bayar; selisih adalah kembalian dari akun cash tersebut.
  Setiap baris harus memberi kontribusi positif pada pelunasan. Tunai tambahan
  setelah non-tunai sudah lunas ditolak, bukan transaksi kas masuk/keluar palsu.
- Tagihan Rp100.000: transfer Rp60.000 + tunai Rp50.000 menghasilkan kembalian
  Rp10.000, penerimaan bank Rp60.000 dan kas bersih Rp40.000.
- Uang pembayaran exact scale2; quantity base dan nilai HPP FIFO exact scale6.
  Domain memakai brick/math existing, bukan float. UI memakai format Indonesia.
- FIFO per item/gudang, urut received_at lalu id. Alokasi parsial membulatkan
  turun nilai pada enam pecahan; pengurasan layer mengambil seluruh residual.
- Penjualan, payment record/net cash, saldo/movement/alokasi FIFO dan audit
  berhasil bersama pada satu transaction MySQL default atau seluruhnya rollback.
- Posted immutable, tanpa soft delete/edit/hapus bebas. Simpan waktu server,
  actor dan snapshot barang/SKU/satuan/rasio, gudang, pelanggan/level, harga,
  HPP serta metode/akun. Nomor dokumen server dengan ULID, tanpa backdating.
- Diskon dan pajak belum dapat diinput pada tahap ini. Snapshot eksplisit nol
  pada dokumen posted; bukan fallback atas input client atau engine pajak aktif.
- Retry memakai kunci idempotency dan fingerprint payload normal. Kunci sama,
  actor sama dan payload sama mengembalikan transaksi sama tanpa mutation baru;
  payload berbeda ditolak. Response hilang tidak membolehkan double posting.

## Tidak Dikerjakan

Piutang/penjualan kredit, perhitungan diskon/pajak, retur/void/reversal UI,
shift/closing, payment gateway, QRIS, fee/settlement, rekening eksternal,
saldo awal uang dan full accounting. Payment record bukan laporan saldo bank
lengkap. Tidak mengubah theme, grant/akun/password atau dependency.

## Acceptance Criteria

- [ ] Checkout lunas nyata multi-metode, kembalian tunai dan bukti transaksi.
- [ ] FIFO/HPP, saldo dan kartu stok keluar konsisten; stok tidak minus.
- [ ] Seluruh mutation/audit atomic; retry tidak menggandakan transaksi.
- [ ] Snapshot historis stabil sesudah rename/ubah konfigurasi master.
- [ ] Permission backend, invalid/stale/tampered/insufficient stock ditolak.
- [ ] QA otomatis desktop/tablet serta MySQL concurrency dan data lokal aman.

## Dampak Dan Risiko Yang Sudah Diidentifikasi

1. stock_movements.opening_line_id saat ini wajib/unique. Migration additive
   Stock harus mengizinkan sumber sale tanpa mengubah movement masuk existing.
2. Query kartu stok memakai inner join stok awal. Jika tidak disesuaikan,
   movement penjualan akan tersembunyi. Perubahan query milik Stock saja;
   simpan snapshot sumber sendiri, bukan join model privat POS.
3. Quote/read contract saat ini non-locking. Jangan menggunakan CheckCart
   sebagai otorisasi final posting. Audit lock order shared dengan edit master,
   pricing, stok awal dan metode bayar sebelum contract locking diperluas.
4. Metode/akun yang dirujuk payment record memerlukan guard/snapshot historis;
   audit log tidak menggantikan ledger uang maupun stok.
5. Increment 1 belum mengklaim row lock, migration, idempotency HTTP atau
   perubahan saldo. Port/event baru hanya dibuat saat consumer nyata terpasang.

## Handoff Saat Ini

Rincian hasil increment dan command ada di tasks.md. Data pengguna, Old-docs,
SampleUI dan LSP tidak boleh disentuh. Work item belum selesai hanya karena
perhitungan Domain lulus; pembayaran UI tetap disabled sampai alur lengkap lolos.
