# Tasks: Migrasi Role Super Lama

## Sebelum Mulai

- [x] AGENTS, docs/README, arsitektur, struktur dan dokumen Identity dibaca.
- [x] Target Platform/Identity diinformasikan; diagnosis MySQL read-only.
- [x] User menyetujui rename role lama, menjaga relasi, tanpa reset password.
- [x] Scope command administratif; UI manajemen akses tidak diklaim tersedia.

## Increment 1: Action Dan Command

- [x] Tulis failing test migrasi dan preview.
  - Acceptance: 403 sebelum rename; akses sesudah rename; ID/pivot/user tetap.
  - Verification: focused test RED sebelum implementation.
- [x] Implementasikan transaction/lock/audit dan command opt-in.
  - Acceptance: preview nonmutating, --apply explicit, repeat aman; konflik
    dua role ditolak tanpa perubahan; guard lain tidak ikut rename.
  - Verification: focused tests termasuk rollback audit dan shared auth.
- [x] Verifikasi source dan commit selektif.
  - Acceptance: regresi penuh, format, help command dan diff lulus.
  - Verification: hasil command aktual dicatat.

## Increment 2: Migrasi Lokal

- [x] Preview dan terapkan --apply pada database lokal sesuai persetujuan user.
  - Acceptance: role yang sama menjadi super-system; tidak reset data/credential.
  - Verification: ID/pivot/count sebelum/sesudah, permission Gate dan audit.
- [x] Perbarui docs dan commit bukti operasi lokal.
  - Acceptance: status jujur; scope UI management masih pending; bukti dicatat.
  - Verification: staging selektif dan hasil smoke dicatat.
- [ ] Push dan verifikasi status remote.
  - Acceptance: seluruh work item terverifikasi dikirim tanpa artefak user.
  - Verification: push berhasil dan HEAD cocok origin/main.

## Sesudah Coding

- [x] Test, format, command help dan diff lulus.
- [x] Operasi lokal terverifikasi dan risiko tersisa dicatat.
- [x] UI/browser/frontend SKIPPED/BLOCKED dengan alasan dan fallback.
- [x] Old-docs, artefak user, theme dan credential tidak disentuh.

## Hasil Verifikasi

Diagnosis: 1 super-admin web, 1 user, 8 permission; Gate inventory.view/manage
false dan isSuperSystem false. Baseline regression sebelum perubahan:
SuperSystemGateTest + InventoryMasterHttpTest 15 test/194 assertion PASS.

Increment 1: RED 6 test sebelum action/command tersedia, kemudian GREEN 6
test/34 assertion. Full regression 156 test/887 assertion PASS; Pint dirty test,
help command dan git diff --check PASS. Preview MySQL lokal menemukan role lama
tanpa mutation. Assertion atribut user memakai boolean agar kegagalan tidak
mencetak credential. Tidak menambah schema/route/frontend/dependency.

Increment 2 pada 11 Oktober 2026:

| Pemeriksaan | Hasil | Bukti |
| --- | --- | --- |
| `identity:migrate-super-role` preview | PASS | Role lama ditemukan; tidak menulis data |
| `identity:migrate-super-role --apply` via Artisan dengan snapshot in-memory | PASS | Exit 0; ID role tetap; seluruh atribut user, pivot role user dan pivot permission role sama |
| Gate MySQL user terkait | PASS | inventory.view dan inventory.manage true |
| Shared auth MySQL user terkait | PASS | superSystem true, roles.super-system true |
| Audit migrasi | PASS | 1 event identity.legacy_super_role_migrated pada role yang sama |
| Repeat --apply | PASS | No-op; tetap 1 role/1 audit migrasi |
| `php artisan migrate:status` | PASS | Semua 9 migration Ran; tidak mengubah schema |
| HTTP regression pada SQLite disposable | PASS | Test 403 sebelum rename dan 200 sesudah rename; shared auth dan preservation ikut diuji |
| Chrome DevTools MCP | BLOCKED | Tool tidak tersedia di sesi; tidak mengakses sesi login user |
| Browser/lint/typecheck/build | SKIPPED | Tidak mengubah UI/TS/CSS; fallback HTTP test dan Gate/shared auth lokal PASS |
| `git diff --check` | PASS | Whitespace bersih |

Save point implementasi: 9b700cd. Tidak menjalankan seeder akun, tidak reset
password/database, tidak mengubah permission assignment/pivot atau data Inventory.
Rename nama role memang memperluas Gate sesuai persetujuan eksplisit user;
validasi stok/barang/transaction tidak dibypass. Browser perlu refresh penuh agar
props auth lama diganti. UI manajemen role/permission/user masih pending sebagai
work item baru, bukan fitur yang diselesaikan oleh perbaikan 403 ini.
