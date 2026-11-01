# Tasks: Bantuan Harga Persen Dan Level Dinamis

## Sebelum Mulai

- [x] Baca AGENTS, indeks, arsitektur, struktur, Items/Parties dan alur pembelian.
- [x] User menyetujui markup persen, review manual, acuan beli diterima terakhir
  lintas gudang; belum ada pembelian tetap harga manual.
- [x] Pastikan matriks existing sudah membaca master level, tanpa hapus data.
- [x] Pertahankan LSP, Old-docs, FIFO dan default pelanggan POS existing.

## Increment 1: Backend

- [x] Public lookup biaya diterima dan DTO snapshot tanpa data supplier/Finance.
  - Acceptance: draft/terhapus/tanpa receipt tidak menjadi acuan, urutan deterministik.
- [x] Aturan markup exact dan preview berizin/read-only, level/satuan dinamis.
  - Verification: focused RED/GREEN, nol/pecahan/overflow/rational/no-cost,
    permission, item/master nonaktif, fingerprint/audit tidak berubah.
- [x] Commit save point setelah gate terfokus lulus.
  - RED: endpoint 404 sebelum implementasi. GREEN PriceMarkupTest/MarkupPriceTest
    19 PASS / 55 assertions; formatter terarah PASS. Domain Parties hanya diakses
    Items melalui method publik ItemPriceCatalog.markup, bukan import privat.

## Increment 2: UI Dan Dokumentasi

- [ ] Input persen per level, preview biaya/sumber dan usulan, apply hanya ke form.
- [ ] Invalidasi/abort/error, format Indonesia, shortcut dan panduan operator/F1.
- [ ] Level custom, demo tanpa tepat tiga level dan menjaga harga existing.
- [ ] Frontend gate dan commit save point.

## Increment 3: QA Dan Delivery

- [ ] Native MySQL serta browser desktop/tablet alur berubah.
- [ ] Satu regresi penuh, typecheck/lint/build/page-guides; ulang hanya terdampak.
- [ ] Route cache dan fingerprint existing tetap, tanpa schema/seed/grant toko.
- [ ] Review scoped diff, checklist, commit/push dan verifikasi remote.
