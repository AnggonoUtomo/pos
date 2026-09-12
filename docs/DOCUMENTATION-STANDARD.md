# Documentation Standard

## Bahasa

- Dokumentasi proyek ditulis dalam Bahasa Indonesia.
- Istilah teknis umum boleh memakai bahasa Inggris.
- Nama kode, enum, permission, route, class, dan method mengikuti konvensi teknis bahasa Inggris.

## Lokasi Dokumen

```text
AGENTS.md                         Instruksi kerja agent/pengembang
docs/SPEC.md                      Spesifikasi teknis dan domain
docs/PRD.md                       Product requirements
docs/ARCHITECTURE.md              Arsitektur dan Boundary modul
docs/MODULE-COMMUNICATION.md      Aturan komunikasi lintas modul
docs/DEFINITION-OF-DONE.md        Checklist standar selesai
docs/modules/                     Dokumentasi per module
docs/adr/                         Catatan Keputusan Arsitektur
docs/templates/                   Template dokumen
docs/work-items/                  Work-item dan registry
docs/tasks/                       Plan/todo teknis sebelum build
```

## ADR

- ADR disimpan di `docs/adr/`.
- Format nama: `ADR-0001-short-Judul.md`.
- Status: `Diusulkan`, `Diterima`, `Digantikan`, atau `Tidak digunakan lagi`.
- ADR lama tidak dihapus. Jika keputusan berubah, buat ADR baru yang supersedes ADR lama.

## Naming

- Dokumentasi dan UI label memakai Bahasa Indonesia.
- Tabel, kolom, class, enum, DTO, event, route name, permission key, contract, dan namespace memakai English technical naming.
- Jangan mencampur nama tabel Bahasa Indonesia dan English dalam schema.

## Work-Item

- Work-item disimpan di `docs/work-items/`.
- Format nama: `WI-0001-short-Judul.md`.
- Semua work-item dicatat di `docs/work-items/WORK-ITEM-REGISTRY.md`.
- Work-item harus punya kriteria penerimaan dan verification checklist sebelum coding.

## Dokumentasi Module

Dokumentasi module disimpan di `docs/modules/{Category}/{Module}/`.

Setiap module yang akan dikerjakan wajib memiliki:

- `README.md`: identitas, tujuan, boundary, public boundary, data, permission, audit, operasi, dan verifikasi utama.
- `specification.md`: scope, non-scope, contract, data, authorization, audit, dependency, acceptance criteria, dan risiko.
- `plan.md`: urutan increment, dependency, acceptance, verification, batas berhenti, dan rollback.
- `tasks.md`: checklist pekerjaan yang diisi sebelum dan sesudah coding.

Work-item tetap menjadi unit eksekusi. Dokumen module menjadi konteks produk/arsitektur yang lebih tahan lama.

## Bukti

Setiap laporan selesai harus menyebut:

- File utama yang diubah.
- Command yang dijalankan.
- Hasil command.
- Risiko atau gap yang masih ada.

Jangan menulis "sudah aman" tanpa bukti command atau inspeksi.
