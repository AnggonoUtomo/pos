# Retur Pembelian

## Status

Selesai untuk fondasi draft. Increment 2-6 telah diverifikasi. Migration Returns
telah aktif di database lokal; tabel draft kosong. Posting stok/FIFO, pengiriman
retur dan refund supplier memerlukan scope terpisah.
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
Returns mengonsumsi published `PurchaseOrders\Application\Contracts\ReturnPurchaseLookup`
dan DTO `PurchaseOrders\Application\DTOs\ReturnPurchaseData` untuk lookup dan
save. Adapter query tetap dimiliki PurchaseOrders.
Tidak membaca model/repository/Domain privat PurchaseOrders atau Sales/Returns.
Stock/Finance hanya diperiksa fingerprint-nya, bukan dependency mutation draft.

## Permission, Data Dan Seeder

- `ReturnsAccessSeeder` mendaftarkan `purchasing.returns.view` dan
  `purchasing.returns.manage` tanpa memberi role/grant. Permission dibedakan per
  action; super-system tetap tunduk invariant.
- Backend route aktif: `GET /purchasing/returns` (Inertia),
  `/purchasing/returns/data`, `/purchasing/returns/purchases`,
  `/purchasing/returns/purchases/{id}`, `POST /purchasing/returns`,
  `PUT /purchasing/returns/{id}`, `DELETE /purchasing/returns/{id}`, dan
  `POST /purchasing/returns/{id}/restore`.
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
  Seeder hanya local/testing, idempotent termasuk marker yang diedit/terhapus,
  dan tidak mengubah source, ledger, permission maupun grant.

## Operasi Dan Verifikasi

Increment 5 memakai SQLite fixture disposable terpisah. Increment 6 juga lulus
QA native MySQL disposable serta aktivasi additive disposable dan lokal dua kali.
Aktivasi hanya menjalankan migration Returns dan mendaftarkan catalog permission;
tidak memberi grant atau menjalankan seeder demo. Draft lokal tetap kosong.
Command dan hasil rinci ada di [work item fondasi](work-items/draft-foundation/README.md)
dan [QA](../../../QUALITY.md).
Gunakan [plan](plan.md) dan [checklist module](tasks.md) untuk urutan capability.
Implementasi harus memakai generator project setelah dry-run, test terfokus,
route/status, QA browser desktop/tablet dan aktivasi additive yang terverifikasi.
