# WI-0002: Review Baseline Dokumentasi dan Arsitektur

Status: Draf

## Tujuan

Menyesuaikan dokumen baseline dengan source Laravel yang sebenarnya setelah audit awal.

## Rujukan

- `docs/SPEC.md`
- `docs/PRD.md`
- `docs/ARCHITECTURE.md`
- `docs/adr/`
- `docs/work-items/WI-0001-baseline-laravel-source-and-tooling-audit.md`
- `docs/QA-AUTOMATION.md`

## Modul Target

- Category: Platform
- Module: Documentation
- Namespace/path: `docs`, `AGENTS.md`
- Jenis pekerjaan: review dan sinkronisasi dokumentasi baseline

## Scope

Masuk scope:

- Update command yang sudah terverifikasi.
- Update struktur frontend/backend sesuai source.
- Tandai open questions yang sudah terjawab.
- Tambahkan ADR jika ada keputusan baru.

Di luar scope:

- Implementasi modul.
- Perubahan schema.

## Checklist Sebelum Coding

- [ ] WI-0001 selesai atau minimal menghasilkan audit yang cukup.
- [ ] Konflik dokumen vs source sudah dicatat.

## Rencana Increment

| Increment | Fokus | Checklist Sebelum | Checklist Sesudah | QA automated | Status |
| --- | --- | --- | --- | --- | --- |
| INC-01 | Review dokumen baseline | - [ ] `SPEC`, `PRD`, `ARCHITECTURE`, ADR dibaca | - [ ] Gap dan konflik dicatat | Review manual dokumen | Draf |
| INC-02 | Review work-item dan template | - [ ] Registry work-item dibaca | - [ ] Checklist dan acceptance criteria sinkron | `git diff --check` | Draf |
| INC-03 | Review aturan QA | - [ ] `docs/QA-AUTOMATION.md` dibaca | - [ ] QA gate tercermin di DoD/work-item | `git diff --check` | Draf |

## Kriteria Penerimaan

- [ ] Dokumen baseline tidak bertentangan dengan source awal.
- [ ] Command di spec realistis.
- [ ] ADR awal tetap konsisten atau disupersede dengan jelas.

## Verifikasi

- [ ] Review manual dokumen.
- [ ] Link/path dokumen valid.
- [ ] Tidak ada klaim command pass tanpa command dijalankan.
