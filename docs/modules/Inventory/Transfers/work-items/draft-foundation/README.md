# Fondasi Draft Transfer Antar-Gudang

Status: In Progress (delivery). Implementasi, QA dan aktivasi lokal lulus
24 Oktober 2026; scope disetujui 23 Oktober 2026. Push final masih pending.

## Owner Dan Kondisi Awal

Inventory/Transfers (app/Modules/Inventory/Transfers) belum ada. Master barang/
satuan/gudang dan Stock/FIFO sudah berjalan; tidak ada workflow pindah gudang.
Spesifikasi: [module](../../specification.md).

## Scope Dan Non-Scope

CRUD draft pasangan gudang, multi-satuan/exact quantity, snapshot/version,
soft delete/restore, permission/audit, demo CCTV/kelistrikan dan UI. Tidak
memindahkan stok, membuat ledger/FIFO, reservasi, shipment/receipt atau Finance.
Tidak menyentuh theme/dependency/Old-docs maupun file staged user.

## Acceptance

- [x] Gudang aktif berbeda, quantity/base exact6, item/satuan valid, no forgery.
- [x] CRUD/lifecycle/version/no-op/audit atomic dan snapshot readonly.
- [x] Permission403/CSRF419, UI/shortcut/viewer dan demo idempotent.
- [x] Stock/FIFO/Finance immutable, native concurrency dan migration guard.
- [x] Full QA/browser desktop-tablet/activation tanpa grant/demo lokal.
- [ ] Dokumen/checklist/commit per increment dan delivery terverifikasi.

## Dampak Dan Dependency

Schema2tabel/migrationmodule,6route+options,2permission catalog tanpa grant,
page/nav/palette, audit dan demo draft. Public dependencies WarehouseLookup,
ItemStockLookup, UnitLookup. Tidak ada event/port eksternal baru tanpa consumer.
Aktivasi additive schema/catalog lokal setelah QA disposable, dua kali.
Rencana: [plan](plan.md), checklist: [tasks](tasks.md).
Kebutuhan produk: [PRD](PRD.md).
