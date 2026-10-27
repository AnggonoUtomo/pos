# Tasks: Gambar Utama Barang

## Sebelum Mulai

- [x] Baca aturan project, arsitektur, struktur, dokumen Items dan quality.
- [x] Konfirmasi satu gambar utama opsional per barang dengan user.
- [x] Informasikan target Items dan consumer POS; periksa status Git.
- [x] Tulis contract, plan, acceptance dan checklist sebelum coding.

## Increment 1: Backend Media

- [x] Test RED upload, ganti/hapus, permission, invalid file dan rollback.
  - Acceptance: mereproduksi kebutuhan baru tanpa mutation database toko.
  - Verification: focused ItemImageTest pada SQLite disposable.
- [x] Implementasikan adapter Media Library, schema/model ULID dan port.
  - Acceptance: satu gambar per barang, privat, path tidak dari input user;
    soft delete/restore mempertahankan gambar.
  - Verification: focused tests dan migration disposable.
- [x] Integrasikan upload pada save dan URL pada daftar/katalog publik.
  - Acceptance: stok/harga/barcode/snapshot existing tidak berubah; file dan
    data lama aman jika validasi/audit/storage gagal.
  - Verification: rollback, barcode dan public contract tests.
- [x] Commit backend setelah focused test/Pint lulus.

## Increment 2: UI Barang Dan POS

- [x] Editor gambar: pilih/preview/ganti/hapus/batalkan, error dan processing.
  - Acceptance: gambar opsional; perubahan baru berlaku sesudah Simpan sukses;
    multipart update dan form harga/satuan tetap berfungsi.
  - Verification: browser upload, invalid upload, replace, remove dan reload.
- [x] Thumbnail master dan katalog POS.
  - Acceptance: dimensi stabil, object-contain, fallback ikon dan error gambar;
    barcode mengambil satuan/harga yang tepat dan tanpa foto tetap dapat dijual.
  - Verification: desktop/tablet, pencarian dan scan barcode existing.
- [ ] Commit UI setelah verifikasi yang terdampak lulus.

## Increment 3: Gate Dan Aktivasi

- [x] Regresi backend satu kali pada paket final.
- [x] Pint, ESLint scope berubah, typecheck dan build lulus.
- [x] Discovery DevTools dicatat dan fallback browser bila perlu.
- [x] Native MySQL migration disposable dan status/route tervalidasi.
- [x] Migration additive lokal aktif; data/master/transaksi existing tetap.
- [x] Panduan gambar dan batas backup binary ditulis; checklist diperbarui.
- [ ] Commit/push dan local/remote diverifikasi, tanpa file LSP user.

## Bukti

- RED test upload: FAIL sesuai harapan; image_url belum tersedia.
- Focused Items: PASS, 51 tests / 345 assertions termasuk sembilan test media.
  Meliputi multipart method spoof, validation, storage gagal, audit gagal,
  rollback file, preserve/replace/remove, soft delete/restore, permission dan
  barcode pada public catalog. Test media memakai DatabaseMigrations agar
  afterCommit benar-benar dieksekusi, bukan tertunda oleh outer test transaction.
- Pint scope PHP berubah: PASS. Tidak mengubah schema/data toko pada tahap ini.
- Commit backend: 18879ba. Review integrasi memakai permission POS existing
  sales.pos.invoices.create, bukan key permission baru.
- Gate regresi backend: PASS, 845 tests / 6858 assertions, satu kali pada
  backend paket. Sesudahnya hanya guard URL barang terhapus disesuaikan untuk
  mengembalikan null (menghindari thumbnail 404 pada daftar terhapus); verifikasi
  ulang dibatasi ItemImageTest, bukan mengulang seluruh suite.
- Typecheck, ESLint scope UI berubah dan build produksi: PASS.
- Native MySQL disposable: PASS schema/morph ULID, upload/replace/remove dan
  cleanup file. Percobaan pertama terhenti karena cache lama belum memuat default
  package; rerun fresh config lulus, database disposable selalu dibersihkan.
- `.env` lokal: hanya menambahkan tanda kutip APP_NAME yang mengandung spasi;
  nilai nama/credential tidak diganti dan file tidak masuk Git. config:cache
  dan route:cache PASS setelah migration additive media batch 29 aktif.
- Produksi tetap production/debug false/secure cookie true. Items 6, invoice 3,
  akun 2, role 8 dan media 0; tidak seed ulang/reset atau mengisi foto rekaan.
- HTTPS login HTTP 200, route image tercatat dan migration status Ran.
- DevTools discovery: BLOCKED, tool Chrome DevTools MCP tidak tersedia pada
  sesi ini. Fallback Chrome existing lewat Playwright; bukan klaim DevTools PASS.
- Fallback browser final: PASS, empat kelompok pemeriksaan, tanpa console error
  tidak terduga. Artefak ignored:
  storage/framework/testing/item-image-browser-54a4d96ac270e34b/report.json.
  Screenshot form desktop/tablet, tabel master dan POS diperiksa visual; tidak
  ada overflow/overlap pada elemen gambar. Bukan uji tablet/printer fisik.
- Harness awal disesuaikan untuk Chrome installed, menunggu image.decode(),
  dan locator img bernama spesifik agar tidak bertabrakan dengan ikon SVG.
  Perbaikan hanya harness; UI tidak berubah setelah build yang lulus.
- .env.testing sementara sudah dihapus; fixture browser/files/DB seluruhnya
  terisolasi. Browser dan server QA selesai, tidak meninggalkan runner OS.
- Review akhir: dependency layer terjaga, media tidak bocor pada JSON,
  permission memakai key existing, query media di-eager-load, snapshot posted
  dan fingerprint quote tidak memakai gambar. Gap backup binary dijelaskan
  pada panduan tambahan; PDF global lama tidak diubah dalam pekerjaan ini.
- Focused ulang guard URL terhapus: PASS, ItemImageTest 9 tests / 104 assertions.
  Pint guard model PASS; frontend/build/browser sebelumnya dipakai kembali
  karena source UI tidak berubah.
