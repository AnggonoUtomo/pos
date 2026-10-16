# Tasks: Patch HTTP Foundation

## Sebelum Mulai

- [x] Persetujuan user untuk patch kompatibel terpisah diterima.
- [x] AGENTS, dokumen aktif dan source/caller package dibaca.
- [x] Advisory primer dan follow-up compatibility diperiksa.
- [x] Dry-run menunjukkan satu update7.4.18 ke7.4.21.
- [x] Batas scope dan QA/redaksi/exclusion ditetapkan.

## Increment 1: Reproduksi

- [x] Tambahkan test trigger Cache-Control dan legitimate controls.
  - Acceptance: input bounded dengan backtrack budget rendah menunjukkan
    directive hilang sebelum patch; separator whitespace juga diuji.
  - Verification: focused PHPUnit RED, tidak mengirim request ke server nyata.

## Increment 2: Patch

- [x] Update package tunggal dalam seri7.4 dan review lockfile.
  - Acceptance: tidak ada dependency/major baru atau patch vendor manual.
  - Verification: focused GREEN, composer audit/validate dan diff.
- [x] Commit patch/test setelah verifikasi relevan.

## Increment 3: Penutupan

- [ ] Full backend/browser checkout dan history setelah patch.
  - Acceptance: auth/CSRF/validation/request/receipt tidak regresi.
  - Verification: full test, frontend gates existing, browser/report.
- [ ] Dokumen/status/audit dan delivery remote cocok.
  - Verification: scoped commit, push/ls-remote/divergence0/0.

## Bukti

RED pada7.4.18: tiga test whitespace space/tab/mixed gagal karena max-age
menjadi string kosong, legitimate control PASS. Test control awal salah shape
single separator nested; dikoreksi ke kontrak array flat sebelum patch, lalu
RED hanya trigger keamanan. Tidak mengubah ekspektasi keamanan setelah patch.

composer update symfony/http-foundation:7.4.* --no-interaction PASS, hanya satu
lock package7.4.18 ke7.4.21. Tidak mengubah composer.json atau vendor manual.
Focused PHPUnit GREEN4 tests/15 assertions; Pint PASS, composer validate --strict
PASS, composer audit --locked bersih. npm audit --omit=dev juga bersih.
Review ulang shared boundary HeaderBag/Request/HeaderUtils memastikan token
tidak lagi mengonsumsi whitespace terminal/backtrack, separator whitespace
tetap dikecualikan dan quoted directive kompatibel. Bukan benchmark DoS server.
Full regression/browser sesudah patch masih pending pada Increment 3.
