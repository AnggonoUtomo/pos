# Specification: Inventory/Units

## Status Dan Scope

Fondasi backend Implemented pada 9 Oktober 2026. Memiliki master satuan untuk retail/grosir
tanpa saldo stok, konversi global, harga, barcode barang, atau transaksi POS.

## Arsitektur

- Composition root: `app/Modules/Inventory/Units/ServiceProvider.php`.
- Migration, seeder, route scaffold, dan model berada di dalam module.
- Eloquent model Unit berada pada Infrastructure/Persistence/Models.
- Seeder menjadi adapter provisioning dengan database transaction.
- Tidak ada Domain tanpa business rule murni. CreateUnit/ListUnits memakai
  UnitRepository/CreateUnitData; adapter Eloquent dibinding oleh provider.
- UnitLookup dan UnitData menjadi public boundary dengan consumer CreateItem.
  Adapter Eloquent menyaring status aktif/soft delete; lockForUpdate hanya di
  dalam transaction connection default bersama. Base unit, konversi, dan aturan
  precision dimiliki Items; model privat Units tidak diimpor oleh runtime Items.

## Data

| Kolom | Ketentuan |
| --- | --- |
| `id` | ULID primary key |
| `code` | String maksimal 32, unique termasuk soft-deleted row |
| `name` | String maksimal 150 |
| `symbol` | String maksimal 16, simbol tampil satuan |
| `is_active` | Boolean default true |
| `created_at`, `updated_at` | Timestamp Laravel |
| `deleted_at` | Soft delete master mutable |

Tidak ada conversion_ratio atau is_base_unit pada schema. Kode diisi eksplisit;
normalisasi code/name/symbol memakai trim, validasi wajib/panjang pada create.
Satuan nonaktif/terhapus
tidak boleh dipilih untuk relasi barang baru saat Items dibuat.

## Seeder Demo

| Kode | Nama | Simbol |
| --- | --- | --- |
| PCS | Buah | pcs |
| PACK | Pak | pak |
| BOX | Dus | dus |
| KG | Kilogram | kg |
| G | Gram | g |
| L | Liter | l |
| ML | Mililiter | ml |

Seeder mencari existing row termasuk soft-deleted row. Pengulangan tidak
menimpa nama/simbol/status, mengganti ULID, mengembalikan data terhapus, atau
menghasilkan audit tanpa perubahan. Demo ditolak di luar local/testing.
Access seeder terpisah, tidak mengubah akun/role/permission user.

## Authorization Dan Audit

- Permission inventory.view dan inventory.manage, guard web.
- Endpoint data/store memakai HasMiddleware, can:inventory.view/manage per
  action, auth dan verified mengikuti starter kit. Detail response ada di API.md.
- Spatie Activitylog mencatat create/update/delete/restore dan actor jika ada.
- Atribut audit hanya code, name, symbol, is_active, deleted_at.
- Update tanpa perubahan tidak menambah log; subject audit memakai ULID dengan
  compatibility migration existing dari fondasi Warehouses.
- CreateUnit memakai Application transaction untuk data/audit; mutation CRUD
  lain wajib mengikuti boundary yang sama.

## UI Dan Risiko

Belum ada UI baru. Increment 6 wajib memakai Sonner, dropdown shortcut,
permission UX, dan QA desktop/tablet. Integrasi POS dan FIFO tidak tersentuh.
ManageUnit menjalankan update/delete/restore dalam transaction. Update nonaktif
dan delete menolak satuan yang pernah dirujuk barang melalui public
Items/Application/Contracts/ItemUnitUsage::isReferenced. Referensi mencakup
item dan item_units soft-deleted. Lock master satuan konsisten dengan create/edit
Items untuk mencegah satuan dinonaktifkan saat relasi baru dibuat. Tidak ada
dependency ke model privat Items. Keputusan user: 10 Oktober 2026.

## Acceptance Criteria

- [x] Provider ditemukan dan schema units tersedia.
- [x] ULID, kode unik, default aktif, dan soft delete terverifikasi.
- [x] Demo seeder menjaga existing row dan menolak environment produksi.
- [x] Permission seed idempotent dan tidak memberi akses user otomatis.
- [x] Audit lifecycle, actor/subject, dan update tanpa perubahan terverifikasi.
