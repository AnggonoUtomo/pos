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
- [ ] Isi hasil dan commit save point.

## Increment 2: Integrasi Barang Dan POS

- [ ] Tambah pilihan nullable di DTO/normalisasi/create/update serta audit.
  - Acceptance: opsi invalid/nonaktif ditolak; omit mempertahankan, null clear;
    barang lama tetap valid; harga/satuan dan quick-create pembelian tetap utuh.
  - Verification: focused regression API/boundary.
- [ ] Tambah label/filter Barang dan kategori/merek pada katalog POS.
  - Acceptance: filter bergabung dengan search/page; reset halaman1; filter tidak
    menghapus keranjang atau membatasi scan; harga/stok/checkout tetap authority.
  - Verification: focused backend dan browser alur berubah.
- [ ] Commit save point sesudah verifikasi.

## Increment 3: UI Dan Panduan

- [ ] Halaman master, form, soft delete/restore, sidebar/command palette.
  - Acceptance: shared workspace/table-pagination, Sonner, warna semantic,
    shortcut/F1, desktop/tablet dan permission guard existing.
  - Verification: typecheck/lint/build/page-guides dan browser.
- [ ] Seeder demo relevan dan panduan F1/Markdown diperbarui.
  - Acceptance: demo eksplisit testing/local idempotent tanpa overwrite legacy.
  - Verification: test seeder, review operator guide.
- [ ] QA desktop/tablet serta commit.

## Increment 4: Aktivasi Dan Delivery

- [ ] Satu gate regresi lengkap; ulang hanya pemeriksaan terdampak setelahnya.
- [ ] Backup verified, migration additive/status MySQL dan route cache.
- [ ] Fingerprint data/kolom/grant existing identik, kategori/merek lama null;
  tanpa seed atau klasifikasi otomatis di toko.
- [ ] Catat bukti/review, commit scope tanpa LSP, push dan verifikasi remote.

## Bukti Awal

- RED2 gagal404, GREEN `ItemClassificationsTest`2 PASS/43 assertions.
- Pint PHP terkait PASS; route inspection uncached terpisah menunjukkan5 route
  kategori. Route/cache produksi tidak dibersihkan untuk QA.
- Belum migration/seed pada database toko; schema baru diuji SQLite disposable.
