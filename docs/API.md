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

## Endpoint Internal Pelanggan

Module Sales/Parties memakai session web/auth/verified/CSRF. Bukan API token.
Prefix `/sales/customers` (`sales.customers.*`) dan `/sales/customer-levels`
(`sales.customer-levels.*`): GET index/data memerlukan `sales.parties.view`;
POST store, PUT `{id}`, DELETE `{id}`, POST `{id}/restore` memerlukan
`sales.parties.manage`. ID route ULID, super-system tetap tunduk invariant.

Index Inertia `sales/parties/index` dan `sales/parties/customer-levels`;
props records/filters, customer menambah levelOptions aktif (maksimal 1000).
JSON data `{data,meta}` dengan current_page/last_page/per_page/total, 25 baris,
urut code/id. Search code/nama maksimal 150, status all/active/inactive,
trashed 0/1, page positif; customer_level_id ULID opsional pada daftar customer.

Level: id/code/name/is_active/deleted_at. Customer menambah customer_level_id,
customer_level_name, phone/email/address nullable. Mutation level: code/name,
is_active opsional default true. Customer menambah level wajib, phone maksimal
32, email valid maksimal 150, address maksimal 500. Code trim/uppercase dan
unique termasuk trashed; field tak dikenal tidak disimpan.

Create JSON 201/id, mutation 200/id; web redirect flash success. Invalid input,
level invalid dan konflik kode 422 teredaksi; unknown resource 404. Level
terpakai oleh customer atau harga barang, termasuk trashed, tidak boleh
delete/nonaktif (error parties).
Customer create/edit/restore membutuhkan level aktif (error customer_level_id).
Mutation/audit atomic; detail kontrak ada pada
[specification Parties](modules/Sales/Parties/specification.md).

## Endpoint Internal Supplier

Module Purchasing/Suppliers, session web/auth/verified/CSRF; bukan API token.
Prefix `/purchasing/suppliers`, nama `purchasing.suppliers.*`:

| Method | Path relatif | Nama | Permission |
| --- | --- | --- | --- |
| GET | / | index | purchasing.suppliers.view |
| GET | /data | data | purchasing.suppliers.view |
| POST | / | store | purchasing.suppliers.manage |
| PUT | /{id} | update | purchasing.suppliers.manage |
| DELETE | /{id} | destroy | purchasing.suppliers.manage |
| POST | /{id}/restore | restore | purchasing.suppliers.manage |

Index Inertia `purchasing/suppliers/index`, props records/filters. JSON data
`{data,meta}` berisi id/code/name/phone/email/address/is_active/deleted_at,
meta current_page/last_page/per_page/total. Pagination 25 urut code/id.
Search literal code/name maksimal 150, status all/active/inactive, trashed0/1,
page integer positif. Read tidak mengaudit atau mengubah data.

Payload code wajib max32 trim/uppercase unik termasuk trashed; name wajib
max150; phone nullable max32, email nullable valid max150, address nullable
max500; kontak trim/kosong null. is_active opsional default true. Field lain
tidak dipersist. Create JSON201/id; mutation200/id atau redirect back untuk
Inertia/Sonner. Invalid/konflik kode422, ID invalid/tidak ditemukan404.
Soft delete/restore mempertahankan status. Manage tidak otomatis memberi view;
super-system tetap tunduk validasi. Audit wajib atomic dan tidak memuat nilai
kontak. Public SupplierLookup dikonsumsi draft/posting PurchaseOrders melalui DTO
id/code/name aktif tanpa kontak; lock true wajib shared transaction. Belum ada
integration event atau usage guard supplier spekulatif. Supplier boleh nonaktif/
soft delete setelah posted; FK restrict/snapshot transaksi menjaga histori,
posting baru wajib aktif dan replay versi asal tidak membaca ulang master.
Detail [spesifikasi Suppliers](modules/Purchasing/Suppliers/specification.md).

## Endpoint Internal Pembelian

Purchasing/PurchaseOrders memakai session web/auth/verified/CSRF.
Prefix `/purchasing/purchase-orders`, nama `purchasing.purchase-orders.*`.
GET index/data memerlukan purchasing.purchase-orders.view. POST store, PUT {id},
DELETE {id}, POST {id}/restore memerlukan purchasing.purchase-orders.manage.
POST {id}/post memerlukan purchasing.purchase-orders.post. Manage/post bukan view
dan manage bukan post; super-system tetap tunduk invariant.

