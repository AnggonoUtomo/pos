# Tasks: Security Dependency

## Sebelum Mulai

- [x] AGENTS.md, docs/README.md, WORKFLOW.md dan QUALITY.md dibaca.
- [x] User menyetujui increment terpisah dan update kompatibel.
- [x] Target tiga package dan non-scope ditetapkan sebelum update.
- [x] Audit awal dan advisory resmi mengidentifikasi versi rentan/patched.
- [x] Investigator compatibility read-only dimulai; tanpa akses Old-docs.

## Increment 1: Scope Dan Boundary

- [x] Catat chain dependency, constraint dan entry point nyata.
  - Acceptance: versi rentan dibedakan dari exploitability endpoint aplikasi.
  - Verification: lock/tree/caller dan investigator independen.
- [x] Dokumentasikan rencana dan checklist.
  - Acceptance: scope minimal, risiko dan QA ditetapkan sebelum update.
  - Verification: git diff --check dan review link.

## Increment 2: Update Dan Regresi

- [ ] Update tiga dependency transitive secara terarah.
  - Acceptance: tidak mengganti stack/constraint langsung atau package lain.
  - Verification: diff lockfile, npm ls dan composer show --locked.
- [ ] Verifikasi trigger dan kontrol normal parser.
  - Acceptance: input malicious ditolak/diescape tanpa eksekusi shell/DoS.
  - Verification: regresi bounded, kontrol normal dan review kandidat read-only.
- [ ] Audit seluruh dependency.
  - Acceptance: npm full/production dan Composer tidak melaporkan advisory.
  - Verification: npm audit, npm audit --omit=dev, composer audit --locked.

## Increment 3: Integrasi Dan Delivery

- [ ] Ulang gate integrasi backend/frontend dan Stock browser.
  - Acceptance: full test, lint/typecheck/build dan workflow desktop/tablet PASS.
  - Verification: command aktual dan report QA disposable.
- [ ] Sinkronkan dokumen, selective commit dan push penutupan Stock.
  - Acceptance: status dan bukti jujur; artefak user tidak ikut commit.
  - Verification: diff/staging, commit, push dan remote ref.

## Sesudah Coding

- [ ] Semua gate relevan lulus dan risiko/proof gap dicatat.
- [ ] Tidak ada perubahan data/secret/tema/arsip/artefak user.

## Bukti Awal

Audit penutupan Stock: npm production menemukan shell-quote critical dan
source-map-js high; Composer menemukan dua advisory pada league/commonmark.
Versi target mengikuti advisory resmi yang ditautkan di README. Audit ini belum
menjadi bukti endpoint aplikasi dapat dieksploitasi.

Investigasi read-only: concurrently 9.1.2 menerima shell-quote ^1.8.1;
Vite 6.4.3/PostCSS 8.5.28 menerima source-map-js ^1.2.1; Laravel 12.69.2
menerima CommonMark ^2.8.1. Semua versi target sesuai constraint existing.
Shell dipakai tooling dev dengan command tetap; source map dipakai PostCSS;
CommonMark dipakai renderer email reset/verifikasi Laravel. Tidak ditemukan
entry point HTTP aplikasi yang menerima arbitrary Markdown/shell/source map.

Regresi sebelum update: shell-quote gagal karena TypeError tidak dilempar;
CommonMark HTML gagal karena tag script belum diescape. Tes paragraf/table
normal lulus. Semua input kecil, tanpa eksekusi shell atau delivery email.
