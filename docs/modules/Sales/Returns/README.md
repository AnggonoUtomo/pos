# Retur Penjualan

Module `Sales/Returns` memiliki draft retur dari invoice penjualan posted.
Lihat [Spesifikasi](specification.md), [Rencana](plan.md), dan
[Checklist](tasks.md). Pekerjaan aktif: [Fondasi draft](work-items/draft-foundation/README.md).

Dependency publik: `Sales/POS/Application/Contracts/ReturnInvoiceLookup` untuk
snapshot invoice asal, bukan model/repository privat POS.

Status: Active untuk fondasi draft; posting/refund belum tersedia.
URL `/sales/returns`, permission `sales.returns.view` dan `sales.returns.manage`.
CRUD draft/version/audit, snapshot asli dan demo idempotent sudah diuji.
Migration lokal Ran batch20; aktivasi additive tanpa demo/grant/reset akun.
