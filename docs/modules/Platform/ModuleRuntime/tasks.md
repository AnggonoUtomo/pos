# Tasks: Platform/ModuleRuntime

## Baseline Saat Ini

- [x] Generator menggunakan path `app/Modules`.
- [x] Generator mendukung `--dry-run`.
- [x] Generator mendukung `--with-tests`.
- [x] Scaffold menyertakan route module.
- [x] Scaffold menyertakan database migrations folder.
- [x] Scaffold menyertakan demo seeder module.
- [ ] Capability belum menjadi source module fisik.
- [ ] Validator arsitektur module belum tersedia.

## Checklist Module Berikutnya

- [ ] Jika command dipindah ke module fisik, update provider dan autoload dengan
  work item khusus.
- [ ] Jika validator dibuat, pastikan tidak menggantikan code review layer.
- [ ] Jika scaffold berubah, update `docs/FOLDER-STRUCTURE.md` dan test.

## Hasil Verifikasi Terakhir Yang Diketahui

| Command | Hasil | Catatan |
| --- | --- | --- |
| `php artisan test --filter=MakeModuleCommandTest` | PASS terakhir sebelum baseline docs baru | Perlu rerun bila generator diubah |