Index Inertia purchasing/purchase-orders/index, props records/filters/options.
Data JSON {data,meta}, pagination25 descending id, baris eager maksimum100/order.
Search literal max150 pada number/supplier_name/warehouse_name; supplier_id dan
warehouse_id opsional ULID, status all/draft/posted default all, trashed0/1,
page integer positif. Read tidak mengaudit.
Options aktif melalui public lookup (maksimum1000/master), supplier hanya
id/code/name tanpa kontak, barang hanya satuan pembelian aktif.

Input supplier_id/warehouse_id ULID wajib, notes nullable max500; lines1..100,
item_id distinct dan unit_id ULID, quantity/unit_cost canonical decimal string
positif max14 digit integer/6 pecahan. Satuan terdaftar dengan is_purchase_unit.
Konversi dan subtotal exact scale6 dihitung backend; rounding/overflow ditolak.
Field ID/status/number/total/snapshot/demo_key/actor dari client tidak dipersist.
Edit/delete/restore wajib version existing; stale422 error purchase_order,
no-op edit tidak menaikkan revision/audit. Restore hanya mempertahankan snapshot;
master invalid harus diganti saat simpan berikutnya. Tidak hard delete.

JSON store201/id, mutation200/id; Inertia redirect back dengan Sonner. Invalid/
stale422, invalid/missing id404, forbidden403 atau guest401/login redirect.
Draft memakai ULID/PO-{ULID}, snapshot master dan total_value/lines scale6;
audit atomic tanpa catatan/kontak. Draft tidak mengubah stok/FIFO/finance.

Posting hanya payload version integer positif (CSRF diizinkan); field lain422.
Seluruh draft diterima sekali, bukan input quantity/cost baru. Revalidasi master
aktif dan konsistensi base unit/ratio/base quantity/total; perubahan konversi
memerlukan edit/simpan draft. Snapshot nama/SKU diperbarui saat posting.
JSON200 {id,status,version,stock_receipt_id,posted_at}, web redirect/Sonner.
posted_from_version menyimpan request pertama; replay versi asal mengembalikan
hasil lama tanpa stok/audit/revision baru, bahkan sesudah master nonaktif.
Versi lain/stale/trashed/posted mutation422; posted tidak bisa edit/delete/restore.
Data read menambah stock_receipt_id/posted_from_version/posted_at nullable.
Status/baris/audit Purchasing dan receipt/saldo/movement/FIFO/audit Stock satu
transaction retry3, gagal semuanya rollback. Finance tetap tidak dicatat;
partial receiving/utang/retur/reversal bukan endpoint tahap ini; pembayaran
terpisah sesudah receipt melalui endpoint berikut.

POST /purchasing/purchase-orders/{id}/pay (purchasing.purchase-orders.pay),
permission purchasing.purchase-orders.pay terpisah dari view/manage/post.
Payload version dan payments1..10 {method_id,amount}, field lain422 (CSRF boleh).
ULID metode unik, amount canonical string positif max24 digit integer/6 pecahan;
total snapshot backend harus tepat, tanpa kembalian/rounding/pembayaran parsial.
Posted nontrashed dengan receipt stok saja; tidak lookup ulang supplier/item
posted, tetapi akun/metode aktif dan compatible wajib saat pembayaran baru.
JSON200 id/status/version/payment_disbursement_id/payment_snapshot/paid_at;
web redirect/Sonner. Snapshot total/payments method_id/name/type/amount, tanpa
account_id/metadata bank. Index options.paymentMethods hanya diberikan kepada
pemegang pay, melalui public lookup maksimal1000, tanpa perlu finance.view.
Read menambah pointer/paid_from_version/paid_at/payment_snapshot nullable,
filter payment_status all/paid/unrecorded (posted tanpa pointer).
paid_from_version menyimpan revisi request asal, version naik sekali. Replay
versi asal/split canonical identik no-op sebelum lookup master; actor pertama
tetap, beda revisi/split422. Pengeluaran Finance dan snapshot/pointer/dua audit
Purchasing atomic dalam transaction retry3; Stock/FIFO tidak berubah. Existing
tidak otomatis lunas atau dibuatkan utang; saldo kas berjalan belum tersedia.
Rincian [spesifikasi PurchaseOrders](modules/Purchasing/PurchaseOrders/specification.md).

## Endpoint Internal POS

Route module Sales/POS, session web/auth/CSRF. URL/name existing /pos (pos.index)
dipertahankan; index/catalog/quote memerlukan sales.pos.invoices.create.
Gate super-system tidak melewati validasi bisnis. Tidak perlu permission admin
Inventory/Parties agar kasir dapat membaca data penjualan terbatas ini.

