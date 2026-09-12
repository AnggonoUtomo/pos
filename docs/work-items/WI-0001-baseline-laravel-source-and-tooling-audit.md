# WI-0001: Audit Baseline Source Laravel dan Tooling

Status: Draf

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

- [ ] Source Laravel sudah dicopy ke workspace.
- [ ] Repository state dicek.
- [ ] Tidak ada perubahan user yang belum dipahami.

## Rencana Increment

| Increment | Fokus | Checklist Sebelum | Checklist Sesudah | QA automated | Status |
| --- | --- | --- | --- | --- | --- |
| INC-01 | Audit stack dan dependency | - [ ] Source tersedia<br>- [ ] `.env` tidak ikut commit | - [ ] Versi stack tercatat<br>- [ ] Gap dependency dicatat | `php artisan --version`, `php -v`, `node -v`, `npm -v` | Draf |
| INC-02 | Audit command baseline | - [ ] Dependency siap atau alasan belum siap dicatat | - [ ] Command build/test tercatat<br>- [ ] Hasil command dicatat | `npm run build`, `php artisan test` jika environment siap | Draf |
| INC-03 | Audit starter kit dan route auth | - [ ] Route auth dibaca | - [ ] Auth starter kit teridentifikasi<br>- [ ] Gap public register dicatat | `php artisan route:list` | Draf |

## Kriteria Penerimaan

- [ ] Versi stack tercatat.
- [ ] Command dev/build/test diketahui.
- [ ] Auth starter kit teridentifikasi.
- [ ] Gap awal dicatat.
- [ ] Belum ada perubahan fitur domain.

## Verifikasi

- [ ] `php artisan --version`
- [ ] `php -v`
- [ ] `node -v`
- [ ] `npm -v`
- [ ] `composer show`
- [ ] `npm run build` jika dependency siap
- [ ] `php artisan test` jika test environment siap

## Catatan

Isi setelah audit dilakukan.
