# WI-0003: Scaffold Folder dan Loader Modul

Status: Draf

## Tujuan

Menyiapkan fondasi folder modul dan mekanisme loading route/migration/provider tanpa membuat fitur domain besar.

## Rujukan

- `docs/ARCHITECTURE.md`
- `docs/adr/ADR-0001-modular-monolith-ddd-lite-hexagonal.md`

## Scope

Masuk scope:

- Struktur `Modules/{Category}/{Module}`.
- Module service provider atau loader sesuai pola Laravel yang disepakati.
- Loading route modul.
- Loading migration modul.
- `ServiceProvider.php` sebagai composition root module.
- Dokumentasi cara membuat modul baru.

Di luar scope:

- Implementasi bisnis Catalog, Sales, Purchasing, dan Inventory.
- Schema transaksi.

## Checklist Sebelum Coding

- [ ] WI-0001 dan WI-0002 selesai.
- [ ] Pola autoload composer dipahami.
- [ ] Risiko route/migration discovery dicatat.

## Kriteria Penerimaan

- [ ] Folder modul awal tersedia.
- [ ] Route modul dapat diload.
- [ ] Migration modul dapat diload.
- [ ] Autoload namespace bekerja.
- [ ] Binding contract-adapter, route, migration, policy/listener diarahkan melalui ServiceProvider module.
- [ ] ServiceProvider tidak berisi business logic.
- [ ] Dokumentasi module convention tersedia.

## Verifikasi

- [ ] `composer dump-autoload`
- [ ] `php artisan route:list`
- [ ] `php artisan migrate:status`
- [ ] `php artisan test` jika tersedia