| Method | Path | Nama | Hasil |
| --- | --- | --- | --- |
| GET | /pos | pos.index | Inertia sales/pos/index, warehouses/customers aktif |
| GET | /pos/catalog | pos.catalog | items, has_more, customer |
| POST | /pos/quote | pos.quote | lines, subtotal, customer, warehouse, fingerprint, checked_at |
| GET | /pos/payment-methods | pos.payment-methods | methods aman; create+post wajib |
| POST | /pos/invoices | pos.invoices.store | receipt JSON201; create+post wajib |
| GET | /pos/invoices/{id} | pos.invoices.show | receipt snapshot; pemilik dengan post atau staff view |

Catalog: warehouse_id/customer_id wajib ULID; search optional maksimal 150,
page 1..100000. 25 kandidat/page, has_more; query SKU/nama/barcode unit jual
aktif. Master nonaktif/trashed tidak dipublikasikan. Unit dengan price null
tidak boleh dijual, tanpa fallback. Saldo base unit per gudang, default nol.
Payload item memiliki id/sku/name/base_unit_id/base_unit_name/stock/units;
unit id/code/name/ratio/barcode/price nullable. Tidak memuat HPP atau nilai FIFO.
Customer DTO id/code/name/levelId/levelName, tanpa data kontak.

Quote: warehouse_id/customer_id, lines list 1..100 dengan item_id/unit_id/quantity.
Quantity decimal string positif maksimal 14 integer/enam pecahan; unknown
row field atau root price/level/tax/payment ditolak. Pasangan item/unit unik;
stok yang dibutuhkan dijumlah per item lintas unit. Backend membaca ulang
pelanggan/level, barang/unit jual, harga dan stok dalam read transaction.
Price, conversion ratio, subtotal tidak dipercaya dari browser.

Response line: item_id/unit_id/base_unit_id/sku/name/unit_name/base_unit_name, quantity/base_quantity,
conversion_ratio/unit_price/subtotal; semua nilai decimal berupa string.
Base quantity harus tepat scale6/muat20,6; nilai uang tepat scale2/muat30,2,
bukan pembulatan silent. Invalid/master tidak tersedia/stok kurang/harga kosong
422; error per baris lines.{index}.quantity atau unit_id; root error cart/lines.
Guest JSON 401, web redirect login, permission 403, CSRF invalid 419.

Quote tidak disimpan, tidak reservasi stok, tidak membuat ledger/audit mutation,
dan bukan dasar mempercayai harga/stok saat posting. Fingerprint deterministic
mengecualikan checked_at, mencakup snapshot context/line/total; bukan harga bebas.

Posting menerima idempotency_key ULID, warehouse_id/customer_id, lines seperti
quote, quote_fingerprint64 hex lowercase dan payments list1..10 method_id/amount
decimal string scale2. Unknown field ditolak; total/harga/ratio/HPP/nomor/actor/
diskon/pajak/status bukan input client. Current master/price/stock dilock dan
dihitung ulang; quote berubah422 field quote_fingerprint. Kurang bayar, metode
invalid, stok kurang dan payload invalid422, tanpa mutation parsial. Create/retry
identik201, key berbeda payload422 idempotency_key. Scope key per actor;
retry tidak menambah invoice/Stock/Finance/audit. Auth401/403, CSRF419.

Invoice receipt: ID/number POS-ULID, snapshot gudang/pelanggan/level, total,
diskon/pajak nol, line snapshot dan payments total/tendered/change/baris metode
dengan applied bersih. Tidak memuat HPP, request_hash/idempotency key, actor,
kontak pelanggan atau metadata rekening. Detail GET ownership dicek backend,
view boleh lintas pemilik; tidak ada PUT/DELETE invoice posted. Pilihan metode
id/code/name/type saja, active account/type compatible, bukan permission CRUD
Finance. UI multi-payment/recovery/receipt memakai endpoint ini sejak Increment 5.
Retry pending mempertahankan key/payload; recovery browser bukan offline queue. Detail
[specification POS](modules/Sales/POS/specification.md).

## Riwayat Internal POS

GET /sales/invoices (sales.invoices.index) merender Inertia
sales/pos/history/index; GET /sales/invoices/data (sales.invoices.data) memberi
props JSON yang sama. Keduanya auth + authorization post ATAU view. Post hanya
pemilik; view lintas pemilik. Tidak ada parameter client untuk mengatur scope.
Create-only ditolak403; guest login/401. Receipt tetap GET /pos/invoices/{id},
tanpa mengubah contract/ownership existing.

