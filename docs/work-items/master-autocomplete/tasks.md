# Tasks: Autocomplete Master Lintas Modul

## Sebelum Mulai

- [x] AGENTS, indeks, arsitektur/struktur dan form existing dibaca.
- [x] User meminta autocomplete semua pilihan sejenis, quick-create pembelian.
- [x] Batasi ke pilihan dinamis; enum/static dan transaksi posted dipertahankan.
- [x] LSP user, Old-docs dan data toko tidak disentuh.

## Increment 1: Autocomplete Bersama

- [x] Inventaris field/form dan pengecualian, shared model dan test RED/GREEN.
- [ ] Komponen shared accessible, keyboard/IME, selected label, disabled/empty,
  scroll/portal dalam dialog, tidak submit form ketika memilih opsi.
- [ ] Konversi pilihan dinamis dengan parser, review tipe dan business callbacks.
- [ ] Frontend focused gate dan commit save point.

## Increment 2: Quick-create Pembelian

- [ ] Supplier/barang dengan izin create masing-masing, reuse validasi/master.
- [x] Metadata berizin melalui contract publik dan backend gate; tanpa mutation.
- [ ] Callback create JSON; tanpa navigate/reload parent.
- [ ] Pilih master baru, unit reset sesuai aturan; qty/harga/baris lain utuh.
- [ ] Cancel/error/403/duplikat/price-required/image dan backend gate.
- [ ] Commit save point.

## Increment 3: QA Dan Delivery

- [ ] Panduan F1/Markdown dan standar UI diperbarui.
- [ ] QA keyboard/click/scroll/empty/disabled serta quick-create desktop/tablet.
- [ ] Satu regresi penuh dan lint/typecheck/build/page-guides.
- [ ] Route/cache dan fingerprint data/schema/grant existing identik.
- [ ] Review scope, checklist, commit/push dan verifikasi remote.

## Bukti Sementara

- Inventaris: 44 deklarasi / 34 file, rincian dan pengecualian pada README.
- RED lookup metadata404; sesudah implementasi focused3 PASS,25 assertions.
- Regresi backend final: `php artisan test --compact`987 PASS,7786 assertions,
  311,53 detik. Backend tidak diubah sesudah gate ini selain format Pint.
- Pint file PHP terkait PASS. Metadata memakai public contract, bukan model
  privat; izin create asli tetap dipakai dan ledger tidak berubah.
- Node model/serializer PASS; typecheck dan lint awal PASS. Build2947 modul PASS.
- Browser pertama menangkap Escape menutup parent; shared handler diperbaiki,
  verifikasi alur ulang sedang berjalan. MCP cached/stdio timeout tools/call,
  BLOCKED; Chrome/Playwright menjadi fallback, bukan mengklaim MCP PASS.
- Aktivasi metadata native MySQL dan route cache PASS; fingerprint seluruh
  data/kolom/grant existing identik, tanpa migration/seed/transaksi.
