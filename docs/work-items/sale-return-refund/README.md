# Work Item: Refund Retur Penjualan Lunas

## Status

Ready. Scope disetujui melalui interview; implementasi belum dimulai.
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

- [ ] Hanya retur posted yang belum diselesaikan dapat direfund.
- [ ] Nilai dihitung exact dari snapshot asal; total per baris terjaga sampai
  sisa kuantitas dan nominal habis, termasuk alokasi nol dan nominal besar.
- [ ] Dua proses pada invoice sama tidak membayar melebihi hak refund.
- [ ] Multi-payment positif tepat jumlah, metode berbeda dari asal dibolehkan,
  dan akun/metode tidak valid ditolak tanpa efek samping.
- [ ] Penyelesaian nol diaudit tanpa ledger uang dan tanpa metode pembayaran.
- [ ] Replay identik stabil; payload berbeda, quote stale dan mutation setelah
  settlement ditolak sesuai contract yang dikunci sebelum implementasi.
- [ ] Audit gagal membatalkan seluruh settlement dan ledger, termasuk setelah
  writer Finance berhasil.
- [ ] Histori refund tetap terbaca dari snapshot setelah rename master;
  histori penerimaan dan pembelian existing tidak berubah makna/presisinya.
- [ ] UI menampilkan nominal/status sebenarnya, Sonner, shortcut, permission,
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

## Dependency Dan Keputusan

- [Arsitektur](../../ARCHITECTURE.md), [struktur](../../FOLDER-STRUCTURE.md),
  [workflow](../../WORKFLOW.md), dan [QA](../../QUALITY.md).
- [Spesifikasi Returns](../../modules/Sales/Returns/specification.md) dan
  [Finance/Payments](../../modules/Finance/Payments/specification.md).
- [Posting fisik](../sale-return-posting/README.md) sudah selesai; biaya FIFO
  ADR-015 tetap terpisah dari nominal refund pelanggan.
- Keputusan interview dalam scope di atas disetujui user. PRD tambahan tidak
  diperlukan saat ini karena kebutuhan dan non-scope sudah eksplisit.
- ADR desain persistence/locking/ledger refund disusun pada Increment 2;
  dokumentasi ini tidak mengklaim schema atau public contract baru sudah ada.

## Increment

| No | Status | Hasil | Verifikasi |
| --- | --- | --- | --- |
| 1 | Passed | Scope, ownership, rencana dan checklist sebelum coding | Review dokumen, 31 link lokal valid, whitespace PASS |
| 2 | Planned | ADR, contract yang dirancang dan aturan valuasi exact | Unit test alokasi dan review boundary |
| 3 | Planned | Writer pengeluaran refund Finance dengan snapshot/replay | Feature Finance, migration disposable, guard master |
| 4 | Planned | Quote dan penyelesaian Returns atomik, termasuk nol | Feature, audit veto, concurrency MySQL |
| 5 | Planned | UI refund dan riwayat Finance yang benar | Typecheck/lint/build, DevTools desktop/tablet |
| 6 | Planned | Regresi, aktivasi aman dan delivery | Full test, native/browser, migration dan Git remote |

## Handoff Saat Ini

- Perubahan: rencana dokumentasi; tidak ada coding, migration atau perubahan DB.
- Verifikasi dokumen: review, 31 link lokal pada 8 dokumen, whitespace dan
  staged scope PASS; rincian pada [tasks.md](tasks.md).
- Runtime QA: belum dijalankan untuk fitur refund karena belum diimplementasi.
- Risiko terbuka: desain ledger/locking, stale quote, konservasi pembulatan dan
  compatibility histori harus ditutup melalui Increment 2..6, bukan diasumsikan.
- Gap conformance: tidak ditemukan konflik struktural pada bagian yang dibaca;
  ini bukan audit menyeluruh repository. `Old-docs` dan file user tidak disentuh.
