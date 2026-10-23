# Spesifikasi Fondasi Draft Transfer Antar-Gudang

Status: Disetujui untuk fondasi draft; implementasi belum selesai.

## Tujuan Dan Scope

Staff gudang membuat/mengubah/menghapus lunak/memulihkan draft dari gudang asal
ke tujuan berbeda. Satu dokumen memiliki satu pasangan gudang dan 1..100 barang
unik, masing-masing memilih satuan terdaftar dan quantity positif. Gudang boleh
diganti selama draft. Tidak ada operasi transfer stok pada scope ini.

## Arsitektur

Hexagon Inventory/Transfers, composition root ServiceProvider module.
Presentation controller/request/routes -> Application actions/query -> Domain
TransferQuantity murni. Adapter repository/audit ada di Infrastructure.
Dependencies hanya published WarehouseLookup, ItemStockLookup dan UnitLookup.
Tidak membaca model/repository/Domain privat module lain. Tidak membutuhkan
Stock/Finance contract baru sampai ada consumer posting nyata.

## Aturan

- Gudang asal/tujuan harus aktif, tersedia dan berbeda. Barang dan satuan dasar/
  pilihan harus aktif. Satuan harus benar-benar terdaftar pada barang; bukan
  dibatasi flag jual/beli karena transfer internal, bukan transaksi komersial.
- Quantity canonical string maksimal14 integer/6pecahan, >0. Rasio >=1 dari
  master server. base_quantity = quantity x rasio, exact6 dan kapasitas20,6;
  rounding, overflow, duplicate item dan field authority palsu ditolak.
- Snapshot kode/nama gudang serta SKU/nama barang/satuan/rasio disimpan.
  Tidak menerima cost, harga jual, total uang atau nomor/status/actor dari client.
- Draft tidak reservasi atau menilai saldo. Quantity boleh melebihi saldo karena
  tidak memutasi stok; posting nanti wajib validasi stok/FIFO dalam scope baru.
- Number server TR-{ULID}, version awal1; update/delete/restore wajib version.
  Stale422, no-op tidak menaikkan version/audit. Status non-draft immutable bagi
  semua role termasuk super-system, walau belum ada route membuat posted.
- Restore menjaga snapshot, tidak membaca master terkini untuk menulis ulang.
  Edit/simpan memvalidasi master aktif dan mengambil snapshot terbaru.
- Lock urut: dokumen -> gudang terurut -> satuan terurut -> barang terurut;
  transaction retry3, audit wajib commit bersama header/baris/version.

## Contract HTTP

Prefix inventory/transfers, name inventory.transfers.*, auth/verified/web/CSRF.
GET / dan /data memakai view, GET /options dan mutation memakai manage.
POST / menerima source_warehouse_id, destination_warehouse_id, notes opsional500,
lines[{item_id,unit_id,quantity}]. PUT /{id} menambah version; DELETE /{id} dan
POST /{id}/restore hanya version. Key tambahan ditolak, termasuk baris.
GET data: {data,meta}, pagination25, search literal150 atas nomor dan nama/kode
gudang, filter source_warehouse_id/destination_warehouse_id serta trashed0/1.
Output memakai decimal string exact6; UI format Indonesia tanpa nol trailing.
401/403/404/419/422 sesuai auth/permission/CSRF/validation. Audit gagal rollback.

## Data

stock_transfers: idULID, numberunique, source/destination_warehouse_id opaque,
source/destination_warehouse_code/name snapshot, notes, status draft, version,
demo_keynullableunique, timestamps/deleted_at. stock_transfer_lines: idULID,
transfer_idFKrestrict, item_id, unit_id, base_unit_id opaque, SKU/nama/satuan
snapshot, quantity/conversion_ratio/base_quantity decimal20,6. Unique transfer+
item. SQLite string21 agar exact. Rollback migration menolak data termasuk
soft-deleted. Referensi master opaque karena snapshot dan soft delete; tidak
menambahkan FK lintas ownership atau guard histori Stock untuk draft.

## Seeder Demo

TransfersDemoSeeder hanya local/testing, WH-MAIN -> WH-SECONDARY, kamera per dus
dan kabel NYM per rol/meter dari master existing. Jika prasyarat kurang, skip
tanpa membuat master atau dokumen parsial. Marker idempotent, termasuk edited/
trashed. Tidak seed ledger. Fixture QA boleh menyiapkan master di DB disposable.

## Authorization, Audit Dan UI

inventory.transfers.view untuk snapshot daftar/detail; manage untuk input/options
dan CRUD. Tidak implikasi permission inventory lain. Backend HasMiddleware can,
frontend usePermission/isSuperSystem hanya UX. Audit inventory.transfers untuk
created/updated/deleted/restored: ID/nomor/gudang/version/jumlah baris, tanpa notes
atau snapshot penuh. Audit gagal membatalkan mutation.

Page resources/js/pages/inventory/transfers, components/types lokal, shadcn,
Lucide semantic, Sonner, shortcut dropdown Alt+F/Ctrl+Shift+N/Ctrl+Enter/Esc,
sidebar dan palette. Viewer readonly, snapshot master nonaktif tetap terlihat.
Loading/empty/error/validation/stale dan processing disable. QA Light/Dark
desktop1440/tablet1024/768 expanded/collapsed, bukan mobile.

## Acceptance Dan Risiko

Checklist [work item](work-items/draft-foundation/tasks.md) adalah authority hasil.
Belum menentukan satu/dua tahap posting, FIFO transfer/dating layer, stok dalam
perjalanan, parsial, reversal, ongkos kirim atau approval. Tidak membuat ADR
posting sebelum keputusan tersebut benar-benar diperlukan.
