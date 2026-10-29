# Panduan Kontekstual Dalam Halaman

Status: implementasi. Cakupan UI-only lintas module, tanpa API/schema/data baru.

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
