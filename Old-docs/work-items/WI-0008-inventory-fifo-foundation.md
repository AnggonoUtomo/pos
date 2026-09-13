# WI-0008: Fondasi Persediaan FIFO

Status: Draf

## Tujuan

Membangun fondasi inventory: warehouse, stock balance, stock movement, FIFO layer, dan stock card.

## Rujukan

- `docs/adr/ADR-0003-inventory-fifo-no-negative-stock.md`
- `docs/ARCHITECTURE.md`
- `docs/QA-AUTOMATION.md`

## Modul Target

- Category: Inventory
- Module: Stock
- Namespace/path: `app/Modules/Inventory/Stock`
- Jenis pekerjaan: warehouse, balance, movement, FIFO layer, dan stock card

## Scope

Masuk scope:

- Warehouse.
- Stock balance per item warehouse.
- FIFO layers per item warehouse.
- Stock movements.
- Stock availability check.
- FIFO consume contract.
- Stock card query.

Di luar scope:

- Purchasing UI.
- UI Sales POS.
- Opname dan transfer detail.

## Checklist Sebelum Coding

- [ ] Locking strategy dirancang.
- [ ] Base-unit quantity digunakan.
- [ ] Movement type enum disetujui.

## Rencana Increment

| Increment | Fokus | Checklist Sebelum | Checklist Sesudah | QA automated | Status |
| --- | --- | --- | --- | --- | --- |
| INC-01 | Warehouse dan stock balance | - [ ] Multi gudang phase 1 dipahami | - [ ] Balance per item/warehouse tersedia | feature test stock balance | Draf |
| INC-02 | Stock movement dan stock card | - [ ] Movement type disetujui | - [ ] Movement tercatat dan query stock card berjalan | feature test movement and stock card | Draf |
| INC-03 | FIFO layer dan consume contract | - [ ] Locking strategy jelas | - [ ] FIFO consume oldest layer<br>- [ ] Stok minus ditolak | unit test FIFO consume, feature test insufficient stock | Draf |
| INC-04 | Public contract Inventory | - [ ] Consumer Sales/Purchasing diketahui | - [ ] Contract eksplisit tersedia tanpa expose model privat | contract test inventory availability/consume | Draf |

## Kriteria Penerimaan

- [ ] Stock tidak bisa minus.
- [ ] FIFO consume mengambil layer tertua.
- [ ] Stock movement tercatat untuk semua perubahan stok.
- [ ] Stock balance konsisten dengan movement/layer.
- [ ] Kontrak bisa dipakai Sales/Purchasing.

## Verifikasi

- [ ] Unit test FIFO consume.
- [ ] Feature test insufficient stock rejection.
- [ ] Feature test stock movement and balance.
