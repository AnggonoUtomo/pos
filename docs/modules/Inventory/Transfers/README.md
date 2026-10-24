# Transfer Antar-Gudang

## Status

Active untuk fondasi draft. QA dan aktivasi lokal batch26 lulus 24 Oktober 2026;
belum memiliki posting transfer. Source mengikuti
[arsitektur](../../../ARCHITECTURE.md) dan [struktur](../../../FOLDER-STRUCTURE.md).

## Tujuan Dan Boundary

Inventory/Transfers memiliki dokumen permintaan transfer, snapshot gudang/barang/
satuan, quantity/base quantity, lifecycle draft, permission, audit dan UI.
Tidak memiliki saldo, movement, FIFO, pengiriman/penerimaan, costing atau Finance.
Draft bukan reservasi dan tidak membuktikan barang telah berpindah.

## Public Boundary Dan Dependency

Mengonsumsi Warehouses/WarehouseLookup, Items/ItemStockLookup dan Units/UnitLookup.
Repository internal di Application/Contracts hanya dipakai use case sendiri.
Belum ada published contract/DTOs/event baru karena belum ada consumer eksternal.
Routes berada pada /inventory/transfers, bukan endpoint pemindahan stok bebas.

## Permission, Data Dan Seeder

inventory.transfers.view/manage terpisah, tanpa auto-grant. Header/baris ULID;
draft soft delete/restore, nomor TR-{ULID} dan version. Route/migration/seeder
di dalam app/Modules/Inventory/Transfers. Demo CCTV/kelistrikan hanya draft pada
local/testing, relasi existing, idempotent termasuk marker edited/trashed.
Tidak membuat master/role/stock/payment sebagai efek seeder transfer.

## Operasi Dan Verifikasi

Rincian [spesifikasi](specification.md), [rencana](plan.md), [checklist](tasks.md)
dan [work item](work-items/draft-foundation/README.md). Aktivasi lokal hanya
schema dan catalog setelah QA disposable, tanpa seed demo/grant/reset data.
Database toko tetap tanpa draft demo. Detail produk:
[PRD](work-items/draft-foundation/PRD.md). Route aktif:
[Transfer antar-gudang](http://pos.test/inventory/transfers).

```powershell
php artisan test --compact tests/Feature/Modules/Inventory/Transfers tests/Unit/Modules/Inventory/Transfers
php artisan route:list --path=inventory/transfers
```
