# Dokumentasi POS Modular ERP-Lite

Dokumentasi ini adalah acuan aktif project `C:\laragon\www\pos`. Folder
`Old-docs/` hanya arsip dan tidak menjadi sumber arsitektur harian, kecuali
garis besar PRD dan ADR lama yang sudah dipindahkan ke dokumen aktif ini.

## Mulai Di Sini

1. Baca [Project](PROJECT.md) untuk tujuan, scope, dan batas produk.
2. Baca [Arsitektur](ARCHITECTURE.md) untuk boundary module dan dependency.
3. Baca [Struktur Folder](FOLDER-STRUCTURE.md) sebelum mengubah struktur.
4. Baca [Daftar Module](MODULES.md) untuk ownership dan status module.
5. Baca [Workflow](WORKFLOW.md) sebelum memulai pekerjaan.
6. Pilih pemeriksaan dari [Quality](QUALITY.md) sesuai risiko.
7. Baca [Keputusan](DECISIONS.md) saat menyentuh keputusan aktif.
8. Baca [API](API.md) saat mengubah endpoint publik atau contract integrasi.

Template tersedia di [`templates/`](templates/README.md). Konvensi dokumentasi
module dan work item berada di [`modules/`](modules/README.md) dan
[`work-items/`](work-items/README.md).

## Stack

- Backend: Laravel 12, PHP 8.4.
- Frontend: Inertia React, React 19, Vite, Tailwind CSS 4, shadcn/ui,
  lucide-react.
- Database/cache/queue: MySQL untuk target utama; konfigurasi cache, session,
  dan queue mengikuti Laravel starter kit sampai diputuskan lebih lanjut.
- Auth dan authorization: Laravel starter kit, Spatie Laravel Permission.
- Audit trail: Spatie Laravel Activitylog.
- Identifier: ULID untuk primary key tabel utama.

## Prinsip Dokumentasi

- Dokumentasi proyek memakai Bahasa Indonesia.
- Technical naming pada kode dan database tetap English.
- Dokumen aktif harus menjelaskan contract publik, perubahan behavior, langkah
  operasi, QA, dan keputusan yang sulit dibalik.
- Jangan menyimpan execution log panjang untuk perubahan kecil; cukup command,
  hasil, dan bukti yang relevan pada work item.
- Jika dokumen dan kode bertentangan, catat gap pada work item dan minta arahan
  sebelum mengubah keputusan besar.
