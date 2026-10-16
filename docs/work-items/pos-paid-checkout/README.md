# Work Item: Checkout POS Lunas Dan Multi-Payment

## Status

Done. Scope dan aturan kembalian disetujui user 15 Oktober 2026.
Increment 1..6 selesai: presisi, Stock/Finance, posting POS atomic, UI
multi-payment/recovery/receipt dan verifikasi/delivery. Push main dikonfirmasi
16 Oktober 2026; bukti ada pada tasks.md. Selesai dalam scope, bukan klaim siap
produksi tanpa keputusan retention/recovery dan pengujian operasional tambahan.

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

- [x] Checkout lunas nyata multi-metode, kembalian tunai dan bukti transaksi.
- [x] FIFO/HPP, saldo dan kartu stok keluar konsisten; stok tidak minus.
- [x] Seluruh mutation/audit atomic; retry tidak menggandakan transaksi.
- [x] Snapshot historis stabil sesudah rename/ubah konfigurasi master.
- [x] Permission backend, invalid/stale/tampered/insufficient stock ditolak.
- [x] QA otomatis desktop/tablet serta MySQL concurrency dan data lokal aman.
- [x] Penutupan keseluruhan, review delivery dan push Increment 6.

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

## Desain Increment 2

Stock memiliki stock_issues (sumber sale/ULID/nomor/gudang/hash/actor/waktu),
stock_issue_lines (source_line_id/item/base unit/snapshot/quantity/nilai) dan
fifo_consumptions (movement/layer/quantity/nilai). Tidak ada soft delete.
Referensi sale/line tetap opaque ULID, tanpa FK circular ke tabel POS yang
ditambahkan pada Increment 4. Semua FK internal Stock restrict; source_type/source_id dan line source
unik. Source sama/payload normal sama replay tanpa mutation; payload beda ditolak.

stock_movements.opening_line_id menjadi nullable, tambah issue_line_id unique
dan snapshot sumber untuk pengeluaran. Opening existing tidak dibackfill/diubah.
Movement sale quantity/nilai signed negatif; issue line/consumption positif.
Query movement memakai snapshot sendiri dan fallback left join opening lama.
Balance tetap memakai identitas opening existing; saldo nol tidak dihapus.

IssueStock awalnya action internal; Increment 4 memasang SaleStockWriter untuk
consumer PostInvoice, tanpa route pengeluaran bebas.
Input base_quantity berasal dari konversi authoritative POS kelak, bukan input
harga/rasio bebas. Memakai lookup publik Warehouse/Units/Items. Lock gudang,
base units terurut, items terurut, balance lalu layer received_at/id; gudang
menserialisasi mutation setempat mengikuti posting opening existing. Balance
quantity/value harus sama dengan sisa layer sebelum setiap consumption.
Atomic data/audit, no-negative, actor eksplisit dan waktu server. Fixture demo
transaksi hanya disposable. Schema lokal diterapkan additive pada increment ini
setelah QA MySQL agar query kartu tetap berjalan, dengan fingerprint seluruh
kolom existing; tidak membuat pengeluaran pada database pengguna.
Down migration menolak bila pengeluaran nyata ada, bukan menghapus histori.

## Handoff Saat Ini

### Implementasi Increment 4

POST /pos/invoices menerima idempotency_key ULID, warehouse_id/customer_id,
lines item_id/unit_id/quantity, quote_fingerprint dan payments method_id/amount.
Input tidak menerima harga/rasio/HPP/tax/discount/status/actor/nomor invoice.
CheckCart menambah fingerprint deterministic (tanpa checked_at), bukan reservasi.
Posting menghitung ulang metadata/harga/stock dengan current locks; fingerprint
berubah ditolak422 dan perlu quote ulang. Snapshot diskon/pajak eksplisit nol.

Tabel sales_invoices dan sales_invoice_lines memakai ULID, tanpa soft delete.
Header menyimpan nomor POS-ULID, snapshot gudang/pelanggan/level, total/HPP,
payment snapshot aman dan referensi opaque Stock issue/Finance receipt.
Line menyimpan snapshot SKU/nama/satuan/base/ratio/quantity/harga/subtotal/HPP.
FK master RESTRICT; source Stock/Finance tetap opaque agar tidak circular FK.
Down ditolak jika invoice posted ada. Tidak ada edit/delete/reversal endpoint.

Unique actor_id/idempotency_key dan hash payload canonical mengikat retry.
Lock gudang mendahului lookup retry current; replay dikembalikan dari snapshot
sebelum membaca master lain. Race lintas gudang ditutup unique dan lookup ulang
sesudah rollback collision. Retry dengan payload beda ditolak, actor berbeda
memiliki ruang key sendiri. Nomor berasal server, bukan sequence bebas client.

