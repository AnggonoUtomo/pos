# Work Item: Refund Retur Penjualan Lunas

## Status

In Progress. Increment 1..5 Passed; QA dan aktivasi Increment 6 Passed, delivery
Git sedang ditutup. Dua migration refund lokal Ran batch22, catalog aktif tanpa
grant/demo/payout. Fingerprint existing terjaga pada dua aktivasi.
Increment 1 hanya dokumentasi, bukan aktivasi fitur refund.

## Owner Dan Lokasi

- `Sales/Returns`: hak refund dari snapshot invoice, alokasi nominal per baris,
  penyelesaian retur, permission, audit dan UI.
- `Finance/Payments`: validasi metode/akun, pengeluaran refund immutable,
  snapshot pembayaran, audit dan integrasi riwayat pembayaran.
- `Sales/POS`: penyedia snapshot invoice melalui public contract existing;
  perubahan kontrak hanya bila consumer refund benar-benar memerlukan.
- `Inventory/Stock`: hanya target pemeriksaan bahwa refund tidak memutasi stok.
- Kode sasaran: `app/Modules/Sales/Returns`, `app/Modules/Finance/Payments`,
  `resources/js/pages/sales/returns`, dan UI riwayat Finance bila diperlukan.
- Dokumen: [Rencana](plan.md) dan [Checklist](tasks.md).

## Kondisi Awal

Draft dan posting fisik retur sudah tersedia. `PostReturnDraft` menerima barang
ke gudang asal dengan biaya dari konsumsi FIFO penjualan. Belum ada refund,
status penyelesaian uang, quote nominal, maupun ledger pengeluaran refund.

Snapshot publik invoice menyediakan kuantitas, satuan, subtotal, diskon dan
pajak baris sebagai decimal string. Finance mempunyai penerimaan penjualan
scale2 dan pengeluaran pembelian scale6. `RecordPurchasePayment` menggunakan
source_type purchase; itu bukan writer refund yang bisa dipakai langsung.
Riwayat Finance saat ini membaca penerimaan dan pengeluaran pembelian.

## Scope Disetujui

- Hanya retur barang yang telah diterima/posted; tidak membayar draft.
- Satu penyelesaian penuh per dokumen retur, boleh gabungan metode pembayaran
  aktif, total tepat tanpa kembalian.
- Nominal ditentukan server dari nilai bersih baris invoice asal, proporsional
  terhadap kuantitas, bukan harga master terkini atau input nominal bebas.
- Presisi uang dua desimal; alokasi sebagian dibulatkan ke bawah, sedangkan
  penyelesaian seluruh sisa kuantitas mengambil sisa nominal. Nilai kumulatif
  tidak boleh melampaui nilai bersih baris asal.
- Metode refund boleh berbeda dari metode invoice asal. Akun keluar diturunkan
  dari master metode aktif/kompatibel dan disimpan sebagai snapshot.
- Nominal Rp0,00 dapat diselesaikan sebagai **Tanpa pengembalian uang** dengan
  permission dan audit, tanpa metode atau ledger pembayaran nol. Kuantitas
  yang diselesaikan tetap tercatat agar sisa pembulatan tidak hilang.
- Permission refund terpisah dari view/manage/post fisik. Backend adalah
  authority; super-system tetap tunduk invariant bisnis.
- Pengeluaran, alokasi hak refund, metadata penyelesaian dan audit berhasil
  bersama atau seluruhnya rollback. Retry tidak membayar atau mengaudit ganda.
- Invoice, penerimaan uang asal, posting fisik, movement, layer dan konsumsi
  FIFO tidak berubah. HPP bukan dasar refund dan tidak ditampilkan.
- Riwayat Finance menampilkan pengeluaran refund positif sebagai transaksi
  keluar yang dapat dikenali dari snapshot; penyelesaian nol tidak membuat
  baris transaksi uang fiktif.

## Tidak Dikerjakan

Pembayaran parsial, uang muka, kredit pelanggan, tukar barang, refund otomatis
saat posting fisik, barang rusak/karantina, reversal/void refund, transfer bank
nyata, running balance, pengecekan kecukupan saldo kas, fee, utang/piutang,
full accounting, input diskon/pajak baru, maupun perubahan tema.

