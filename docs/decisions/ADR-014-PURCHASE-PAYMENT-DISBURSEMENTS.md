# ADR-014: Pengeluaran Pembayaran Pembelian Terpisah

Status Accepted, 17 Oktober 2026, untuk scope pembayaran lunas yang disetujui.

## Konteks

Receipt stok sudah posted tanpa Finance. Pembelian memiliki nilai exact scale6,
sedangkan receipt kas masuk penjualan memakai scale2. Mengubah receipt sale
menjadi pengeluaran atau membulatkan nilai beli akan mengubah contract existing.

## Keputusan

Finance/Payments memiliki payment_disbursements/payment_disbursement_lines
immutable dengan nilai decimal30,6. Pengeluaran positif mempunyai arah keluar
secara eksplisit dari jenis ledger, bukan kas masuk negatif atau activity log.
PurchasePaymentWriter/DTOs dipakai PayPurchaseOrder sesudah receipt stok posted.
Snapshot total order authoritative, split1..10 metode unik positif harus tepat,
tanpa kembalian, rounding, transfer bank nyata atau running balance.

Pointer/snapshot/time/version Purchasing dan ledger/dua audit commit bersama.
Hash canonical tanpa actor mengizinkan operator berizin lain melakukan replay
identik tanpa mengganti actor pertama. paid_from_version melindungi revisi;
payload berbeda ditolak. Snapshot publik hanya metode/nilai, bukan metadata bank.
Master tetap dikunci/revalidasi saat pembayaran baru, histori melindungi metode
dan akun. Replay tidak bergantung master aktif atau nama terkini.

## Konsekuensi

Existing posted tetap belum dicatat, bukan otomatis lunas/utang. Aktivasi tidak
backfill pembayaran atau grant. Penerimaan stok tetap terpisah, tidak ada mutation
Stock saat bayar. Penambahan saldo kas, utang/parsial/uang muka/reversal memerlukan
pekerjaan baru. Down menolak histori paid. Contract sale scale2 tetap tidak berubah.
Bukti [work item](../work-items/purchase-paid-settlement/README.md).
