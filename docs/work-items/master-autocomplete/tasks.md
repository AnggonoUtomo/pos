# Tasks: Autocomplete Master Lintas Modul

## Sebelum Mulai

- [x] AGENTS, indeks, arsitektur/struktur dan form existing dibaca.
- [x] User meminta autocomplete semua pilihan sejenis, quick-create pembelian.
- [x] Batasi ke pilihan dinamis; enum/static dan transaksi posted dipertahankan.
- [x] LSP user, Old-docs dan data toko tidak disentuh.

## Increment 1: Autocomplete Bersama

- [x] Inventaris field/form dan pengecualian, shared model dan test RED/GREEN.
- [x] Komponen shared accessible, keyboard/IME, selected label, disabled/empty,
  scroll/portal dalam dialog, tidak submit form ketika memilih opsi.
- [x] Konversi pilihan dinamis dengan parser, review tipe dan business callbacks.
- [x] Frontend focused gate dan commit save point.

## Increment 2: Quick-create Pembelian

- [x] Supplier/barang dengan izin create masing-masing, reuse validasi/master.
- [x] Metadata berizin melalui contract publik dan backend gate; tanpa mutation.
- [x] Callback create JSON; tanpa navigate/reload parent.
- [x] Pilih master baru, unit reset sesuai aturan; qty/harga/baris lain utuh.
- [x] Cancel/error/403/duplikat/price-required/image dan backend gate.
- [x] Commit save point.

## Increment 3: QA Dan Delivery

- [x] Panduan F1/Markdown dan standar UI diperbarui.
- [x] QA keyboard/click/scroll/empty/disabled serta quick-create desktop/tablet.
- [x] Satu regresi penuh dan lint/typecheck/build/page-guides.
- [x] Route/cache dan fingerprint data/schema/grant existing identik.
- [x] Review scope, checklist dan commit terfokus.
- [ ] Push dan verifikasi remote.

## Bukti Verifikasi

- Inventaris: 44 deklarasi / 34 file, rincian dan pengecualian pada README.
- RED lookup metadata404; sesudah implementasi focused3 PASS,25 assertions.
- Regresi backend final: `php artisan test --compact`987 PASS,7786 assertions,
  311,53 detik. Backend tidak diubah sesudah gate ini selain format Pint.
- Pint file PHP terkait PASS. Metadata memakai public contract, bukan model
  privat; izin create asli tetap dipakai dan ledger tidak berubah.
- Node model/serializer PASS; typecheck dan lint resources/js PASS. Sesudah
  perubahan terakhir, typecheck dan lint file terdampak diulang PASS.
  Build final2947 modul PASS; page-guides30 halaman PASS.
- Browser RED menangkap Escape menutup parent, jarak teks label React dan master
  baru hilang dari pilihan setelah parent dibatalkan. Ketiganya diperbaiki.
  Percobaan scroll8 opsi ternyata belum memerlukan scroll; fixture diganti
  daftar110 satuan disposable untuk menguji batas100 dan scroll sungguhan.
- Chrome desktop1440x1000/tablet1024x768 final PASS, console/page errors kosong.
  Bukti ignored: `storage/framework/testing/mvp-browser-0e3cfc4f968290d2/visual-report.json`
  dan screenshot popup/master/filter. Hasil alur sebelumnya yang sama juga PASS
  pada `mvp-browser-1c044bec938ba3fc`; run final hanya menunggu filter stabil
  sebelum screenshot, bukan mengulang regresi backend.
- Cakupan browser: klik, panah/Enter tanpa submit, empty/Escape, scroll100 hasil,
  satuan dasar terkunci, supplier cancel/create, barang dengan harga lengkap,
  qty/harga/catatan/gudang utuh, cancel/reopen tanpa refresh, filter akun, form
  stok awal dan F1. Fingerprint pembelian/stok/FIFO/Finance identik; nol POST
  pembelian. Gambar tidak diunggah pada harness ini agar storage toko tetap utuh:
  validasi/storage gambar memakai ItemImageTest dalam gate penuh, serializer
  multipart array/gambar memakai Node. IME mengikuti library existing dan guard
  isComposing; tidak mengklaim UAT keyboard/scanner fisik.
- MCP cached/stdio timeout tools/call pada percobaan pertama, BLOCKED; Chrome/
  Playwright fallback PASS, bukan MCP PASS. Tidak install package atau runner OS.
- Aktivasi metadata native MySQL dan route cache PASS; fingerprint seluruh
  data/kolom/grant existing identik, tanpa migration/seed/transaksi.
- HTTPS `https://pos.test/login`200 dengan verifikasi sertifikat aktif.
- Review: callbacks/predicate duplikat/disabled dipertahankan, posted readonly,
  permission backend tetap authority; tidak ada dependency baru. CRUD master
  tetap endpoint/validasi/audit asli. Batas lookup awal1.000 existing dicatat,
  tidak diam-diam diubah menjadi pencarian server seluruh database.
- Save point:40b815d backend, d57c909 shared,4aa039a quick-create,00b1c45 Inventory,
  f27b73a Sales/Finance/filter. File LSP staged/untracked user tetap dikecualikan.
