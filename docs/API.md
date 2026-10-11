# API Publik

## Konvensi

- Base path API eksternal: belum ditetapkan untuk fase awal.
- Primary interface aplikasi: web session dengan Inertia React.
- Authentication: Laravel session auth dari starter kit.
- Authorization: backend policy atau permission middleware milik module.
- Rate limit: mengikuti middleware Laravel default sampai kebutuhan API publik
  ditetapkan.
- Idempotency mutation: wajib dipertimbangkan untuk posting, payment, retur,
  void/reversal, dan adjustment; detail contract dibuat pada work item module.
- Format error web: redirect atau Inertia validation error sesuai pola Laravel.
- Format error JSON: mengikuti response Laravel, lalu distandardkan ketika API
  publik pertama ditetapkan.

## Endpoint API Eksternal

Belum ada endpoint API eksternal yang menjadi contract publik. Perubahan yang
membuka endpoint eksternal wajib menambah tabel endpoint pada dokumen ini,
focused contract test, dan catatan authorization.

## Route Web Module

Route web module berada di:

```text
app/Modules/{Domain}/{Module}/Routes/web.php
```

Route web yang mengubah data harus:

- memanggil Application action/query, bukan menulis persistence langsung;
- memakai middleware permission atau policy;
- memakai Form Request atau boundary validation;
- mengembalikan Inertia response, redirect, atau resource sesuai kebutuhan UI;
- mencatat activity log pada mutation penting bila relevan.

### Endpoint Internal Inventory

Endpoint berikut memakai session web, auth, verified, dan CSRF Laravel; bukan
API eksternal/token. Middleware verified mengikuti kemampuan User existing,
yang belum mengimplementasikan MustVerifyEmail. GET `/data` menghasilkan JSON;
GET index menghasilkan Inertia response. POST JSON
menghasilkan 201 `{id}`, POST web redirect kembali dengan flash success.

| Method | Path | Nama route | Permission |
| --- | --- | --- | --- |
| GET | /inventory/warehouses/data | inventory.warehouses.data | inventory.view |
| POST | /inventory/warehouses | inventory.warehouses.store | inventory.manage |
| GET | /inventory/units/data | inventory.units.data | inventory.view |
| POST | /inventory/units | inventory.units.store | inventory.manage |
| GET | /inventory/items/data | inventory.items.data | inventory.view |
| POST | /inventory/items | inventory.items.store | inventory.manage |

Response daftar: `{data, meta}`; meta current_page/last_page/per_page/total,
25 record per page, page integer 1..2147483647, order code lalu id. Master
nonaktif terlihat untuk admin, soft-deleted tidak dikembalikan. Field gudang:
id/code/name/is_active; satuan menambah symbol. Field mutation: code/name,
symbol untuk satuan, optional is_active boolean default true. Input text di-trim;
field lain tidak dipersist. Code unik termasuk soft-deleted row.

Guest GET JSON 401, web redirect login; POST tanpa CSRF valid 419. Tanpa permission
403; invalid/konflik unique
422 validation errors JSON atau web session errors. Konflik persistence unique
dipetakan pada code; SQL/error infrastruktur tidak disertakan. Super-system
guard web bypass permission Gate sesuai ADR-010, bukan validasi/invariant.

Barang memakai pagination/metadata yang sama, order sku lalu id, dengan field
id/sku/name/base_unit_id/is_active dan units (id/item_id/unit_id/conversion_ratio/
barcode/is_sale_unit/is_purchase_unit). Relasi units diurutkan id dan disaring
soft delete, di-eager-load tanpa model Units privat.
Input barang: sku/name/base_unit_id ULID; optional is_active; optional units
list maksimal 100 row. Row berisi unit_id ULID distinct, conversion_ratio
decimal string, optional barcode nullable dan flag boolean jual/beli. ULID
input dinormalisasi lowercase; barcode '0' tidak diubah menjadi null. SKU dan
barcode unik termasuk data terhapus; barcode payload distinct case-insensitive.
Action memvalidasi satuan aktif dan ratio; base row otomatis bila belum ada.
Error request memakai field asal; error invariant memakai units; konflik unique
saat persistence memakai inventory. Form UI menampilkan error
umum tersebut selain error per field.

Update/delete/restore tersedia pada setiap module inventory:

| Method | Path suffix | Nama route suffix | Permission |
| --- | --- | --- | --- |
| PUT | /{id} | .update | inventory.manage |
| DELETE | /{id} | .destroy | inventory.manage |
| POST | /{id}/restore | .restore | inventory.manage |

Prefix mengikuti /inventory/{warehouses|units|items} dan inventory.{module}.
ID route wajib ULID. Mutation JSON sukses 200 `{id}` atau web redirect dengan
flash success. Update wajib is_active boolean dan field master lengkap; unique
mengabaikan row sendiri saja, tetap mereservasi kode/barcode trashed. Update/delete
row terhapus dan restore row non-terhapus menghasilkan 404 untuk payload valid.
Semua mutation/audit atomic. Base unit tidak dapat diganti. Satuan terpakai
(termasuk referensi trashed) menolak delete/nonaktif dengan error inventory.
Konversi yang dihapus mempertahankan barcode; tambah ulang unit yang sama dapat
memulihkan row miliknya. Konflik DB menghasilkan 422 tanpa detail SQL.

