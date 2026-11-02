# Tasks: Kategori Dan Merek Barang

## Sebelum Mulai

- [x] AGENTS, README, arsitektur/struktur dan dokumen/source Items dibaca.
- [x] User menyetujui kategori/merek opsional, satu tingkat dan pengaman referensi.
- [x] Scope tidak mencakup impor, laporan margin, serial atau perubahan transaksi.
- [x] Worktree LSP user dicatat dan dikecualikan dari commit.

## Increment 1: Master Dan Pengaman

- [x] Tulis/run RED CRUD kategori/merek, izin, kode duplikat, soft delete/restore.
  - Acceptance: unauthorized403; kode tombstone tidak bisa digunakan ulang.
  - Verification: focused PHPUnit disposable.
- [x] Implementasi schema, layer/port, audit dan public lookup.
  - Acceptance: referensi barang terhapus ikut melindungi master; locking current
    read; tidak ada query privat lintas modul atau permission baru.
  - Verification: focused tests, Pint, review dependency dan route.
- [x] Isi hasil dan commit save point.

## Increment 2: Integrasi Barang Dan POS

- [x] Tambah pilihan nullable di DTO/normalisasi/create/update serta audit.
  - Acceptance: opsi invalid/nonaktif ditolak; omit mempertahankan, null clear;
    barang lama tetap valid; harga/satuan dan quick-create pembelian tetap utuh.
  - Verification: focused regression API/boundary.
- [x] Tambah label/filter Barang dan kategori/merek pada katalog POS.
  - Acceptance: filter bergabung dengan search/page; reset halaman1; filter tidak
    menghapus keranjang atau membatasi scan; harga/stok/checkout tetap authority.
  - Verification: focused backend dan browser alur berubah.
- [x] Commit save point sesudah verifikasi.

## Increment 3: UI Dan Panduan

- [x] Halaman master, form, soft delete/restore, sidebar/command palette.
  - Acceptance: shared workspace/table-pagination, Sonner, warna semantic,
    shortcut/F1, desktop/tablet dan permission guard existing.
  - Verification: typecheck/lint/build/page-guides dan browser.
- [x] Seeder demo relevan dan panduan F1/Markdown diperbarui.
  - Acceptance: demo eksplisit testing/local idempotent tanpa overwrite legacy.
  - Verification: test seeder, review operator guide.
- [x] QA desktop/tablet.
- [x] Commit UI dan panduan sesudah verifikasi.

## Increment 4: Aktivasi Dan Delivery

- [x] Satu gate regresi lengkap; ulang hanya pemeriksaan terdampak setelahnya.
- [x] Backup verified, migration additive/status MySQL dan route cache.
- [x] Fingerprint data/kolom/grant existing identik, kategori/merek lama null;
  tanpa seed atau klasifikasi otomatis di toko.
- [x] Catat bukti dan review scope tanpa LSP.
- [x] Commit penutupan, push dan verifikasi remote.

## Bukti Verifikasi

- RED2 gagal404, GREEN `ItemClassificationsTest`2 PASS/43 assertions.
- Pint PHP terkait PASS; route inspection uncached terpisah menunjukkan5 route
  kategori. Route/cache produksi tidak dibersihkan untuk QA.
- Foundation disimpan pada ab85bd2. Generator/dry-run N/A karena pengembangan
  module Items existing, bukan menambah module baru.
- Integrasi terfokus19 PASS/197 assertions; tambahan test boundary POS dan
  harga/pembayaran/invoice5 PASS/63 assertions. Pint PHP terkait PASS.
- MySQL disposable: current read referensi soft-deleted setelah snapshot,
  inactive setelah snapshot, peer lock timeout dan restore unused PASS.
  Database QA dibersihkan pada finally; database toko tidak dipakai test.
- Backup `backups/pos-20261102-104653-174a301d.sql` verified dengan restore
  disposable identik. Aktivasi native PASS: satu migration additive batch36 Ran,
  fingerprint kolom/data/grant existing identik, barang lama null. Tidak seed,
  reprice, assign role atau membuat transaksi di toko; route cache aktif.
- Frontend: typecheck PASS, lint resources/js PASS, build2955 modul PASS,
  page-guides32 halaman PASS. Build/hasil yang sama dipakai ulang bila source
  runtime tidak berubah; hanya formatter fixture/selector QA yang berubah.
- DevTools MCP cached/bin/stdio actual PASS: auth, Kategori barang, DOM dan
  screenshot pada `storage/framework/testing/mvp-browser-4531c7a3b0f6331d/`.
  Alur Chrome sesudahnya berhenti pada selector QA bernama Klasik, sedangkan
  aria-label aktual Tampilan Klasik. Selector diperbaiki, bukan runtime UI.
- Browser akhir PASS: `storage/framework/testing/mvp-browser-8e2454b74540d982/visual-report.json`.
  Desktop1440x1000/tablet1024x768, create/restore/used guard, kategori/merek barang,
  filter gabungan, F1/hotkey, POS Visual/Klasik, cart tetap, barcode di luar
  filter dan pilihan quick-create/cancel pembelian. Console/page errors kosong.
  Screenshot diperiksa; fingerprint transaksi/stok/FIFO/Finance identik.
- Regresi penuh sebelum interupsi kehilangan output akhir; cache PHPUnit
  historis tidak dijadikan bukti lulus. Gate akhir dipulihkan dengan JUnit:
  `php artisan test --compact --log-junit storage/framework/testing/item-classifications-final.xml`
  PASS996 tests/7937 assertions,151,13 detik. Tidak ada perubahan runtime setelah
  gate ini; hanya dokumentasi/formatter fixture/selector QA dan teks aktivasi.
- Commit integrasi c318491, tanpa file LSP user. Review arsitektur/security:
  Presentation memanggil use case/lookup publik; enum kind mencegah table bebas;
  permission backend, CSRF, whitelist input dan unique DB tetap. Filter sebelum
  pagination, label relasi eager-load, tidak ada model privat lintas module.
  Lookup aktif bounded1.000 dicatat di API, belum pencarian remote tanpa batas.
- Commit UI b3abecf memakai workspace/pagination/Autocomplete existing,
  panduan lokal per page dan navigasi berizin; tanpa dependency/design system baru.
- Delivery543cd20 berhasil ke origin/main, ls-remote sama dengan HEAD dan
  divergence0/0. File LSP staged/untracked user tidak ikut commit. Commit
  penutupan checklist berikutnya hanya dokumentasi, tanpa runtime change.
- HTTPS login200 dengan verifikasi TLS normal. Perangkat/printer/UAT fisik dan
  scheduler/backup terpisah tetap pending di work item MVP, tidak diaktifkan ulang.
