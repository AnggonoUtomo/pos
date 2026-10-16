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

- [x] Full backend/browser checkout dan history setelah patch.
  - Acceptance: auth/CSRF/validation/request/receipt tidak regresi.
  - Verification: full test, frontend gates existing, browser/report.
- [x] Dokumen/status/audit dan delivery remote cocok.
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
Pada save point patch, full regression/browser masih pending; hasil final di bawah.

Increment 3 QA: full suite423 tests/3316 assertions PASS. Browser history dan
checkout sesudah patch PASS (report pada work item invoice-history), native
MySQL checkout/history PASS. Typecheck/ESLint/build2715 tetap berlaku karena
tidak ada frontend berubah saat patch. npm audit penuh/production bersih,
composer audit --locked/validate --strict PASS. Schema/status dan aktivasi lokal
Nothing to migrate; fingerprint/grant/business data identik. Review independen
candidate terpisah selesai17 Oktober2026 tanpa bypass/regresi konkret. Reviewer
read-only mengonfirmasi shared boundary Request/HeaderBag/HeaderUtils dan
lock/vendor7.4.21 konsisten. Focused4/15 diulang; tambahan9 parsing controls dan
60 bounded cases termasuk JIT on/off lulus. Batas: bukan exhaustive malformed
input, live attack atau benchmark produksi. Tidak ada file diubah oleh reviewer.
Delivery main b6ebcd9 dikonfirmasi17 Oktober2026 melalui git ls-remote dan
divergence0/0; patch/test commit874b4f3 ikut range. Audit ulang17 Oktober2026:
composer audit --locked dan npm audit kembali bersih. Commit penutup checklist
disinkronkan setelah bukti delivery ini; tidak ada file user/arsip/secret ikut.
