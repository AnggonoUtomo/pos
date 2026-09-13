# Tasks: Platform/Identity

## Baseline Saat Ini

- [x] Module source berada di `app/Modules/Platform/Identity`.
- [x] Route module berada di `Routes/web.php`.
- [x] Seeder module berada di `Database/Seeders`.
- [x] Shared auth memakai `superSystem`.
- [x] Hook permission frontend tersedia.
- [x] Focused identity test tersedia.
- [ ] UI role/permission/user access belum final.

## Checklist Module Berikutnya

- [ ] Saat menambah permission baru, update seeder dan test.
- [ ] Saat menambah UI CRUD/sync, gunakan Sonner toast.
- [ ] Saat menambah controller, gunakan `HasMiddleware` dengan `can:*` per
  action atau policy eksplisit.
- [ ] Saat menambah mutation akses, catat activity log.
- [ ] Saat menambah public boundary, dokumentasikan contract/DTOs/event.

## Hasil Verifikasi Terakhir Yang Diketahui

| Command | Hasil | Catatan |
| --- | --- | --- |
| `php artisan test --filter=Identity` | PASS | Focused identity tests |
| `php artisan test --filter=PermissionBaselineTest` | PASS | Permission baseline |
| `npm run build` | PASS | Hook frontend ter-build |
| `npm run lint` | PASS | Lint frontend |
