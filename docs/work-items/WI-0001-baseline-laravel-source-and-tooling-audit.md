# WI-0001: Audit Baseline Source Laravel dan Tooling

Status: Draf

## Tujuan

Memeriksa source Laravel 12 Inertia React fresh setelah dicopy ke workspace, tanpa mengubah fitur domain.

## Rujukan

- `AGENTS.md`
- `docs/SPEC.md`
- `docs/ARCHITECTURE.md`
- `docs/DEFINITION-OF-DONE.md`

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
