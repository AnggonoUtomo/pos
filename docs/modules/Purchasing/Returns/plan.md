# Rencana Retur Pembelian

1. [Fondasi draft](work-items/draft-foundation/plan.md): scope disetujui,
   Increment 1 dokumentasi selesai diverifikasi; runtime belum ada. Snapshot sumber,
   quantity, lifecycle/audit/permission/demo dan UI tanpa efek ledger.
2. Posting retur supplier/pengeluaran stok: belum disepakati. Harus menetapkan
   sumber layer/biaya, no-negative, batas kumulatif dan replay secara eksplisit.
3. Pengembalian uang supplier/utang/koreksi: belum disepakati; bukan implikasi
   harga beli snapshot pada draft atau refund penjualan yang sudah tersedia.

Hanya langkah1 yang memiliki approval scope. Jangan membuat writer Stock/Finance,
port/event atau schema posted dari daftar roadmap ini tanpa persetujuan user.
Checklist: [tasks.md](tasks.md).
