# WI-0005: Baseline Pengaturan Perusahaan dan Penomoran

Status: Draf

## Tujuan

Membuat company profile, setting pajak dasar, dan document numbering yang aman dari bentrok.

## Rujukan

- `docs/SPEC.md`
- `docs/ARCHITECTURE.md`

## Scope

Masuk scope:

- Company profile.
- Tax default setting.
- Document number format defaults.
- Sequence table dan locking strategy.
- Permission company/settings.

Di luar scope:

- Desain struk lengkap.
- Multi company.

## Checklist Sebelum Coding

- [ ] Format nomor final dibaca dari spec.
- [ ] Strategi lock sequence ditentukan.
- [ ] Dampak timezone/tanggal dokumen dipahami.

## Kriteria Penerimaan

- [ ] Nomor dokumen bisa digenerate per type dan tanggal.
- [ ] Nomor tidak reuse setelah void.
- [ ] Setting pajak default tersedia.
- [ ] Perubahan setting tercatat di activity log.

## Verifikasi

- [ ] Test sequence concurrent jika memungkinkan.
- [ ] `php artisan test --filter=DocumentNumber`
- [ ] `php artisan test --filter=CompanySettings`