Tidak mengubah data lama menjadi sudah direfund, tidak membuat pembayaran
historis otomatis, dan tidak mengubah transaksi posted lewat soft delete.

## Acceptance Criteria

- [x] Hanya retur posted yang belum diselesaikan dapat direfund.
- [x] Nilai dihitung exact dari snapshot asal; total per baris terjaga sampai
  sisa kuantitas dan nominal habis, termasuk alokasi nol dan nominal besar.
- [x] Dua proses pada invoice sama tidak membayar melebihi hak refund.
- [x] Multi-payment positif tepat jumlah, metode berbeda dari asal dibolehkan,
  dan akun/metode tidak valid ditolak tanpa efek samping.
- [x] Penyelesaian nol diaudit tanpa ledger uang dan tanpa metode pembayaran.
- [x] Replay identik stabil; payload berbeda, quote stale dan mutation setelah
  settlement ditolak sesuai contract yang dikunci sebelum implementasi.
- [x] Audit gagal membatalkan seluruh settlement dan ledger, termasuk setelah
  writer Finance berhasil.
- [x] Histori refund tetap terbaca dari snapshot setelah rename master;
  histori penerimaan dan pembelian existing tidak berubah makna/presisinya.
- [x] UI menampilkan nominal/status sebenarnya, Sonner, shortcut, permission,
  serta state loading/error/readonly pada desktop dan tablet.
- [ ] Aktivasi lokal additive menjaga data existing; seluruh QA yang relevan
  selesai, checklist berbukti, commit dan remote delivery terverifikasi.

## Dampak Yang Direncanakan

| Area | Dampak dan batas |
| --- | --- |
| Schema | Alokasi kuantitas/nominal dan metadata settlement milik Returns; ledger refund milik Finance. Desain final dikunci pada Increment 2 sebelum migration. |
| Route | Quote dan settlement Returns; rincian nama/payload dicatat sebelum coding endpoint. Tidak membuka endpoint Finance pengeluaran bebas. |
| Permission | Catalog refund terpisah, additive, tanpa auto-grant/reset role. |
| UI | Konfirmasi refund/status pada Returns dan pembacaan pengeluaran di riwayat Finance. |
| Seeder demo | Tidak membuat payout otomatis; fixture CCTV/kelistrikan memakai DB disposable, alasan skip seeder transaksi dicatat. |
| Audit | Settlement Returns dan payout Finance atomik; tanpa rekening, credential, catatan pribadi atau payload penuh. |
| Koreksi | Settlement/ledger immutable tanpa soft delete; reversal di luar scope. |
| Stok/FIFO | Tidak dimutasi; fingerprint sebelum/sesudah harus sama. |
| Harga/diskon/pajak | Membaca nilai bersih snapshot; tidak menghitung ulang master atau menambahkan input baru. |

## Operasi Aktivasi Dan Batas Koreksi

Jalankan `php scripts/qa/activate-sale-return-refund-local.php` hanya CLI pada
local/MySQL, saat tidak ada transaksi atau perubahan user bersamaan. Script
memverifikasi database aktual, baseline tabel dan advisory lock aktivasi; hanya
dua migration refund tertentu serta catalog Returns yang dijalankan. Fingerprint
kolom/data existing, role/grant, source/Stock/Finance/audit dan metadata existing
harus tetap; cache permission boleh diinvalidate. Ulangi untuk membuktikan no-op.

QA: `php scripts/qa/activate-sale-return-refund-mysql.php` membuat database
random, mencoba aktivasi dua kali pada retur fisik existing, lalu mengulang
setelah settlement fixture. Override --qa-database hanya menerima namespace
disposable khusus, bukan nama database bebas. QA bukan instruksi mengisi data
pada database user.

Normal user memerlukan sales.returns.view untuk halaman dan sales.returns.refund
untuk quote/konfirmasi; finance.payments.history.view untuk riwayat uang.
Aktivasi tidak memberi grant. Role super-system bypass authorization sesuai
ADR-010, tidak melewati validasi/invariant refund. Retur posted lama tetap belum
refund, bukan otomatis diselesaikan. UI `/sales/returns`, history
`/finance/payments/history`.

Tidak ada seeder payout refund: pengeluaran bukan data master/demo yang boleh
dibuat saat aktivasi. Demo master CCTV/kelistrikan dipertahankan, sedangkan
transaksi refund relasional hanya dibuat fixture QA disposable. Tidak ada
reset akun, demo ulang, refund otomatis, perubahan harga, atau mutation stok.

