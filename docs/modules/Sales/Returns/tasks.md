# Checklist Retur Penjualan

- [x] Fondasi draft terverifikasi dan aktif lokal.
- [x] Scope posting barang layak jual disetujui pada pekerjaan terpisah.
- [x] Urutan FIFO/valuasi dikunci dan posting selesai diverifikasi.
- [x] Scope refund lunas, nominal otomatis/residual, metode berbeda dan hasil nol
  disetujui melalui interview.
- [x] Increment 2 refund: ADR-016, desain contract dan Domain valuasi/reconciliation
  diverifikasi; belum memasang persistence, endpoint atau UI refund.
- [x] Increment 4: source summary, quote dan settlement backend atomik/nol,
  permission, audit, replay serta concurrency MySQL diverifikasi; belum UI/aktivasi.
- [x] Increment 5: UI refund positif/nol, readonly/recovery, status uang terpisah
  dan browser desktop/tablet verified.
- [x] Increment 6: regresi dan aktivasi lokal dua kali Ran batch22,
  fingerprint data/role/grant/source/Stock/audit identik, tanpa demo/payout.
- [x] Implementasi dan QA refund selesai; ikuti
  [checklist refund](../../../work-items/sale-return-refund/tasks.md).
- [ ] Karantina/tukar/reversal disepakati pada pekerjaan terpisah.

Checklist rinci: [Fondasi draft](work-items/draft-foundation/tasks.md).
Checklist posting: [Posting retur](../../../work-items/sale-return-posting/tasks.md).