Filter opsional search substring nomor literal (max150, tanpa wildcard bebas),
date_from/date_to YYYY-MM-DD inklusif WIB, warehouse_id/customer_id ULID,
page positif; field lain atau rentang invalid422. Null/kosong berarti semua.
Response records {data,meta}, filters canonical, viewAll, warehouseOptions dan
customerOptions. Pagination25 order posted_at desc/id desc. List/total/options
semua ter-scope; opsi dari snapshot authorized bounded1000 per jenis termasuk
master tidak aktif/terhapus. Tidak membuka HPP/key/hash/actor/kontak/rekening.
Read dan reprint tidak menulis ledger/audit. Detail
[riwayat](modules/Sales/POS/work-items/invoice-history/README.md).

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
Field additive `prices` berupa list unit_id/customer_level_id/amount; amount
tetap decimal string canonical maksimal dua desimal, bukan float.
Input barang: sku/name/base_unit_id ULID; optional is_active; optional units
list maksimal 100 row. Row berisi unit_id ULID distinct, conversion_ratio
decimal string, optional barcode nullable dan flag boolean jual/beli. ULID
input dinormalisasi lowercase; barcode '0' tidak diubah menjadi null. SKU dan
barcode unik termasuk data terhapus; barcode payload distinct case-insensitive.
Action memvalidasi satuan aktif dan ratio; base row otomatis bila belum ada.
Error request memakai field asal; error invariant memakai units; konflik unique
saat persistence memakai inventory. Form UI menampilkan error
umum tersebut selain error per field.

Create/update barang wajib menyertakan matrix `prices` lengkap untuk setiap
satuan jual x seluruh level pelanggan aktif. Row berisi unit_id ULID,
customer_level_id ULID, amount string positif maksimal 18 digit integer dan
dua pecahan; maksimal 5.000 kombinasi. Pasangan asing/duplikat, harga nol,
level/satuan stale dan matrix tidak lengkap menghasilkan 422. Error matrix
memakai `prices`; amount memakai `prices.{index}.amount`. Barang tanpa satuan
jual dapat mengirim list kosong. Permission tetap inventory.manage, termasuk
untuk mutation harga dari workflow barang; tidak membuat endpoint harga bebas.
Katalog, relasi, harga dan audit tersimpan dalam satu transaction.

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
tetap tanpa filter; penambahan field prices tidak menghapus field existing.
Barang juga dapat dicari lewat barcode relasi aktif. Props unitOptions berasal
dari UnitLookup::listActive, maksimal 1000 satuan aktif, urut code/id. Form
menyimpan rasio sebagai string, bukan float. Base unit terkunci saat edit.
Barcode duplicate case-insensitive pada payload update ditolak sebelum mutation.
Props `priceLevels` berisi id/code/name level aktif, urut id, maksimal 1000.
UI menampilkan harga dengan grouping titik/koma pecahan dan mengirim canonical
decimal string. Tidak ada harga fallback atau harga pokok pada form master ini.

### Endpoint Internal Stock

Session web/auth/verified/CSRF, bukan API eksternal. Prefix `/inventory/stock`,
nama `inventory.stock.*`. Super-system bypass permission, bukan invariant bisnis.

| Method | Path suffix | Nama suffix | Permission |
| --- | --- | --- | --- |
| GET | / | index | inventory.stock.view |
| GET | /data | data | inventory.stock.view |
| POST | /openings | openings.store | inventory.stock.manage |
| PUT | /openings/{id} | openings.update | inventory.stock.manage |
| DELETE | /openings/{id} | openings.destroy | inventory.stock.manage |
| POST | /openings/{id}/restore | openings.restore | inventory.stock.manage |
| POST | /openings/{id}/post | openings.post | inventory.stock.post |

ID route ULID. Index Inertia `inventory/stock/index` memiliki props records,
filters dan options; data JSON `{data, meta}`, 25 record/page. Filter tab
openings/balances/movements, page 1..2147483647, search maksimum 150, warehouse_id
dan item_id ULID, status all/draft/posted, trashed boolean. Status/trashed hanya
memengaruhi daftar dokumen. Pilihan master aktif maksimal 1000 per katalog.

Payload create/update: warehouse_id, notes nullable maksimum 500, lines 1..100
dengan item_id distinct, unit_id milik barang, quantity dan unit_cost decimal
string positif maksimum enam desimal. ULID master dinormalisasi lowercase
sebelum duplicate validation. Rasio diambil dari master, bukan dipercaya dari
payload. Quantity x ratio dan quantity x unit_cost harus exact, tanpa pembulatan
silent. Harga pokok per satuan pilihan, bukan per base unit.

