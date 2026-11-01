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

- [x] Input persen per level, preview biaya/sumber dan usulan, apply hanya ke form.
- [x] Invalidasi/abort/error, format Indonesia, shortcut dan panduan operator/F1.
- [x] Level custom, demo tanpa tepat tiga level dan menjaga harga existing.
- [x] Frontend gate dan commit save point.
  - Focused markup/seeder: 23 PASS / 71 assertions. Custom level tunggal dan
    empat nama level diuji; tidak menambah/rename level toko.
  - Typecheck, eslint resources/js dan lint terarah komponen terakhir PASS;
    build 2943 modules PASS; page-guides 30 page PASS.
  - Request lama di-abort dan reference request dinetralkan saat input berubah
    atau dialog ditutup. Timeout/error tidak mengubah form/harga tersimpan.

## Increment 3: QA Dan Delivery

- [x] Native MySQL serta browser desktop/tablet alur berubah.
  - activate-price-markup-local.php: native lookup/normalisasi satu barang
    pada data existing baca-saja PASS; bukan klaim seluruh skenario diuji MySQL.
  - MCP namespace tidak terdaftar; executable cached bin/stdio isolated dipakai
    tanpa install: login/Barang/DOM/screenshot PASS.
  - Chrome/Playwright existing, fixture SQLite/session terpisah: custom level,
    persen koma, preview tanpa mengubah input, apply/batal tanpa simpan, override
    manual/persist/reload, belum ada pembelian, F1/shortcut dan 403 PASS.
    Ledger/Finance fingerprint identik, console bersih; desktop1440/tablet1024.
    Screenshot diperiksa visual, modal memakai scroll vertikal existing.
    Bukti storage/framework/testing/mvp-browser-e614c6bf9a3e1ecb/visual-report.json.
- [x] Satu regresi penuh, typecheck/lint/build/page-guides; ulang hanya terdampak.
  - php artisan test --compact: 984 PASS / 7760 assertions, 128,11 detik.
  - Review mengganti lookup satuan per baris menjadi UnitLookup.findManyActive
    yang sudah tersedia; tidak mengubah contract/harga. Setelah itu hanya uji
    terdampak PriceMarkupTest/MarkupPriceTest: 19 PASS / 56 assertions, termasuk
    satuan nonaktif. Tidak mengulang full suite/browser yang tidak berubah.
- [x] Route cache dan fingerprint existing tetap, tanpa schema/seed/grant toko.
  - Native script memverifikasi seluruh row/kolom/schema/grant existing identik
    (kecuali cache/session), tanpa repricing/seed/migration. Satu route preview
    aktif/cache dan HTTPS login200 tanpa bypass TLS. Formatter script PASS.
- [x] Review scoped diff, checklist, commit/push dan verifikasi remote.
  - Save point backend e61853f, workspace 6f5554f, QA/aktivasi 9d31f59.
    Push main berhasil; ls-remote cocok 9d31f59 dan divergence0/0 saat diperiksa.
    Commit penutupan checklist dipush sesudahnya. Sisa perubahan Git hanya
    LSP user yang tidak disentuh atau dimasukkan commit.
