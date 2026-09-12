# WI-0005: Baseline Pengaturan Perusahaan dan Penomoran

Status: Draf

## Tujuan

Membuat company profile, setting pajak dasar, dan document numbering yang aman dari bentrok.

## Rujukan

- `docs/SPEC.md`
- `docs/ARCHITECTURE.md`
- `docs/QA-AUTOMATION.md`

## Modul Target

- Category: Platform
- Module: CompanySettings
- Namespace/path: `Modules/Platform/CompanySettings`
- Jenis pekerjaan: company profile, setting, dan penomoran dokumen

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

## Rencana Increment

| Increment | Fokus | Checklist Sebelum | Checklist Sesudah | QA automated | Status |
| --- | --- | --- | --- | --- | --- |
| INC-01 | Company profile dan setting dasar | - [ ] Field company phase 1 disetujui | - [ ] Setting dapat dibaca/diubah | feature test company settings | Draf |
| INC-02 | Numbering service | - [ ] Format nomor disetujui | - [ ] Nomor unik dan berurutan per tipe dokumen | unit/feature test numbering | Draf |
| INC-03 | Audit perubahan setting | - [ ] Aksi penting ditentukan | - [ ] Activity log tercatat | test activity log setting terkait | Draf |

## Kriteria Penerimaan

- [ ] Nomor dokumen bisa digenerate per type dan tanggal.
- [ ] Nomor tidak reuse setelah void.
- [ ] Setting pajak default tersedia.
- [ ] Perubahan setting tercatat di activity log.

## Verifikasi

- [ ] Test sequence concurrent jika memungkinkan.
- [ ] `php artisan test --filter=DocumentNumber`
- [ ] `php artisan test --filter=CompanySettings`