Create JSON 201 `{id}`, mutation lain 200 `{id}`; web redirect/flash success.
Invalid input/invariant 422 (stock untuk invariant); guest JSON 401, permission
403, target hilang/lifecycle invalid 404 atau invariant 422 sesuai kondisi.
Draft delete/restore tidak mengubah stok. Posted tidak boleh edit/delete/restore;
posting ulang id yang sama idempotent tanpa saldo/audit ganda. Pasangan barang +
gudang yang sudah memiliki saldo/histori menolak stok awal berikutnya.

Posting membuat balance, movement masuk dan FIFO layer bersama audit dalam
satu transaction. Snapshot SKU/nama barang, unit/base unit, rasio, gudang dan
harga pokok menjaga histori rename. Kuantitas dan nilai JSON tetap decimal
string. Timestamp posted_at dikirim ISO UTC, ditampilkan menurut timezone
browser. Tidak ada consumption FIFO, pengeluaran, transfer atau reversal UI.
Permission seeder tidak memberi role/grant otomatis; demo eksplisit local/testing.

### Endpoint Internal Identity

Session web/auth/verified/CSRF mengikuti starter kit seperti Inventory. Ini bukan
API eksternal. Role super-system web bypass permission Gate sesuai ADR-010,
tetapi tetap tunduk pada validasi, transaction dan invariant akses.

| Method | Path | Nama route | Permission |
| --- | --- | --- | --- |
| GET | /platform/identity | platform.identity.index | platform.identity.view atau users.view khusus tab users |
| POST | /platform/identity/roles | platform.identity.roles.store | platform.identity.manage |
| PUT | /platform/identity/roles/{id} | platform.identity.roles.update | platform.identity.manage |
| DELETE | /platform/identity/roles/{id} | platform.identity.roles.destroy | platform.identity.manage |
| PUT | /platform/identity/roles/{id}/permissions | platform.identity.roles.permissions | platform.identity.manage |
| PUT | /platform/identity/users/{id}/roles | platform.identity.users.roles | platform.identity.manage |
| POST | /platform/identity/users | platform.identity.users.store | platform.identity.users.create |
| PUT | /platform/identity/users/{id} | platform.identity.users.update | platform.identity.users.update |
| PATCH | /platform/identity/users/{id}/status | platform.identity.users.status | platform.identity.users.manage |

ID User/Role/Permission existing integer mengikuti starter kit/package, bukan
migrasi ULID pada pekerjaan UI ini. Target role hanya guard web; guard lain atau
target hilang menghasilkan 404. GET menghasilkan Inertia component
platform/identity/index dengan records `{data, meta}`, filters `{tab, search}`,
options `{roles, permissions, active_super_users}`. Tab roles/permissions/users; search maksimum 150
karakter; page 1..2147483647, 25 record/page, order name/id.
Role fields id/name/permissions/users_count; permission id/name/roles_count;
user id/name/email/roles/direct_permissions_count/is_active/can_edit. Tidak
mengekspos password, remember_token atau access_version.
users.view saja default tab users dan menolak roles/permissions; identity.view
tetap memiliki akses tiga tab seperti sebelumnya.
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
assignment target super-system, dan minimal satu pemegang aktif harus tersisa.
Assignment web mempertahankan direct grant/role guard lain/atribut akun.
Mutation dan audit atomic, no-op tanpa audit ganda, cache refresh sesudah commit.
Last-super juga ditegakkan pada self-delete profil seperti contract di bawah.
Direct grants dan key permission bebas tidak tersedia.

Create pengguna: name/email wajib maksimum 255, trim nama dan lowercase/trim
email unik case-insensitive; password awal minimal 12 karakter/maksimal 72 byte,
confirmed, roles list
distinct maksimum 1000 (boleh kosong). Role nonkosong juga memerlukan
identity.manage; pengaman grant/reserved tetap berlaku, super-admin legacy tidak
bisa diberikan ke akun baru. Password hashed; email_verified_at tetap null.
PUT profil admin menerima name/email saja, tanpa password/roles/status;
email berubah mencabut remember/reset token lama serta verification.
PATCH status memerlukan is_active boolean; no-op tanpa audit/version ganda.
Operator tidak dapat edit/status pengguna super/higher privilege/guard lain.
Self-deactivation dan last-super aktif ditolak 422 account; Gate bypass tidak
melompati invariant. Belum tersedia reset password atau hapus akun oleh admin.
Audit user_created hanya roles, user_updated hanya changed_fields, dan
user_status_changed hanya before/after is_active; tidak menyimpan PII/credential.

