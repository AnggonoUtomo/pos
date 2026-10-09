# Plan: Inventory Foundation

## Tujuan

Membangun fondasi master Inventory yang wajib ada sebelum transaksi POS real:
gudang, satuan, dan item. Fondasi ini harus mendukung multi gudang, multi
satuan, harga level pelanggan di tahap berikutnya, dan aturan stok tidak boleh
minus.

## Prinsip Urutan

- Gudang dibuat lebih dulu karena transaksi POS harus memilih gudang.
- Satuan dibuat sebelum item karena semua stok disimpan dalam base unit
  terkecil.
- Item dibuat setelah gudang dan satuan agar relasi base unit serta satuan jual
  dapat tervalidasi.
- Stock movement dan FIFO layer tidak dibuat pada master data pertama; keduanya
  masuk work item transaksi stok terpisah agar invariant tidak tercampur.
- Setiap increment harus meninggalkan aplikasi dalam kondisi buildable dan
  testable.

## Increment

| Increment | Status | Scope | Acceptance | Verification |
| --- | --- | --- | --- | --- |
| 1 | Passed | Dokumen perencanaan | Work item memiliki README, plan, tasks, scope dan non-scope jelas | `git diff --check`; review manual |
| 2 | Passed | `Inventory/Warehouses` | Scaffold, schema, seeder demo, permission awal, soft delete, dan audit model | 11 focused test; 49 full test; route, migration/status, seed ulang MySQL |
| 3 | Planned | `Inventory/Units` | Module scaffold mendukung base unit dan conversion ratio awal untuk multi satuan | dry-run generator; focused test; migration/status check |
| 4 | Planned | `Inventory/Items` | Module scaffold mendukung item master, base unit, satuan jual/beli awal, dan status aktif | dry-run generator; focused test; migration/status check |
| 5 | Planned | Seeder dan permission baseline | Demo gudang, satuan, dan item dapat dipakai POS mock; permission backend tersedia | seed class module; permission test; activity log smoke test |
| 6 | Planned | UI admin desktop/tablet | CRUD master inventory memakai Sonner, shortcut dropdown, permission UX, empty/loading/error state | `npm run lint`; `npm run build`; Chrome DevTools MCP desktop/tablet |

## Dependency Graph

```text
Platform/Identity
        |
        v
Inventory/Warehouses ----+
                         |
Inventory/Units ---------+--> Inventory/Items --> Sales/POS real
```

## QA Automated

- Backend:
  - focused feature tests per module;
  - permission middleware test;
  - seeder test atau smoke command;
  - route list check;
  - migration/status check bila schema berubah.
- Frontend:
  - `npm run lint`;
  - `npm run build`;
  - Chrome DevTools MCP untuk desktop/tablet.
- Repository:
  - `git diff --check`;
  - staged diff secret scan sebelum commit.

## Risiko Dan Mitigasi

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Item dibuat sebelum satuan stabil | Konversi stok tidak konsisten | Urutan increment mengunci Units sebelum Items |
| Stok dihitung di master item | FIFO sulit dijaga | Stock movement dan FIFO dipisah ke work item berikutnya |
| Seeder demo tidak relasional | POS mock tidak bisa beralih ke data nyata | Seeder dibuat lintas relasi gudang, unit, dan item |
| Permission hanya frontend | Akses backend bocor | Controller memakai middleware/policy backend |

## Open Questions

- Kode gudang awal diisi eksplisit dan unique termasuk soft-deleted row;
  validasi input CRUD akan ditetapkan bersama form.
- Apakah satuan konversi boleh fractional untuk semua item, atau dibatasi
  decimal scale tertentu per item?
- Apakah item awal perlu kategori/brand sejak foundation, atau ditunda agar
  scope tetap kecil?
- Gap conformance: MODULES.md masih menetapkan Inventory/Catalog, sedangkan
  work item merencanakan Inventory/Units dan Inventory/Items. Hentikan perubahan
  struktur satuan/barang sampai ownership ini diputuskan eksplisit. Fondasi
  Warehouses tetap memiliki boundary yang konsisten di kedua dokumen.
