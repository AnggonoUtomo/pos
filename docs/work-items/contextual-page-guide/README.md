# Panduan Kontekstual Dalam Halaman

Status: selesai dan terverifikasi. Cakupan UI-only lintas module, tanpa
API/schema/data baru.

## Scope

- Setiap halaman operasional/admin yang login memiliki panduan kontekstual,
  termasuk dashboard, pengaturan akun dan POS fullscreen.
- Tombol buku mengambang di admin membuka shadcn Sheet dari kanan. POS memakai
  tombol di toolbar agar pembayaran tidak tertutup. Tooltip dan F1 tersedia.
- Isi Bahasa Indonesia sederhana: tujuan, persiapan, urutan kerja, hasil dan
  peringatan/alur lintas module yang relevan. Berdasarkan docs/user-guide aktif,
  termasuk Buku Kas dan Shift Kasir; bukan fitur backlog.
- Metadata terstruktur di `{page}.guide.ts` dekat page; loader Vite lazy glob
  mengambil hanya panduan halaman saat dibuka, tanpa parser Markdown/HTML baru.
- Komponen shared menangani tampilan, loading/error/retry, fokus, Escape,
  scroll terpisah, reduced motion dan penyesuaian desktop/tablet.
- Panduan tidak menjalankan operasi, mengubah grant atau menampilkan data akun/
  transaksi. Izin operasi tetap backend. Login/public auth tidak diberi panduan
  modul; halaman login tidak diubah.
- Tidak memasang dependency, tidak menyentuh Old-docs/LSP maupun data produksi.

## Pemeliharaan

Panduan ringkas in-app dan Markdown operator harus diperbarui bersama jika
alur berubah. Ini ringkasan terstruktur, bukan menyalin semua PDF ke bundle.
Fallback aman menjelaskan operasi umum untuk halaman yang belum terpetakan;
coverage check mewajibkan panduan untuk page authenticated existing.

## Verifikasi

- Coverage/resolver: `node scripts/qa/page-guides.mjs` PASS, 26 halaman.
- Typecheck: `npx tsc --noEmit` PASS. ESLint resources/js dan kedua skrip QA
  PASS. Build produksi PASS, 2923 module. `git diff --check` PASS.
- Chrome DevTools MCP: BLOCKED, discovery tool tidak tersedia pada sesi ini.
  Fallback Chrome/Playwright existing, tanpa instalasi, database/cache SQLite
  disposable. Bukan data toko.
- Admin: F1/Escape/fokus, lazy chunk, navigasi Inertia, scroll panduan dan form
  belum disimpan PASS. Checkpoint pada `mvp-browser-2f03f02df69e7f73`.
- POS Klasik/Visual: keranjang barcode tetap, toolbar/tablet dan reduced motion
  PASS pada `mvp-browser-2c735e20fb2384ae`. Animasi awal yang mengabaikan reduced
  motion sudah diperbaiki. Typo URL Buku Kas di harness juga sudah dibetulkan.
- Buku Kas dark/tablet, kegagalan chunk dan retry tanpa reload, console bersih
  serta fingerprint ledger unchanged PASS pada `mvp-browser-81883b3c148f4cef`.
  Artefak berada di storage/framework/testing (ignored). Hasil checkpoint lulus
  dipakai kembali; hanya pemeriksaan terdampak yang diulang.
- Backend suite/migration tidak dijalankan karena tidak ada perubahan backend,
  schema, dependency atau aturan transaksi. PDF tidak dibangun ulang. UAT
  operator/printer/tablet jaringan yang pending tetap di luar scope.