Akun nonaktif tetap tersimpan beserta pivot, bukan soft delete. Deaktivasi
menaikkan users.access_version, mengganti remember token dan menghapus reset
token secara atomic. Aktivasi tidak menurunkan version atau mengembalikan sesi
lama. Login menolak is_active=false dan menyimpan version terbaru; middleware
web menolak state inactive/version lama (401 JSON atau redirect login) sebelum
request berikutnya dilayani. Remember recaller lama tidak berlaku setelah
deaktivasi; recaller baru valid dapat login setelah reaktivasi. Tidak mengklaim
cancel request yang sudah berjalan atau cleanup fisik semua driver session.

### Penghapusan Akun Pribadi

DELETE `/settings/profile`, route profile.destroy existing, auth session web dan
CSRF. Bukan admin CRUD akun dan tidak membutuhkan platform.identity.manage.
Input hanya password wajib/current_password; target berasal request user, bukan
id/role dalam payload. ID akun existing tetap integer starter kit.

Super-system web terakhir ditolak validation field account; password salah field
password. JSON error 422 atau web redirect/session errors. Success tetap redirect
`/` seperti starter kit. Application DeleteOwnAccount mengambil lock role web
dengan urutan sama seperti assignment, lalu lock user, membaca roles fresh dan
menegakkan invariant sebelum audit/delete. Audit failure, exception/veto delete
rollback audit/pivot/account; operasi tersebut tidak logout atau invalidate sesi.
Logout dan invalidation sesi dilakukan sesudah action berhasil. Semantik hard
delete existing dipertahankan, bukan soft-delete lifecycle baru.

Audit identity.account_deleted memuat causer/subject ID, before roles dan after
deleted saja; tidak memuat nama/email/credential. Relasi User audit dapat null
sesudah delete, ID historis tetap. Tidak ada route/permission/schema/seeder baru.
Session perangkat lain/retention data dan ownership transaksi bisnis memerlukan
work item terpisah sebelum ada transaksi real yang mereferensikan akun.

## Master Finance/Payments

Master kas/bank pada `/finance/payment-accounts` dan metode pembayaran pada
`/finance/payment-methods`. Nama route `finance.payment-accounts.*` dan
`finance.payment-methods.*`. Masing-masing memiliki GET index, GET `/data`,
POST store, PUT `/{id}`, DELETE `/{id}`, POST `/{id}/restore`. Route web memakai
auth/verified/CSRF; middleware controller `can:finance.payments.view` untuk baca
dan `can:finance.payments.manage` untuk mutation. Super-system hanya bypass
permission, bukan validasi atau invariant relasi.

JSON create menghasilkan 201 `{id}`, mutation lain 200 `{id}`; form Inertia
redirect dengan error bag atau success. Validasi 422; resource valid namun
tidak ditemukan 404; unauthorized 401/403 sesuai konteks JSON. Kode unique
termasuk terhapus, trim/uppercase. PUT mewajibkan `is_active` eksplisit. Payload
unknown tidak dipersist, termasuk nilai saldo buatan client.

Account: code/name/type cash|bank/is_active, metadata bank opsional
bank_name/account_number/account_holder. Nomor rekening string; metadata hanya
boleh terisi untuk bank. Metode: code/name/type cash|debit|transfer,
payment_account_id/is_active. Cash menunjuk cash; debit/transfer menunjuk bank.
Akun harus aktif/non-trashed saat simpan atau restore metode. Akun yang dirujuk
metode, termasuk metode nonaktif/terhapus, tidak boleh dihapus/nonaktif/ganti
jenis. Field error relasi `payment_account_id`; guard akun `payments`.

Index Inertia `records/filters`; metode menambah `accountOptions` aktif maksimal
1000, tanpa metadata rekening. JSON data `{data,meta}`, 25/baris urutcode/id.
Filter page positif, search code/nama maksimal150, status all|active|inactive,
trashed0|1, type sesuai master dan payment_account_id pada metode. Baca metadata
account membutuhkan permission view; UI daftar menyamarkan nomor rekening.

