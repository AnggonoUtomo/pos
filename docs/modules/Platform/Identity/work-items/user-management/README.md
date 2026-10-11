# Work Item: Manajemen Pengguna

## Status

In Progress

## Owner Dan Kondisi Awal

Platform/Identity; backend app/Modules/Platform/Identity dan frontend
resources/js/pages/platform/identity. Permission users.create sudah ada pada
seeder awal, tetapi belum memiliki endpoint/action/UI. User menyetujui tambah
akun, edit nama/email, assignment role dan nonaktifkan/aktifkan akun.

## Scope Dan Acceptance

- [ ] Admin membuat akun dengan password awal dan konfirmasi; password hashed,
  tidak masuk audit/props/history form. Registrasi publik tetap tidak tersedia.
- [ ] Edit nama/email tanpa mengubah password atau direct grants/guard lain.
- [ ] Assignment tetap memakai use case existing dengan anti-escalation.
- [ ] Status nonaktif menolak login dan request sesi lama, termasuk sesudah
  reaktivasi. Minimal satu super-system aktif; self-deactivation dilarang.
- [ ] Mutation audited dan atomic; audit gagal membatalkan perubahan.
- [ ] UI tab pengguna, badge status, dialog, shortcut, Sonner, desktop/tablet.

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
| 3 | Ready | Status, sesi dan last-super aktif | Focused auth/rollback, migration |
| 4 | Ready | UI, QA dan dokumentasi final | Lint/typecheck/build, browser, full tests |

## Handoff

Belum selesai. Hasil aktual dicatat pada tasks.md sesudah pemeriksaan.
