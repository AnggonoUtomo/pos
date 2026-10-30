# Tasks: Area Pencarian Dan Filter

## Sebelum Mulai

- [x] Baca AGENTS dan konteks UI/dokumentasi relevan.
- [x] Inventarisasi area filter dan token existing.
- [x] Batasi pekerjaan ke presentasi, tanpa perubahan backend/.env.

## Increment 1: Tampilan Lintas Module

- [x] Tambahkan band filter, bidang input dan state fokus berbasis tema.
  - Acceptance: tint halus, aksen jelas, tanpa design system baru.
  - Verification: typecheck, lint dan build.
- [x] Terapkan ke pencarian daftar dan POS/dialog yang relevan.
  - Acceptance: handler, shortcut, filter dan pagination tidak berubah.
  - Verification: review diff dan cakupan consumer.

## Increment 2: QA Dan Penutupan

- [x] QA browser terfokus.
  - Acceptance: pencarian/filter/pagination tetap bekerja, tidak overflow atau
    console error pada desktop/tablet representatif Light/Dark.
  - Verification: DevTools MCP existing atau fallback dengan alasan tercatat.
- [x] Lengkapi bukti, diff check dan commit terfokus.
  - Acceptance: file user, Old-docs, data toko dan .env tidak tersentuh.
  - Verification: scope commit dan status git.
- [x] Push setelah selesai dan verifikasi sinkronisasi remote.
