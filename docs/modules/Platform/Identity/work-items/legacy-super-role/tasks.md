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

- [ ] Preview dan terapkan --apply pada database lokal sesuai persetujuan user.
  - Acceptance: role yang sama menjadi super-system; tidak reset data/credential.
  - Verification: ID/pivot/count sebelum/sesudah, permission Gate dan audit.
- [ ] Perbarui docs, commit dan push.
  - Acceptance: status jujur; scope UI management masih pending; remote sinkron.
  - Verification: staging, push dan status remote.

## Sesudah Coding

- [ ] Test, format, command help dan diff lulus.
- [ ] Operasi lokal terverifikasi dan risiko tersisa dicatat.
- [ ] UI/browser/frontend SKIPPED/BLOCKED dengan alasan dan fallback.
- [ ] Old-docs, artefak user, theme dan credential tidak disentuh.

## Hasil Verifikasi

Diagnosis: 1 super-admin web, 1 user, 8 permission; Gate inventory.view/manage
false dan isSuperSystem false. Baseline regression sebelum perubahan:
SuperSystemGateTest + InventoryMasterHttpTest 15 test/194 assertion PASS.

Increment 1: RED 6 test sebelum action/command tersedia, kemudian GREEN 6
test/34 assertion. Full regression 156 test/887 assertion PASS; Pint dirty test,
help command dan git diff --check PASS. Preview MySQL lokal menemukan role lama
tanpa mutation. Assertion atribut user memakai boolean agar kegagalan tidak
mencetak credential. Tidak menambah schema/route/frontend/dependency.
