# Rencana Increment Fondasi Master Finance

1. Schema/DTO/enum/action/repository dan seeders. RED-GREEN test mutation,
   compatibility, audit privacy/no-op/rollback dan demo; commit setelah PASS.
2. HTTP CRUD, authorization, filter/choices dan validation contract. Focused
   HTTP/route/migration tests dan commit.
3. UI dua halaman, form, tabel/navigation, permission UX/Sonner/shortcut/palette.
   Lint/TS/build dan browser desktop/tablet, commit setelah PASS.
4. Full QA, MySQL dua koneksi current read/FK, migration/demo additive lokal,
   fingerprint, docs/checklist. Push setelah pekerjaan selesai dan cocokkan HEAD.

Migration/module scaffold tetap canonical; tidak ada migration global. Demo
module hanya dua master, bukan record pembayaran. Tidak menjalankan Identity
seeder atau migrate:fresh pada database pengguna.
