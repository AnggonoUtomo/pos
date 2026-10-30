# Spesifikasi Audit Log

## Kebutuhan

- Daftar waktu WIB, pengguna (termasuk sistem/pengguna tidak tersedia), module,
  tindakan, objek/reference dan tombol detail.
- Pencarian literal pada nomor/kode aman, referensi objek dan nama pengguna;
  filter tanggal WIB, pengguna, module dan tindakan; pagination global existing.
- Detail perubahan sebelum/sesudah bila dicatat, serta metadata aman bila log
  hanya menyimpan satu sisi. Tidak merekayasa nilai sebelum yang tidak ada.
- Mendukung attributes/old package serta before/after, attributes/old dan
  metadata properties manual. Tidak meneruskan payload mentah ke Inertia/JSON.
- Nilai uang/desimal tetap string dan ditampilkan dengan format Indonesia.
- Tampilan memakai band filter/workspace-table, ikon semantic, shortcut,
  panduan F1 dan detail dialog desktop/tablet Light/Dark.

## Pengaman

- GET /platform/audit-log, /data dan /{id} memerlukan auth, verified dan
  platform.audit-log.view. Menu/palette mengikuti permission yang sama.
- Super-system bypass authorization existing; role lain tanpa auto-grant.
- Read projection allowlist membuang password/token/credential, kontak/alamat,
  rekening, notes, payload nested bebas, path backup dan description mentah.
  Tidak memanggil relation subject/causer polymorphic atau mengungkap class path.
- Filter dan opsi tidak mencari atau menampilkan field sensitif yang dibuang.
- Endpoint mutasi tidak tersedia; request baca tidak menulis audit baru atau
  mengubah activity_log, user, master maupun transaksi.
- Tabel Spatie existing memakai ID numerik; dipertahankan tanpa migration/reset.
  Tidak membuat duplikat tabel audit atau memaksakan ULID pada histori package.
- Demo seeder skip: audit nyata harus berasal dari aktivitas modul. Fixture
  sintetis hanya dibuat pada database testing disposable, bukan data toko.

## Di Luar Scope

Retensi/pembersihan, ekspor, perubahan cakupan logging pada semua modul, merekam
data lama yang tidak pernah dicatat, grant role produksi dan bug tanggal invoice.
