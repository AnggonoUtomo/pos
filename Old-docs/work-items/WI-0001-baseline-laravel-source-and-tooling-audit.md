# WI-0001: Audit Baseline Source Laravel dan Tooling

Status: Selesai

## Tujuan

Memeriksa source Laravel 12 Inertia React fresh setelah dicopy ke workspace, tanpa mengubah fitur domain.

## Rujukan

- `AGENTS.md`
- `docs/SPEC.md`
- `docs/ARCHITECTURE.md`
- `docs/DEFINITION-OF-DONE.md`
- `docs/QA-AUTOMATION.md`

## Modul Target

- Category: Platform
- Module: Baseline
- Namespace/path: source root, `app`, `resources`, `routes`, `config`
- Jenis pekerjaan: audit source dan tooling

## Scope

Masuk scope:

- Cek versi Laravel, PHP, Node, package, starter kit, auth route.
- Cek struktur folder awal.
- Cek command install/build/test yang valid.
- Cek status git jika repository tersedia.
- Update command di `docs/SPEC.md` jika sudah terverifikasi.

Di luar scope:

- Scaffold modul.
- Install package baru.
- Membuat fitur POS.

## Checklist Sebelum Coding

- [x] Source Laravel sudah dicopy ke workspace.
- [x] Repository state dicek.
- [x] Tidak ada perubahan user yang belum dipahami.

## Rencana Increment

| Increment | Fokus | Checklist Sebelum | Checklist Sesudah | QA automated | Status |
| --- | --- | --- | --- | --- | --- |
| INC-01 | Audit stack dan dependency | - [x] Source tersedia<br>- [x] `.env` tidak ikut commit | - [x] Versi stack tercatat<br>- [x] Gap dependency dicatat | `php artisan --version`, `php -v`, `node -v`, `npm -v` | Selesai |
| INC-02 | Audit command baseline | - [x] Dependency siap | - [x] Command build/test tercatat<br>- [x] Hasil command dicatat | `npm run build`, `php artisan test` | Selesai |
| INC-03 | Audit starter kit dan route auth | - [x] Route auth dibaca | - [x] Auth starter kit teridentifikasi<br>- [x] Gap public register dicatat | `php artisan route:list --except-vendor` | Selesai |

## Kriteria Penerimaan

- [x] Versi stack tercatat.
- [x] Command dev/build/test diketahui.
- [x] Auth starter kit teridentifikasi.
- [x] Gap awal dicatat.
- [x] Belum ada perubahan fitur domain.

## Verifikasi

- [x] `php artisan --version`
- [x] `php -v`
- [x] `node -v`
- [x] `npm -v`
- [x] `composer show --direct`
- [x] `npm ls --depth=0`
- [x] `npm audit`
- [x] `npm run build`
- [x] `npm run lint`
- [x] `php artisan route:list --except-vendor`
- [x] `php artisan test`

## Catatan

Stack terverifikasi:

- Laravel Framework 12.69.2.
- PHP 8.4.16.
- Node.js v24.12.0.
- npm 11.6.4.
- Inertia Laravel 2.0.27.
- `@inertiajs/react` 2.3.28.
- React 19.0.0.
- Vite 6.4.3.
- Tailwind CSS 4.0.8.
- shadcn/ui tersedia melalui `components.json` dan komponen `resources/js/components/ui`.

Dependency/gap awal:

- Spatie Laravel Permission belum terpasang.
- Spatie Laravel Activitylog belum terpasang.
- Public registration masih aktif melalui route `GET|POST register` dan page `resources/js/pages/auth/register.tsx`.
- Test starter kit masih mengharapkan user bisa register.
- Forgot password masih aktif.
- `npm ls --depth=0` menampilkan unmet optional dependency Linux-only untuk Rollup/Tailwind/Lightning CSS; build Windows tetap PASS.

Bukti command:

```text
Command: php artisan --version
Hasil: PASS
Catatan: Laravel Framework 12.69.2.

Command: php -v
Hasil: PASS
Catatan: PHP 8.4.16.

Command: node -v
Hasil: PASS
Catatan: v24.12.0.

Command: npm -v
Hasil: PASS
Catatan: 11.6.4.

Command: composer show --direct
Hasil: PASS
Catatan: package utama Laravel/Inertia/Ziggy/Pint/PHPUnit terdeteksi.

Command: npm ls --depth=0
Hasil: PASS
Catatan: dependency frontend baseline terdeteksi; optional Linux packages tidak terpasang di Windows.

Command: npm audit
Hasil: PASS
Catatan: found 0 vulnerabilities.

Command: php artisan route:list --except-vendor
Hasil: PASS
Catatan: 23 route baseline tampil; register route masih aktif.

Command: npm run build
Hasil: PASS
Catatan: Vite build selesai.

Command: npm run lint
Hasil: PASS
Catatan: eslint --fix selesai tanpa perubahan file.

Command: php artisan test
Hasil: PASS
Catatan: 29 tests, 76 assertions.
```
