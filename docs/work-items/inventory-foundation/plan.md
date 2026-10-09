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
| 3 | Passed | `Inventory/Units` | Master satuan dengan ULID, kode unik, simbol, status aktif, soft delete, audit, permission, demo | 9 focused test; 58 full test; migration/status, route, seed ulang MySQL |
| 4 | Passed | `Inventory/Items` | Item, base unit ratio 1, multi satuan per barang, barcode; core 4A dan demo 4B | 40 focused test; 98 full test; lookup public; migration/status; seed dan rollback MySQL |
| 5 | In Progress | Integrasi permission, audit, dan demo | Endpoint internal data/store master memakai can per action; demo relasional dan audit konsisten | feature HTTP/permission; seed integration; audit; regresi |
| 6 | Planned | UI admin desktop/tablet | CRUD master inventory memakai Sonner, shortcut dropdown, permission UX, empty/loading/error state | `npm run lint`; `npm run build`; Chrome DevTools MCP desktop/tablet |

## Rincian Increment 5

1. 5A: tambah query daftar dan create gudang/satuan melalui Application, port,
   adapter, DTOs, request, controller HasMiddleware, dan route module.
2. 5B: hubungkan CreateItem ke request/controller; read query barang dengan
   relasi satuan miliknya tanpa mengambil model privat Units.
3. 5C: verifikasi integrasi ketiga demo/access seeder, permission matrix,
   actor/subject audit, rollback, route, migration status, dan regresi.
4. Commit otomatis per bagian yang lulus; push menunggu work item selesai.

Endpoint internal memakai session web + auth + verified. GET
`/inventory/{warehouses|units|items}/data` memerlukan inventory.view, POST
`/inventory/{warehouses|units|items}` memerlukan inventory.manage. Nama route
`inventory.{module}.data/store`. Daftar terpaginasikan 25 record, urut kode/SKU,
tanpa data terhapus; master nonaktif tetap terlihat untuk admin. Response data
berbentuk `{data, meta}` dengan current_page, last_page, per_page, total.
Page integer minimal 1. Mutation JSON menghasilkan 201 `{id}`; web redirect
kembali dengan flash success, validasi JSON 422 atau web session errors.
Kode/nama/simbol/SKU/barcode di-trim; uniqueness termasuk data soft-deleted.
Request hanya menerima field master yang diizinkan. Units item maksimal 100
baris; ratio berupa decimal string, validasi invariant final tetap pada action.
Konflik unique saat persistence dipetakan menjadi validation error generik,
bukan detail SQL. Error infrastruktur lain tidak disamarkan.

Tidak membuat halaman Inertia palsu, mengubah menu/theme, memberi role/permission
otomatis, memasang dependency, membuat migration, atau membuat transaksi stok.
Edit/delete/restore operasional dan guard master yang direferensikan diselesaikan
bersama CRUD Increment 6. Demo seeder tetap di module; integrasi menguji urutan
Warehouses, Units, Items, bukan membuat seeder bisnis baru di root.

QA frontend/browser SKIPPED karena belum ada tampilan baru; fallback feature
HTTP melalui Laravel termasuk guest, tanpa izin, view saja, manage saja, dan
super-system. Bypass Gate disetujui user melalui ADR-010; validasi/invariant
tidak dibypass. Middleware verified mengikuti starter kit existing: model User
belum mengimplementasikan MustVerifyEmail, sehingga bukan jaminan enforcement
verifikasi email. Tidak mengubah flow auth di increment ini. Smoke HTTP lokal
guest dilakukan tanpa memutasi database user.

## Dependency Graph

```text
Inventory/Units --> Inventory/Items ---+
                                     +--> Sales/POS real (planned)
Inventory/Warehouses -----------------+
```

Dependency runtime Items ke Units sudah tersedia melalui UnitLookup. Warehouses
bukan dependency runtime Items; keduanya kelak dibaca oleh transaksi POS/Stock.

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
- Rasio/quantity memakai decimal(20,6), hasil harus exact tanpa rounding diam-diam.
  Rasio minimal 1 karena base unit terkecil; quantity boleh pecahan.
- Kategori/brand ditunda dari foundation awal; tidak menghalangi master barang.

## Ownership Satuan Dan Konversi

User menyetujui pemisahan Units/Items pada 9 Oktober 2026. ADR-009 menjadi
acuan: Units memiliki master satuan, Items memiliki base unit dan konversi
per barang. Tidak ada ratio atau flag base unit global pada master satuan.
Consumer transaksi memakai public boundary; dependency ke model Units privat
tidak diperbolehkan. Aturan ratio positif dan base unit ratio 1 diuji pada
Increment 4 saat ada relasi barang nyata.
