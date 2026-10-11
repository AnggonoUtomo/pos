# Work Item: Migrasi Role Super Lama

## Status

Completed. Implementasi, QA, migrasi lokal dan delivery remote terverifikasi.

## Kondisi Dan Keputusan

Pada 11 Oktober 2026 database lokal memiliki satu role `super-admin` guard web,
satu user terkait dan delapan permission lama. Role ini tidak memiliki
inventory.view/manage dan tidak memenuhi Gate `super-system`, sehingga akses
Inventory ditolak 403. Fondasi permission sudah ada; UI pengelolaan akses belum
dibuat. User menyetujui migrasi role lama menjadi super-system sesuai ADR-010.

## Scope

Platform/Identity menyediakan Application action dan command administratif
`identity:migrate-super-role`, preview secara default, mutation hanya `--apply`.
Rename mempertahankan ID/pivot/user/permission/password, diaudit secara atomic,
idempotent, guard web saja, menolak konflik dua role. Tidak ada schema baru,
alias bypass super-admin, assignment baru atau seeder akun yang dijalankan ulang.

## Di Luar Scope

UI CRUD role/permission/user, perubahan permission matrix, perubahan auth/theme,
Old-docs, serta merge role otomatis. Command administratif hanya untuk operator
yang memiliki akses CLI deployment, tidak diekspos sebagai route HTTP.

## Risiko

Rename mengaktifkan bypass Gate bagi user role lama sesuai persetujuan user.
Cache permission perlu dihapus sesudah commit. Tidak melakukan rollback akses
diam-diam: koreksi akses membutuhkan operasi administratif audited terpisah.
Sesi browser mungkin perlu refresh penuh agar shared auth terbaru terbaca.

## Verifikasi

Test command/action: preview, preservation, audit atomic, repeat, conflict,
guard lain, HTTP Inventory dan shared auth. Smoke MySQL hanya pada role yang
disetujui, tidak menampilkan credential/data pribadi. Tidak reset database.

## Handoff

Pada 11 Oktober 2026 role lokal berhasil menjadi super-system dengan ID, seluruh
atribut user dan pivot unchanged. Audit 1 event; Gate Inventory dan shared auth
aktif, repeat no-op. Focused 6 test/34 assertion, regresi 156 test/887 assertion,
Pint/help/diff PASS. MCP tidak tersedia; browser/frontend SKIPPED karena tidak
mengubah UI, fallback HTTP dan smoke backend lulus. Refresh browser untuk props
auth terbaru. UI manajemen akses belum dibuat; theme/Old-docs/artefak user tetap.
