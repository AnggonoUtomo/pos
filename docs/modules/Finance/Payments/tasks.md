# Tasks: Finance/Payments

- [x] Scope master kas/bank dan metode disetujui; non-scope saldo/payment jelas.
- [x] Dokumen dan increment dibuat sebelum coding.
- [x] [Fondasi master](work-items/masters-foundation/tasks.md) selesai/diverifikasi.
- [x] Status module/contract/QA diperbarui dan delivery remote dikonfirmasi.

- [x] Penerimaan penjualan internal, snapshot/net cash/replay dan guard histori
  diverifikasi pada [Increment 3 checkout](../../../work-items/pos-paid-checkout/tasks.md).
- [x] Integrasi posting backend POS melalui published writers (Increment 4).
- [x] UI checkout POS (Increment 5 work item yang sama, ownership Sales/POS).
- [x] Penutupan/delivery keseluruhan checkout (Increment 6).

- [x] Pengeluaran pembelian exact6, snapshot/replay/audit dan guard histori;
  consumer PayPurchaseOrder melalui public writer/DTOs, tanpa mutasi stok.
  Bukti [pembayaran pembelian](../../../work-items/purchase-paid-settlement/tasks.md).

Running balance, piutang/utang dan mutasi umum belum dikerjakan.

- [x] Riwayat pembayaran dua arah readonly, filter/permission/detail snapshot,
  GET fingerprint dan QA backend/browser/native.
  Bukti [payment-history](work-items/payment-history/tasks.md).

- [x] Scope pengeluaran refund retur lunas disetujui melalui interview.
- [x] Desain ledger refund khusus decimal30,2 dan boundary consumer Returns
  tercatat pada ADR-016; bukti implementasi mengikuti checklist Increment 3.
- [x] Increment 3: writer refund/DTOs/choices, schema disposable, replay/audit
  dan guard histori master diverifikasi; migration lokal belum diaktifkan.
- [x] Increment 5: riwayat refund snapshot/source_type/detail route terpisah,
  precision2/6, filter, readiness, privacy dan browser/native MySQL PASS.
- [ ] Writer refund, histori, guard master dan QA lintas module diverifikasi;
  ikuti [checklist refund](../../../work-items/sale-return-refund/tasks.md).
  Tidak membuat payout dari checklist atau seeder ini.
