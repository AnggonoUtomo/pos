# WI-0008: Fondasi Persediaan FIFO

Status: Draf

## Tujuan

Membangun fondasi inventory: warehouse, stock balance, stock movement, FIFO layer, dan stock card.

## Rujukan

- `docs/adr/ADR-0003-inventory-fifo-no-negative-stock.md`
- `docs/ARCHITECTURE.md`

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
