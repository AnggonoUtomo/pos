# Tasks: Penyegaran Theme Dashboard

## Sebelum Mulai

- [x] AGENTS/README/QUALITY dan shell/token existing dipelajari.
- [x] Referensi light/dark dibuka di browser sampai konten selesai dimuat.
- [x] Scope user/kontrak desain dicatat; file LSP dan Old-docs tidak disentuh.
- [x] QA config/route cache dipisahkan dari cache produksi existing.

## Increment 1: Entry Aplikasi

- [x] Test RED root guest/login dan authenticated/dashboard.
  - Acceptance: tidak ada welcome, auth/starterkit/registrasi tetap sama.
  - Verification: focused redirect/auth test pada SQLite disposable.
- [x] Ubah route root dan hapus page welcome yang tidak digunakan.
- [x] Verifikasi save point tanpa memasukkan file user; commit setelah gate lulus.
  - RED2 gagal karena root200 welcome; GREEN redirect/auth6 test21 assertion.

## Increment 2: Tampilan Shell Dan Workspace

- [ ] Terapkan token light/dark dan aksen semantic berlapis.
- [ ] Rapikan sidebar/header/logo/command trigger serta login/dashboard.
  - Acceptance: semua menu dan guard tetap; tidak membuat data/kontrol palsu.
- [ ] Pertahankan tooltip, sidebar scroll, mode/accent persistence dan shortcut.
- [ ] Lint/typecheck/build dan browser desktop/tablet alur berubah lulus.
  - Verification: dashboard nyata, theme, collapse, no hover request, scroll,
    command palette, master dialog, POS; console/overflow/overlap diperiksa.
- [ ] Update route cache produksi dan smoke root/login/asset HTTPS.
- [ ] Catat hasil, review scope, commit dan push software terverifikasi.
