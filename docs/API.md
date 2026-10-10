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
yang belum mengimplementasikan MustVerifyEmail. GET selalu JSON; POST JSON
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
saat persistence memakai inventory. Form UI nantinya wajib menampilkan error
umum tersebut selain error per field. Edit/hapus/restore dan UI menunggu Increment 6.

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

UnitData memiliki id, code, name, dan symbol. Contract tersedia di
`app/Modules/Inventory/Units/Application/Contracts/UnitLookup.php`; DTO berada
di `Application/DTOs/UnitData.php`. Exception lock di luar transaction:
LogicException. Boundary diuji pada UnitLookupTest dan feature test Items.
