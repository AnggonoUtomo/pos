# Work Item: Fondasi Draft Pembelian

Status In Progress, 17 Oktober 2026. Baseline aa6411f.
Owner Purchasing/PurchaseOrders; perubahan Suppliers hanya public lookup
tanpa kontak untuk consumer nyata ini. [PRD](PRD.md),
[spesifikasi](../../specification.md), [plan](plan.md), [tasks](tasks.md).

Hasil: draft pembelian valid multi-satuan, subtotal backend exact, CRUD berizin
dan audited tanpa perubahan stok/finance. Harga wajib positif sesuai user.
Non-scope: posting, receiving parsial, diskon/pajak, utang/payment/retur/cetak,
theme/dependency/auth baru. Tidak membaca/mengubah Old-docs atau artefak user.

Risiko: draft bukan penerimaan; master bisa menjadi invalid sesudah simpan,
snapshot tetap terbaca tetapi simpan berikutnya harus memilih master valid.
Lookup input bounded1000 mengikuti baseline. Guard histori dan integrasi Stock
belum dibuat. Demo operasional tidak otomatis di-seed pada database user.