Log `finance.payment_accounts`/`finance.payment_methods` atomic dengan mutation.
Nilai metadata bank tidak masuk audit; perubahan metadata-only mencatat nama
field saja. No-op tanpa audit. PaymentsRepository adalah port internal, bukan
published payment boundary. RecordSalePayment internal sudah menyimpan
payment_receipts/payment_records snapshot, applied net, tendered/change dan
audit received atomic. Tidak ada route penerimaan Finance bebas.
Consumer PostInvoice sudah memakai SalePaymentWriter/DTOs;
pilihan metode melalui SalePaymentMethods. Metode berhistori tidak boleh dihapus,
dinonaktifkan, diganti type atau pindah akun (422 field payments), termasuk
super-system; nama/kode tetap boleh berubah. Akun memiliki guard histori langsung.
Belum ada running balance uang atau piutang. UI checkout dimiliki Sales/POS,
bukan endpoint penerimaan Finance bebas.

## Contract Lintas Module

Contract lintas module tidak didokumentasikan sebagai HTTP API kecuali benar-
benar diekspos melalui endpoint. Public boundary internal tetap mengikuti
[ARCHITECTURE.md](ARCHITECTURE.md):

- `Application/Contracts`
- `Application/DTOs`
- `Application/Events`

### Inventory/Units

Consumer nyata: CreateItem/ManageItem dan seeder demo Items; PrepareOpening dan
StockWorkspace memakai validasi/lock atau pilihan satuan Stock.
Model Infrastructure Units bersifat privat.

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

ItemStockLookup::findActive(id, lock = false) menghasilkan ItemStockData atau null;
choices() mengembalikan maksimal 1000 barang aktif/non-trashed untuk input Stock.
DTO memiliki id, sku, name, baseUnitId, units (ItemUnitData). Lock wajib di dalam
transaction default bersama, setelah lock selected/base Units terurut. Adapter
memakai current read untuk relasi unit saat lock diminta. Consumer: PrepareOpening,
StockWorkspace dan StockDemoSeeder; bukan contract laporan atau pricing.

ItemPricingLookup::findIdBySku(sku) mengembalikan ID non-trashed atau null untuk
demo pricing; lock(itemId) menghasilkan ItemPricingData atau null. DTO berisi
id/saleUnitIds/saleUnits (ItemUnitData dengan rasio exact). Item nonaktif tetap
bisa diedit, tetapi item terhapus/satuan unavailable ditolak. Lock memerlukan
transaction default bersama, mengunci Units terurut, Item, lalu relasi current
read. Consumer nyata: ManageItemPrices dan ItemPricesDemoSeeder; model tetap privat.

### Sales/Parties Pricing

ItemPriceCatalog::activeLevels(lock = false) menghasilkan PriceLevelData[]
(id/code/name), maksimal 1000. Lock memerlukan transaction; saveForItem(itemId,
ItemPriceData[]) memvalidasi matrix current dan menyimpan harga atomic;
forItems(itemIds, customerLevelId = null, lock = false) menghasilkan map itemId ke list ItemPriceData
(unitId/customerLevelId/amount). Consumer: SaveItemCatalog, ListItems dan demo.
Repository harga bukan public mutation boundary. Urutan lock workflow save:
level aktif terurut, Units terurut, Item, relasi dan harga. Tidak ada fallback
level, float, atau pricing kuantitas. Consumer PostInvoice memakai lock/current
read pada transaction default bersama, bukan repository harga privat.

Filter level opsional dipakai SaleCatalog/CheckCart POS agar hanya mengambil
harga level pelanggan terpilih. Default null mempertahankan behavior editor
Items dan provisioning existing. POS tidak mengakses repository harga privat.

### Published Read Boundary Untuk POS

- Items/SaleItemCatalog::search(query, page): data SaleItemData dan has_more,
  25 kandidat/page; findMany(ids, lock = false) map maksimal 100 barang. DTO item:
  id/sku/name/baseUnitId/baseUnitName/units; SaleUnitData:
  id/code/name/ratio/barcode. Aktif/non-trashed dan flag jual wajib; barcode
  satuan nonaktif tidak boleh menjadi hasil pencarian. Filter active unit lewat
  UnitLookup, bukan model/SQL privat Units. Filtering sesudah kandidat dapat
  membuat jumlah hasil halaman kurang dari 25; has_more tetap pada kandidat.
- Units/UnitLookup::findManyActive(ids): map id ke UnitData, maksimal 11000 ID
  untuk batch metadata item/unit tanpa N+1 atau batas choices 1000.
- Parties/CustomerSalesLookup::choices(): maksimal 1000 SaleCustomerData;
  findActive(id, lock = false): DTO atau null. Customer dan level harus aktif/non-trashed;
  kontak tidak dipublikasikan. Consumer SaleContext pada POS.
