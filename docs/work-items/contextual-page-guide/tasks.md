# Tasks: Panduan Kontekstual

## Sebelum Mulai

- [x] Baca AGENTS, README, arsitektur/struktur dan panduan operator relevan.
- [x] Periksa Git, layout admin/POS, page Inertia dan komponen Sheet.
- [x] Batasi scope UI/read-only, metadata lokal, tanpa dependency/migration.

## Increment 1: Konten Dan Resolver

- [x] Tulis test coverage page dan validasi konten sebelum implementasi.
  - Acceptance: seluruh page authenticated terpetakan, tidak mengambil auth
    publik, isi tiap page memiliki judul/tujuan/langkah/peringatan yang valid.
  - Verification: Node/TypeScript existing, RED lalu GREEN.
- [x] Tambah guide lokal untuk master, transaksi, laporan, kas/shift dan akun.
  - Acceptance: sesuai workflow existing, bukan fitur belum tersedia.
  - Verification: review terhadap Markdown/source dan coverage test.
- [x] Resolver lazy dan fallback aman; commit setelah gate terfokus lulus.

Hasil: test awal RED karena `dashboard.guide.ts` belum ada; setelah konten
ditambahkan, `node scripts/qa/page-guides.mjs` PASS untuk 26 page. Test juga
memeriksa mapping Inertia, lazy load, fallback dan propagasi kegagalan loader.

## Increment 2: Sheet Dan Delivery

- [x] Tambahkan tombol di layout admin dan toolbar POS serta F1.
  - Acceptance: sheet kanan, tooltip, loading/error/retry, fokus/Escape,
    pergantian halaman, scrollbar tipis, reduced motion.
  - Verification: lint/type/build dan browser desktop/tablet.
- [x] Verifikasi form/keranjang tetap utuh, tidak reload/mutasi atau menutupi
  kontrol pembayaran; tidak muncul pada login.
- [x] Update aturan panduan page baru/dokumentasi, checklist dan commit.
- [x] Push paket selesai dan verifikasi remote tanpa membawa file LSP.

Hasil: typecheck, ESLint, build, coverage/resolver dan checkpoint browser
desktop/tablet lulus. Detail command, bukti dan fallback MCP pada README.
Review correctness, keterbacaan, boundary, keamanan dan lazy loading selesai;
tidak ditemukan blocker tersisa. Panduan tidak menyimpan/mengirim data bisnis.
Commit konten `8a6096f` dan UI/QA `f1f1c14` sudah dipush ke origin/main;
remote diperiksa dan divergence 0/0. File LSP user tetap di working tree/index,
tidak masuk commit pekerjaan.