Lock order transaksi: gudang -> active levels terurut -> customer level/customer
(discovery assignment diperiksa ulang) -> unit terurut -> item/item_units terurut
-> harga -> balance/layer -> akun/metode -> invoice/audit. Urutan selaras mutation
master existing (level -> unit -> item -> price dan account -> method). Discovery
unit/base dan customer level tidak authoritative; current recheck menolak drift.
Stock dan Payments dipanggil melalui published contracts/DTOs yang langsung
memiliki consumer PostInvoice; tidak mengakses model/repository privat.

Create dan post dibedakan: sales.pos.invoices.create tetap untuk workspace/quote,
posting/choices wajib create+sales.pos.invoices.post. Detail JSON boleh pemilik
dengan post atau staff sales.pos.invoices.view; frontend tidak menerima HPP,
hash/request mentah, kontak pelanggan atau metadata rekening. Tidak grant/reset
akun. GET /pos/payment-methods hanya pilihan aman, bukan CRUD Finance.

QA action/HTTP menginjeksi gagal pada Stock/Finance/persistence/audit, retry,
harga/master/stok berubah, exact/multi-satuan dan permission. MySQL disposable
menguji RR current reads serta worker checkout bersamaan. Local hanya additive
migration/permission catalog dan fingerprint, tanpa posted/demo transaksi user.
UI checkout aktif sejak Increment 5, theme tidak diubah.

Increment 4 full suite: 406 tests/3193 assertions PASS; backend PostInvoice,
published boundary Stock/Finance, current lock/fingerprint, snapshot/idempotency,
net payment dan audit atomic teruji. MySQL native/RR stale/worker replay/last-stock
dan FK PASS. Regresi browser POS desktop/tablet Light/Dark PASS; DevTools BLOCKED
karena tool tidak tersedia pada sesi ini. Frontend/theme tidak diubah.
Migration POS Ran batch13 dan catalog permission additive; fingerprint bisnis
existing identik, tanpa grant/reset atau invoice pengguna.
Delivery keseluruhan ditutup pada Increment 6.

### Implementasi Increment 5

UI /pos mengaktifkan Pembayaran setelah quote valid dengan permission create+post;
admin mengatur grant kasir secara eksplisit. Dialog memakai metode nyata dan angka
Indonesia/exact, satu cash, sisa/kembalian dan loading/error/reload. Success hanya
setelah snapshot backend valid, lalu cart kosong dan katalog stok dimuat ulang.
Pending disimpan per user/tab sebelum POST, membekukan payload/key/context/cart;
retry network/5xx/timeout dan reload tidak membuat invoice baru. 422 definitif
mempertahankan cart dan membatalkan quote. Receipt terakhir dapat dimuat ulang
dari server dan dicetak dengan browser, tanpa workspace/overlay atau data privat.

Full suite 406 tests/3193 assertions, TS/lint/build2711, helper exact dan regresi
POS browser PASS. QA checkout desktop/tablet Light/Dark meliputi double submit,
response hilang setelah commit lalu retry, stale quote, permission create-only,
storage unavailable dan print media. DevTools BLOCKED karena tool tidak tersedia;
fallback Playwright PASS pada SQLite disposable, bukan database pengguna.
Tidak ada schema/permission/grant/dependency berubah pada Increment 5.

Rincian hasil increment dan command ada di tasks.md. Data pengguna, Old-docs,
SampleUI dan LSP tidak disentuh. Recovery tab bukan offline queue/recovery lintas
perangkat; tab/storage hilang perlu rekonsiliasi sebelum membuat transaksi baru.
Printer fisik dan pagination receipt panjang belum diuji; retention/ownership
akun dan granularity lock konservatif tetap risiko sebelum produksi.

### Verifikasi Akhir Increment 6

Full suite406/3193, TS/ESLint/build2711, Pint scope, helper exact, tiga QA MySQL
checkout/Stock/Finance dan browser checkout/regresi desktop/tablet Light/Dark
lulus. MySQL disposable dibersihkan; aktivasi lokal Nothing to migrate dengan
fingerprint seluruh data bisnis existing/grant identik. Semua migration terkait
Ran. Smoke pos.test dan21 asset build200, tanpa membutuhkan Vite aktif.
Tidak ada fitur baru atau perubahan behavior pada increment penutupan.
Hasil/artefak dan batas produksi tercatat pada tasks.md. Push main ke origin
716946c telah dikonfirmasi lewat ls-remote dan divergence0/0; commit penutup
checklist disinkronkan sesudah bukti delivery ini.
