# WI-XXXX: Judul

Status: Draf

## Tujuan

Apa yang ingin dicapai work-item ini.

## Rujukan

- `AGENTS.md`
- `docs/SPEC.md`
- `docs/QA-AUTOMATION.md`

## Modul Target

- Category:
- Module:
- Namespace/path:
- Jenis pekerjaan:

## Scope

Masuk scope:

- ...

Di luar scope:

- ...

## Checklist Sebelum Coding

- [ ] Baca dokumen rujukan.
- [ ] kriteria penerimaan jelas.
- [ ] rencana verifikasi jelas.
- [ ] Modul target sudah diinformasikan ke user.
- [ ] Rencana increment sudah ditulis.
- [ ] QA automated per increment sudah ditentukan.
- [ ] Chrome DevTools MCP QA direncanakan jika menyentuh UI/browser.
- [ ] Kebutuhan demo seeder module sudah diputuskan.
- [ ] Risiko schema/route/permission/FIFO/payment/tax/audit dipertimbangkan.
- [ ] Status git dicek jika repository tersedia.

## Rencana Increment

| Increment | Fokus | Checklist Sebelum | Checklist Sesudah | QA automated | Status |
| --- | --- | --- | --- | --- | --- |
| INC-01 | ... | - [ ] ... | - [ ] ... | `php artisan test --filter=...` | Draf |
| INC-02 | ... | - [ ] ... | - [ ] ... | `npm run build` jika UI berubah | Draf |
| INC-DEMO | Demo seeder bila relevan | - [ ] Relasi data demo jelas | - [ ] Seeder berjalan atau alasan skip dicatat | `php artisan db:seed --class=...` jika diisi | Draf |

## Kriteria Penerimaan

- [ ] ...
- [ ] ...

## Verifikasi

- [ ] Command:
- [ ] Chrome DevTools MCP:
- [ ] Pemeriksaan manual:

## Checklist Sesudah Coding

- [ ] Semua increment yang dikerjakan sudah diperbarui statusnya.
- [ ] QA automated relevan sudah dijalankan.
- [ ] Chrome DevTools MCP QA dijalankan untuk UI/browser atau dicatat `SKIPPED/BLOCKED`.
- [ ] Demo seeder module diverifikasi jika relevan, atau alasan skip dicatat.
- [ ] Bukti command dicatat.
- [ ] Gap atau command yang belum bisa dijalankan dicatat.
- [ ] Tidak ada perubahan di luar scope tanpa catatan.

## Perkiraan File Yang Tersentuh

- ...

## Catatan Implementasi

Catatan saat coding.

## Bukti

Isi setelah verifikasi:

```text
Command:
Hasil:
Catatan:

Chrome DevTools MCP:
Status:
Bukti:
Fallback:
```
