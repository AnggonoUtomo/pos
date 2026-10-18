# Retur Penjualan

Module `Sales/Returns` memiliki draft retur dari invoice penjualan posted.
Lihat [Spesifikasi](specification.md), [Rencana](plan.md), dan
[Checklist](tasks.md). Pekerjaan aktif: [Fondasi draft](work-items/draft-foundation/README.md).

Dependency publik: `Sales/POS/Application/Contracts/ReturnInvoiceLookup` untuk
snapshot invoice asal, bukan model/repository privat POS.

Status awal: disetujui untuk implementasi; belum aktif sampai verifikasi selesai.