Halaman GET /inventory/warehouses, /inventory/units dan /inventory/items tersedia dengan route
inventory.{module}.index, permission inventory.view, response Inertia. Props
records berisi pagination dan deleted_at, filters berisi search/status/trashed.
Filter search maksimum 150 karakter, status all/active/inactive, trashed boolean
(query 0/1); tab terhapus hanya menampilkan row trashed. Endpoint /data lama
tetap tanpa filter/field tambahan untuk menjaga consumer existing.
Barang juga dapat dicari lewat barcode relasi aktif. Props unitOptions berasal
dari UnitLookup::listActive, maksimal 1000 satuan aktif, urut code/id. Form
menyimpan rasio sebagai string, bukan float. Base unit terkunci saat edit.
Barcode duplicate case-insensitive pada payload update ditolak sebelum mutation.

### Endpoint Internal Identity

Session web/auth/verified/CSRF mengikuti starter kit seperti Inventory. Ini bukan
API eksternal. Role super-system web bypass permission Gate sesuai ADR-010,
tetapi tetap tunduk pada validasi, transaction dan invariant akses.

| Method | Path | Nama route | Permission |
| --- | --- | --- | --- |
| GET | /platform/identity | platform.identity.index | platform.identity.view |
| POST | /platform/identity/roles | platform.identity.roles.store | platform.identity.manage |
| PUT | /platform/identity/roles/{id} | platform.identity.roles.update | platform.identity.manage |
| DELETE | /platform/identity/roles/{id} | platform.identity.roles.destroy | platform.identity.manage |
| PUT | /platform/identity/roles/{id}/permissions | platform.identity.roles.permissions | platform.identity.manage |
| PUT | /platform/identity/users/{id}/roles | platform.identity.users.roles | platform.identity.manage |

ID User/Role/Permission existing integer mengikuti starter kit/package, bukan
migrasi ULID pada pekerjaan UI ini. Target role hanya guard web; guard lain atau
target hilang menghasilkan 404. GET menghasilkan Inertia component
platform/identity/index dengan records `{data, meta}`, filters `{tab, search}`,
options `{roles, permissions}`. Tab roles/permissions/users; search maksimum 150
karakter; page 1..2147483647, 25 record/page, order name/id.
Role fields id/name/permissions/users_count; permission id/name/roles_count;
user id/name/email/roles/direct_permissions_count. Tidak mengekspos credential.
Catalog pilihan dibatasi 1000; overflow menghasilkan validation access, bukan
pemotongan silent.

POST/PUT role memakai name, di-trim/lowercase maksimum 64, pola
`[a-z][a-z0-9_-]*` dan unik web. Mutation sync memakai permissions atau roles:
list wajib/distinct maksimum 1000, key/nama terdaftar web, list kosong valid.
Payload tambahan seperti guard/id tidak dipersist. JSON sukses 201 create,
200 mutation lain dengan `{id}`; web redirect/session errors. Guest JSON 401 atau
redirect login; tanpa permission 403, invalid/unique 422 tanpa detail SQL.
Identifier route di luar rentang integer PHP positif ditolak 404, bukan dicast
overflow atau menghasilkan TypeError 500.

Role super-system/super-admin tidak boleh dibuat, rename, delete atau diedit
permission melalui UI. Role terpakai tidak bisa dihapus; role biasa tanpa
referensi memakai hard delete audited sebagai pengecualian package soft delete.
Manager non-super tidak boleh memberi akses melampaui permission efektifnya atau
memodifikasi role berpermission di atas aksesnya. Hanya super-system mengubah
assignment target super-system, dan minimal satu pemegang harus tersisa.
Assignment web mempertahankan direct grant/role guard lain/atribut akun.
Mutation dan audit atomic, no-op tanpa audit ganda, cache refresh sesudah commit.
Last-super guard ini tidak meliputi delete akun dari profil starter kit existing.
CRUD akun/password, direct grants dan key permission bebas tidak tersedia.

## Contract Lintas Module

Contract lintas module tidak didokumentasikan sebagai HTTP API kecuali benar-
benar diekspos melalui endpoint. Public boundary internal tetap mengikuti
[ARCHITECTURE.md](ARCHITECTURE.md):

- `Application/Contracts`
- `Application/DTOs`
- `Application/Events`

### Inventory/Units

Consumer nyata: `Inventory/Items/Application/Actions/CreateItem` dan seeder demo
Items. Model Infrastructure Units bersifat privat.

| Contract | Method | Hasil | Ketentuan |
| --- | --- | --- | --- |
| UnitLookup | findActiveById(string id, bool lock = false) | UnitData atau null | Hanya satuan aktif, belum dihapus; lock wajib di dalam transaction connection default bersama |
| UnitLookup | findActiveByCode(string code) | UnitData atau null | Lookup prasyarat seeder, tanpa membuat/mengubah Units |
| UnitLookup | listActive() | list UnitData | Consumer ListItems untuk pilihan form, aktif/non-trashed, urut code/id, maksimum 1000 |

UnitData memiliki id, code, name, dan symbol. Contract tersedia di
`app/Modules/Inventory/Units/Application/Contracts/UnitLookup.php`; DTO berada
di `Application/DTOs/UnitData.php`. Exception lock di luar transaction:
LogicException. Boundary diuji pada UnitLookupTest dan feature test Items.

### Inventory/Items

ItemUnitUsage::isReferenced(string unitId): bool dipakai ManageUnit untuk guard
delete/nonaktif. Adapter hanya membaca tabel Items miliknya; tidak ada mutation
lintas module. Referensi items.base_unit_id dan item_units.unit_id mencakup row
terhapus agar restore/histori tetap aman.
