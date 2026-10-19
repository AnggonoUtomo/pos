# Retur Pembelian

## Status

In Progress. Scope fondasi draft disetujui user. Scaffold module, migration
draft, dan aturan quantity murni sudah tersedia serta diverifikasi pada
Increment 2. Belum ada contract sumber, route bisnis, permission, CRUD, audit,
demo draft, atau UI.
Dokumen mengikuti [arsitektur](../../../ARCHITECTURE.md) dan
[struktur canonical](../../../FOLDER-STRUCTURE.md).

## Tujuan

Staff pembelian mencatat draft retur dari pembelian yang sudah diterima,
memakai snapshot barang, satuan dan harga beli asal tanpa mengubah transaksi
posted. Spesifikasi: [specification.md](specification.md).

## Boundary

- Memiliki: header/baris draft, snapshot asal, kuantitas retur, version,
  lifecycle soft delete/restore, permission, audit, query, UI dan demo draft.
- Tidak memiliki: penerimaan pembelian, pengurangan/reservasi stok, alokasi FIFO,
  refund supplier, utang, pembayaran atau koreksi dokumen posted.
- Draft tidak menyatakan barang telah dikirim atau uang telah diterima kembali.

## Public Boundary Dan Dependency

Belum ada public contract/DTOs/event milik Returns yang diperlukan module lain.
Jangan menambahkan port/event untuk consumer hipotetis.
Dependency yang direncanakan: lookup snapshot published milik
Purchasing/PurchaseOrders, dibuat bersama consumer nyata pada increment backend.
Nama kerja `Application/Contracts/ReturnPurchaseLookup` dan DTOs
`Application/DTOs/ReturnPurchaseData`; keduanya belum diimplementasikan.
Tidak membaca model/repository/Domain privat PurchaseOrders atau Sales/Returns.
Stock/Finance hanya diperiksa fingerprint-nya, bukan dependency mutation draft.

## Permission, Data Dan Seeder

- Rencana permission: purchasing.returns.view dan purchasing.returns.manage,
  catalog module tanpa auto-grant. Super-system tetap tunduk invariant.
- Rencana route: /purchasing/returns, belum aktif.
- Source: app/Modules/Purchasing/Returns; Routes/, Database/Migrations/ dan
  Database/Seeders/ ada di dalam module. Provider terdaftar dan memuat route
  serta migration module.
- Schema draft: header soft delete, baris mengikuti lifecycle header, ULID,
  version, snapshot JSON dan unique baris sumber per draft. Foreign key sumber
  memakai restrict. Rollback migration ditolak selama header/baris masih ada.
- Domain ReturnQuantity menerima decimal string positif maksimal14/6, memastikan
  retur tidak melebihi kuantitas diterima dan menghasilkan base quantity exact6
  tanpa float.
- Demo CCTV/kelistrikan hanya draft dari sumber posted existing; bila tidak ada
  sumber relevan, skip tanpa membuat penerimaan/pembayaran atau seed module lain.

## Operasi Dan Verifikasi

Migration lokal masih Pending dan tidak dijalankan pada Increment 2. Belum ada
aktivasi, command QA native/browser khusus, atau migration yang dijalankan pada
database user untuk module ini. Rencana command dan hasil aktual dibedakan di
[work item fondasi](work-items/draft-foundation/README.md).
Gunakan [plan](plan.md) dan [checklist module](tasks.md) untuk urutan capability.
Implementasi harus memakai generator project setelah dry-run, test terfokus,
route/status, QA browser desktop/tablet dan aktivasi additive yang terverifikasi.
