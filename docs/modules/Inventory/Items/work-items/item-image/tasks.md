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
- [ ] Commit backend setelah focused test/Pint lulus.

## Increment 2: UI Barang Dan POS

- [ ] Editor gambar: pilih/preview/ganti/hapus/batalkan, error dan processing.
  - Acceptance: gambar opsional; perubahan baru berlaku sesudah Simpan sukses;
    multipart update dan form harga/satuan tetap berfungsi.
  - Verification: browser upload, invalid upload, replace, remove dan reload.
- [ ] Thumbnail master dan katalog POS.
  - Acceptance: dimensi stabil, object-contain, fallback ikon dan error gambar;
    barcode mengambil satuan/harga yang tepat dan tanpa foto tetap dapat dijual.
  - Verification: desktop/tablet, pencarian dan scan barcode existing.
- [ ] Commit UI setelah verifikasi yang terdampak lulus.

## Increment 3: Gate Dan Aktivasi

- [ ] Regresi backend satu kali pada paket final.
- [ ] Pint, ESLint scope berubah, typecheck dan build lulus.
- [ ] Discovery DevTools dicatat dan fallback browser bila perlu.
- [ ] Native MySQL migration disposable dan status/route tervalidasi.
- [ ] Migration additive lokal aktif; data/master/transaksi existing tetap.
- [ ] Panduan gambar dan batas backup binary ditulis; checklist diperbarui.
- [ ] Commit/push dan local/remote diverifikasi, tanpa file LSP user.

## Bukti

- RED test upload: FAIL sesuai harapan; image_url belum tersedia.
- Focused Items: PASS, 51 tests / 345 assertions termasuk sembilan test media.
  Meliputi multipart method spoof, validation, storage gagal, audit gagal,
  rollback file, preserve/replace/remove, soft delete/restore, permission dan
  barcode pada public catalog. Test media memakai DatabaseMigrations agar
  afterCommit benar-benar dieksekusi, bukan tertunda oleh outer test transaction.
- Pint scope PHP berubah: PASS. Tidak mengubah schema/data toko pada tahap ini.
