# Penerimaan Uang Retur Supplier

Status: Done. Implementasi, QA, aktivasi lokal dan delivery terverifikasi
23 Oktober 2026. Commit implementasi/QA 53aeb7c tersedia pada origin/main;
checklist penutupan dicatat terpisah setelah verifikasi remote.

## Owner Dan Kondisi Awal

Purchasing/Returns memiliki retur posted dan pengeluaran FIFO dari layer asal.
PurchaseOrders memiliki pembelian diterima serta pembayaran lunas exact6.
Finance/Payments memiliki kas masuk penjualan exact2 dan pengeluaran pembelian
exact6; penerimaan uang retur supplier belum tersedia.

## Scope

- Retur posted dari pembelian yang sudah dibayar lunas, diselesaikan sekali.
- Nominal otomatis proporsional nilai beli snapshot asal, exact6. Bagian
  dibulatkan turun; penyelesaian seluruh kuantitas mengambil sisa pembulatan.
- Split maksimal 10 metode aktif unik, total tepat tanpa kembalian. Metode
  boleh berbeda dari pembayaran asal; akun masuk mengikuti pilihan saat ini.
- Nilai nol diselesaikan dengan audit tanpa metode atau ledger uang nol,
  mengikuti pola settlement refund existing.
- Quote authoritative/fingerprint, permission terpisah, audit, replay dan UI.
- Riwayat Finance membedakan kas masuk supplier exact6 dari sumber existing.

## Tidak Dikerjakan

Pembelian belum lunas, kredit supplier, pembayaran parsial, utang/jatuh tempo,
uang muka, reversal, negosiasi nominal bebas atau mutation Stock/FIFO.
Tidak memasang dependency, mengubah stack atau mengisi demo di database bisnis.

## Acceptance

- [x] Source paid/receipt/snapshot authoritative dan batas hak per baris exact6.
- [x] Scope pembelian mengunci settlement bersaing; tidak over-refund.
- [x] Finance masuk dan dua audit/status settlement atomic; replay aman.
- [x] Permission, quote stale, metode invalid, nol, readonly dan histori teruji.
- [x] QA unit/feature/native MySQL/browser desktop-tablet, build/lint/typecheck.
- [x] Migration/catalog lokal additive idempotent tanpa grant/demo otomatis.
- [x] Dokumen, commit per increment dan remote delivery terverifikasi.

## Dependency Dan Keputusan

[ADR-018](../../decisions/ADR-018-PURCHASE-RETURN-REFUND-RECEIPTS.md).
Kontrak publik dipublikasikan bersama consumer nyata; tidak mengimpor model,
repository atau Domain privat module lain. Source purchase/receipt/payment
asal dan seluruh Stock/FIFO harus identik setelah settlement.

Rencana: [plan](plan.md), checklist: [tasks](tasks.md).