DDL MySQL tidak rollback otomatis bersama transaction catalog. Bila pemeriksaan
gagal, hentikan aktivasi dan periksa penyebab; script exit nonzero tanpa mencetak
data sensitif. Jangan drop/reset tabel atau menjalankan rollback posted untuk
memaksa recovery. Setelah settlement ada, koreksi memerlukan forward fix atau
work item reversal yang disetujui; reversal belum tersedia.

## Dependency Dan Keputusan

- [Arsitektur](../../ARCHITECTURE.md), [struktur](../../FOLDER-STRUCTURE.md),
  [workflow](../../WORKFLOW.md), dan [QA](../../QUALITY.md).
- [Spesifikasi Returns](../../modules/Sales/Returns/specification.md) dan
  [Finance/Payments](../../modules/Finance/Payments/specification.md).
- [Posting fisik](../sale-return-posting/README.md) sudah selesai; biaya FIFO
  ADR-015 tetap terpisah dari nominal refund pelanggan.
- Keputusan interview dalam scope di atas disetujui user. PRD tambahan tidak
  diperlukan saat ini karena kebutuhan dan non-scope sudah eksplisit.
- [ADR-016](../../decisions/ADR-016-SALE-RETURN-REFUND-SETTLEMENT.md) mengunci
  valuasi, ledger refund decimal30,2, scope invoice dan contract yang dirancang.
  Interface/DTOs runtime diterbitkan saat consumer operasional tersedia pada
  Increment 3/4; tidak membuat abstraction kosong pada Increment 2.

## Increment

| No | Status | Hasil | Verifikasi |
| --- | --- | --- | --- |
| 1 | Passed | Scope, ownership, rencana dan checklist sebelum coding | Review dokumen, 31 link lokal valid, whitespace PASS |
| 2 | Passed | ADR-016, desain contract dan Domain valuasi exact | RED/GREEN, focused/regresi592 test, Pint |
| 3 | Passed | Writer pengeluaran refund Finance dengan snapshot/replay | Focused26/full618 test, native MySQL worker/schema/guard |
| 4 | Passed | Quote dan penyelesaian Returns atomik, termasuk nol | Focused15/full633 test, empat worker native, audit/FK/down guard |
| 5 | Passed | UI refund/recovery/nol dan riwayat Finance snapshot | Full637 test, native history, DevTools/Playwright desktop/tablet, TS/lint/build |
| 6 | In Progress | Regresi dan aktivasi aman lulus; menunggu bukti remote | Full637 test, tiga native refund/history + native aktivasi, browser, batch22 |

## Handoff Saat Ini

- Perubahan terbaru: dialog refund positif/multi-metode dan nol, status uang
  terpisah dari penerimaan, hasil readonly, recovery body identik, serta source
  refund/detail khusus di history Finance. Read aman saat schema belum aktif.
- Verifikasi: full637 test/5509 assertion; focused93 test/874 assertion; native
  history MySQL nominal besar/presisi/filter/snapshot PASS. DevTools authenticated
  positif/nol/history PASS; Playwright desktop/tablet Light/Dark expanded/collapsed,
  timeout30s/5xx/lost response/stale/permission/keyboard PASS. TS/lint/build/Pint
  lulus. Bukti dan lokasi artifact pada [tasks.md](tasks.md).
- Kedua migration refund Ran batch22 melalui aktivasi lokal dua kali, catalog
  additive tanpa grant/reset/demo/payout. Semua kolom/data existing, user/role/
  pivot, source/Stock/Finance/audit identik. Theme dan package tidak diubah.
- QA Increment 6: full637/5509, native writer/settlement/history/aktivasi, DevTools
  dan Playwright terbaru PASS; bukti pada tasks. Tidak ada seeder payout otomatis.
- Delivery Git menunggu commit/push dan verifikasi SHA remote. Batas tersisa adalah
  non-scope (parsial, kredit, tukar, reversal, running balance dan transfer bank
  nyata), bukan fitur yang diam-diam dianggap selesai.
- Gap conformance: tidak ditemukan konflik struktural pada bagian yang dibaca;
  ini bukan audit menyeluruh repository. `Old-docs` dan file user tidak disentuh.
