# Work Item: Manajemen Pengguna

## Status

Done

## Owner Dan Kondisi Awal

Platform/Identity; backend app/Modules/Platform/Identity dan frontend
resources/js/pages/platform/identity. Permission users.create sudah ada pada
seeder awal, tetapi belum memiliki endpoint/action/UI. User menyetujui tambah
akun, edit nama/email, assignment role dan nonaktifkan/aktifkan akun.

## Scope Dan Acceptance

- [x] Admin membuat akun dengan password awal dan konfirmasi; password hashed,
  tidak masuk audit/props/history form. Registrasi publik tetap tidak tersedia.
- [x] Edit nama/email tanpa mengubah password atau direct grants/guard lain.
- [x] Assignment tetap memakai use case existing dengan anti-escalation.
- [x] Status nonaktif menolak login dan request sesi lama, termasuk sesudah
  reaktivasi. Minimal satu super-system aktif; self-deactivation dilarang.
- [x] Mutation audited dan atomic; audit gagal membatalkan perubahan.
- [x] UI tab pengguna, badge status, dialog, shortcut, Sonner, desktop/tablet.

## Tidak Dikerjakan

Reset password admin, hapus/soft-delete akun admin, email invitation, public
registration, migrasi integer ID existing, stok/FIFO/payment, theme dan Old-docs.
Tidak menjalankan ulang seeder akun existing atau reset credential MySQL.

## Dampak Dan Pengaman

Migration module menambah is_active default true dan access_version default 0
pada users existing. Penonaktifan mempertahankan row/pivot: bukan soft delete,
karena scope ini tidak menyediakan operasi hapus. Self-delete existing tetap
hard delete audited. Hitungan last-super memperhitungkan akun aktif.

Permission users.view/create/update/manage didaftarkan seeder permission-only.
users.manage mengatur status, identity.manage mengatur role; tidak ada implicit
grant baru. GET existing menerima identity.view atau users.view. Manager biasa
tidak boleh mengedit identitas/status pengguna dengan akses di luar miliknya,
terutama super-system; ini menghindari takeover lewat perubahan email.

Session access_version tersimpan saat login; naik saat deaktivasi dan tidak
turun saat aktivasi. Remember token diganti dan reset token dibuang saat
deaktivasi/email berubah. Middleware web sebelum Inertia/authorization menolak
akun nonaktif/version lama. Request yang sudah berjalan sebelum perubahan tidak
diklaim terhenti; concurrency/load MySQL tetap risiko yang perlu pengujian khusus.

Demo seeder akun baru SKIPPED: fixture QA disposable relevan tersedia, sedangkan
seeder demo akun berpassword statis berisiko menimpa akun. Seeder role existing
tetap dan tidak mendapat permission baru otomatis.

## Increment

| Increment | Status | Isi | Verifikasi |
| --- | --- | --- | --- |
| 1 | Passed | Scope, PRD, plan/checklist sebelum coding | Review dan diff |
| 2 | Passed | Tambah/edit akun, permission dan audit | 51 test/305 assertion, route, Pint |
| 3 | Passed | Status, sesi dan last-super aktif | 82 test/420 assertion, migration |
| 4 | Passed | UI, QA dan dokumentasi final | 205 test/1213 assertion, UI/browser gates |

## Handoff

Create/edit/status internal sudah tersedia; seluruh verification scope PASS.
Chrome DevTools smoke PASS dan workflow Playwright disposable PASS (bukan
klaim workflow authenticated via MCP). Detail command/artefak ada di tasks.md.
Migration + permission-only seed MySQL lokal diterapkan dengan snapshot akun,
credential, role dan pivot tetap identik. QA destructive hanya SQLite disposable.
Delivery commit/push dicatat sesudah hasil Git tersedia.

Risiko: concurrency/load MySQL belum diuji dengan beberapa koneksi nyata; request
in-flight tidak dibatalkan. Cleanup fisik sesi, ownership transaksi dan reset/
invitation/admin delete belum masuk scope. Gap starter profil update persistence
dan checkbox remember pada login UI existing tidak ditutup dalam pekerjaan ini;
backend recall diuji terpisah. Theme, Old-docs dan artefak user tidak disentuh.