- Stock/StockAvailability::forItems(warehouseId, itemIds, lock = false): map maksimal 100 ID
  ke quantity base unit canonical scale6, nol jika tanpa saldo. Hanya membaca
  stock_balances; default tidak lock/reservasi atau memuat nilai stok/FIFO.
  Lock true wajib transaction default bersama dan memakai current read. Hal
  sama berlaku pada lock SaleItemCatalog/CustomerSalesLookup/ItemPriceCatalog;
  POS memegang lock gudang/level terlebih dahulu sesuai mutation master existing.
- WarehouseLookup dipakai SaleContext untuk pilihan dan validasi gudang POS,
  di samping consumer Stock existing.

### Inventory/Warehouses

WarehouseLookup::findActive(id, lock = false) menghasilkan WarehouseData atau null;
choices() maksimal 1000 gudang aktif/non-trashed untuk consumer Stock.
WarehouseData memiliki id/code/name. Lock memerlukan transaction default
bersama. Consumer tidak mengimpor Eloquent model Warehouses.

### Inventory/Stock

StockUsage::hasItemHistory(itemId) dan hasWarehouseHistory(warehouseId) dipakai
ManageItem/ManageWarehouse untuk guard master. Caller memegang lock master
terkait dalam transaction default bersama; adapter Stock memakai current read.
Riwayat saldo/movement/layer tidak boleh diputus melalui delete/nonaktif master.
StockRepository adalah port internal, bukan public mutation lintas module.
SaleStockWriter::issue(SaleStockRequest) dikonsumsi PostInvoice, wajib transaction
caller. DTO request warehouse/source ID/number/line source ID/item/base quantity/
actor; result SaleStockResult memuat issue ID dan map line cost scale6.

IssueStock mengimplementasikan SaleStockWriter tanpa endpoint pengeluaran bebas.
StockRepository tetap port internal; outflow dipakai IssueStock.
`inventory.stock.data?tab=movements` tetap route existing dengan permission
inventory.stock.view, tetapi quantity/total_value dapat signed negatif untuk
sale. Snapshot sumber menghasilkan number/SKU/item/base-unit/gudang tanpa join
privat POS. Opening legacy tetap terlihat; filter/pagination existing berlaku.
Tidak ada route store/delete pengeluaran atau bypass permission melalui UI.

PurchaseStockWriter::receive(PurchaseStockRequest) dikonsumsi PostPurchaseOrder,
wajib transaction default caller. Request warehouseId/sourceId/sourceNumber,
lines source_line_id/item_id/base_unit_id/base_quantity/total_value dan actorId
nullable. Result PurchaseStockResult receipt ID/postedAt. ReceivePurchaseStock
menormalisasi hash/source, memvalidasi base unit/master, lock gudang/saldo/layer,
dan menambah quantity/value exact beserta satu movement/layer per line.
Source purchase dan source_line unik; replay identik no-op, payload beda ditolak.
ReceiptBalance menolak saldo/FIFO tidak konsisten, orphan layer dan overflow.
Receipt/line immutable dengan audit received atomic, bukan StockRepository publik.
Saldo opening_line_id nullable dan snapshot metadata fallback menampilkan
purchase-only bersama legacy. Movement purchase positif, receipt_line_id unique
FK restrict; query existing tetap inventory.stock.view. Tidak ada route receipt
bebas, Finance, soft delete posted atau rollback yang membuang receipt.

### Finance/Payments Writer

PurchasePaymentWriter::pay(PurchasePaymentRequest) dikonsumsi PayPurchaseOrder,
wajib shared transaction. DTO sourceId/sourceNumber/total/payments/actorId,
result id/paidAt/snapshot aman. PurchasePaymentMethods::choices pilihan aktif
id/code/name/type maksimal1000. Ledger pengeluaran terpisah immutable exact6,
unique source/hash replay tanpa actor, lock akun/metode sorted dan current read.
Audit disbursed wajib. Bukan PaymentsRepository publik atau route Finance bebas.
Contract penerimaan sale existing di bawah ini tidak berubah (scale2).

SalePaymentWriter::receive(SalePaymentRequest) dikonsumsi PostInvoice pada
transaction default bersama. Request source ID/number/total/payments method ID/
amount/actor; result SalePaymentResult receipt ID dan snapshot aman total/
tendered/change/list method ID/name/type/tendered/applied/change. Writer menolak
panggilan di luar transaction caller, tidak mengekspos account metadata atau
PaymentsRepository. SalePaymentMethods::choices dipakai controller POS untuk
opsi id/code/name/type aktif dan compatible, maksimal1000. Bukan payment gateway.
