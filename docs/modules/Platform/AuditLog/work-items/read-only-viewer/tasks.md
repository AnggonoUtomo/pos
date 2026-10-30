# Tasks: Viewer Audit Log

## Sebelum Mulai

- [x] Baca AGENTS, arsitektur, struktur dan pola audit/package existing.
- [x] Scope dan permission tanpa auto-grant dijelaskan.
- [x] Dry-run generator diperiksa; tidak ada konflik struktur canonical.

## Increment 1: Backend Aman

- [x] Buat scaffold dan test gagal untuk permission/filter/redaksi.
  - Acceptance: test memakai database disposable; tidak memuat production cache.
  - Verification: focused RED.
- [x] Implementasikan port/read projection, request, controller dan access seeder.
  - Acceptance: hanya GET; whitelist payload; WIB/UTC akurat; global pagination.
  - Verification: focused feature dan route/status.

## Increment 2: UI Dan Panduan

- [x] Buat daftar/filter/detail lokal, state loading/empty/error dan shortcut.
  - Acceptance: tema existing, keyboard, readonly, tanpa raw JSON/secret.
  - Verification: typecheck/lint/build dan browser.
- [x] Tambahkan sidebar/palette, guide F1 serta Markdown operator.
  - Acceptance: guard permission konsisten, seluruh page memiliki guide.
  - Verification: page-guides, backend dan QA browser.

## Increment 3: Penutupan

- [x] Jalankan gate akhir dan tinjau screenshot desktop/tablet Light/Dark.
  - Acceptance: hasil/gap dicatat jujur, tidak mengulang QA yang tidak berubah.
  - Verification: command dan report.
- [x] Aktifkan route dan catalog permission secara additive.
  - Acceptance: data/role/grant existing identik, tidak ada demo/reset.
  - Verification: route cache, migration status dan fingerprint.
- [x] Review scope, commit terfokus, push dan cek remote.
