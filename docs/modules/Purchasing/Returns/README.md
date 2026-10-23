# Retur Pembelian

## Status

Selesai untuk fondasi draft dan implementasi posting stok/FIFO dari layer
penerimaan asal. Migration posting aktif di database lokal; QA Chrome DevTools
MCP Light/Dark desktop/tablet lulus. Rincian pada
[work item terpisah](../../../work-items/purchase-return-posting/README.md).
Penerimaan uang supplier tersedia terpisah dari posting barang, hanya untuk
pembelian lunas; [work item penerimaan](../../../work-items/purchase-return-refund/README.md).
Schema receipt/settlement lokal Ran batch25, catalog refund additive tanpa grant,
demo atau penerimaan uang otomatis; QA native/full/browser dan aktivasi dua kali PASS.
Dokumen mengikuti [arsitektur](../../../ARCHITECTURE.md) dan
[struktur canonical](../../../FOLDER-STRUCTURE.md).

## Tujuan

Staff pembelian mencatat draft retur dari pembelian yang sudah diterima,
memakai snapshot barang, satuan dan harga beli asal tanpa mengubah transaksi
posted. Spesifikasi: [specification.md](specification.md).

## Boundary

- Memiliki: header/baris draft dan posted, snapshot asal, kuantitas retur,
  version, lifecycle soft delete/restore draft, permission, audit, query, UI
  dan demo draft; quote dan settlement penerimaan uang supplier exact6.
- Tidak memiliki: penerimaan pembelian, reservasi stok, alokasi FIFO internal
  Stock, utang, pembayaran pembelian asal atau koreksi dokumen posted.
- Draft belum mengurangi stok; status posted menyatakan barang diserahkan,
  bukan uang telah diterima kembali.

## Public Boundary Dan Dependency

Belum ada public contract/DTOs/event milik Returns yang diperlukan module lain.
Jangan menambahkan port/event untuk consumer hipotetis.
Returns mengonsumsi published `PurchaseOrders\Application\Contracts\ReturnPurchaseLookup`
dan DTO `PurchaseOrders\Application\DTOs\ReturnPurchaseData` untuk lookup dan
save. Adapter query tetap dimiliki PurchaseOrders.
Tidak membaca model/repository/Domain privat PurchaseOrders atau Sales/Returns.
Stock/Finance hanya diperiksa fingerprint-nya, bukan dependency mutation draft.
Saat posting, Returns mengonsumsi `Inventory/Stock/Application/Contracts/
PurchaseReturnStockWriter` dan DTO publiknya dalam shared transaction.
Posting barang tidak memutasi Finance. Settlement uang mengonsumsi published
PurchaseRefundReceiptWriter/Request/Result dan PurchaseRefundReceiptMethods
milik Finance; source paid melalui ReturnPurchaseLookup::refundBasis. Scope
per pembelian, receipt, alokasi dan audit dua module satu transaction. Partial
round-down6/full residual, nol tanpa uang, canonical replay dan quote stale422.
Ledger kas masuk dimiliki Finance; tidak mengubah stok/FIFO atau PO asal.

## Permission, Data Dan Seeder

- `ReturnsAccessSeeder` mendaftarkan `purchasing.returns.view`,
  `purchasing.returns.manage`, `purchasing.returns.post`, dan
  `purchasing.returns.refund` tanpa memberi grant.
  Permission dibedakan per
  action; super-system tetap tunduk invariant.
- Backend route aktif: `GET /purchasing/returns` (Inertia),
  `/purchasing/returns/data`, `/purchasing/returns/purchases`,
  `/purchasing/returns/purchases/{id}`, `POST /purchasing/returns`,
  `PUT /purchasing/returns/{id}`, `DELETE /purchasing/returns/{id}`, dan
  `POST /purchasing/returns/{id}/restore`, dan
  `POST /purchasing/returns/{id}/post`, `GET /{id}/refund-quote`, dan
  `POST /{id}/refund` pada prefix yang sama. Hasil settlement dapat dibaca viewer
  tanpa izin refund; dialog konfirmasi/Sonner/shortcut/recovery milik Returns.
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
dan [QA](../../../QUALITY.md). Posting lulus feature, full suite, native MySQL
dan aktivasi additive dua kali; runner DevTools MCP existing/stdio isolated
memverifikasi posting, penolakan stok, readonly/viewer, shortcut/palette,
framing Light/Dark serta console/network tanpa error.
Gunakan [plan](plan.md) dan [checklist module](tasks.md) untuk urutan capability.
Implementasi harus memakai generator project setelah dry-run, test terfokus,
route/status, QA browser desktop/tablet dan aktivasi additive yang terverifikasi.
