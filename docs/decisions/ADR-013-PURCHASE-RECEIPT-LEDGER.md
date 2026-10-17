# ADR-013: Receipt Stok Pembelian Terpisah Dari Stok Awal

Status: Accepted untuk implementasi full receipt yang disetujui user,
17 Oktober 2026. Verifikasi implementasi pada work item purchase-full-receipt.

## Konteks

Stok awal sengaja hanya sekali per barang/gudang. Pembelian adalah penerimaan
berulang dan dapat menjadi sumber stok pertama. Menggunakan opening palsu akan
merusak invariant tersebut; inner join saldo ke opening akan menyembunyikan
saldo dari pembelian. Activity log bukan ledger stok.

## Keputusan

Stock memiliki stock_receipts/stock_receipt_lines immutable, unique source/baris,
snapshot serta hash replay. PurchaseOrders memanggil PurchaseStockWriter melalui
DTO dalam satu transaction caller; Stock tidak mengimpor model Purchasing.
Satu purchase menerima seluruh draft satu kali. Posted pointer/revision berasal
server dan FK restrict; perubahan posted hanya lewat koreksi terpisah kelak.

Anchor opening_line_id pada saldo nullable dengan metadata snapshot fallback;
legacy opening tetap kompatibel. Movement purchase menaut ke receipt line,
layer FIFO tetap generik berbasis movement. Query legacy memakai left join,
tidak memerlukan opening buatan/backfill data bisnis. Guard rollback menolak
histori posted. Tidak mengubah formula FIFO residual atau writer sale.

## Konsekuensi

Migration dan query saldo/kartu stok harus diuji untuk purchase-only dan legacy.
Stock writer caller transaction wajib dan kedua audit harus atomic. Finance/
payable bukan bagian receipt tahap ini. Penerimaan parsial memerlukan pekerjaan
baru, bukan mengubah posted receipt. Tidak perlu event tanpa consumer nyata.
