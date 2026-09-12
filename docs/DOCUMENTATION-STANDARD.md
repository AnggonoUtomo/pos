# Documentation Standard

## Bahasa

- Dokumentasi proyek ditulis dalam Bahasa InSelesaisia.
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
docs/adr/                         Catatan Keputusan Arsitektur
docs/templates/                   Template dokumen
docs/work-items/                       Work-item dan registry
docs/tasks/                            Plan/todo teknis sebelum build
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

## Bukti

Setiap laporan selesai harus menyebut:

- File utama yang diubah.
- Command yang dijalankan.
- Hasil command.
- Risiko atau gap yang masih ada.

Jangan menulis "sudah aman" tanpa bukti command atau inspeksi.
