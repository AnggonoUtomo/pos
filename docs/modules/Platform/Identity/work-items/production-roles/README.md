# Role Produksi

User meminta profil panduan menjadi role database siap dipakai dengan permission
masing-masing. Target hanya Platform/Identity; tidak mengubah UI atau aturan bisnis.

Disepakati: supervisor boleh mengelola stok awal/koreksi, posting transfer dan
retur barang, serta Terima dan bayar pembelian; refund hanya keuangan. Supervisor
juga mengelola draft retur penjualan agar alur retur tidak berhenti pada super-system.
Role lain mengikuti paket minimal panduan. Tidak membuat akun/password,
mengganti assignment user, menghapus role lama atau mengubah direct permission.

Seeder module khusus produksi, tanpa demo: tujuh role guard web. Role existing
dengan matriks berbeda harus menyebabkan penolakan atomic, bukan penimpaan.
Role super-system existing dipertahankan bersama seluruh permission/pivotnya;
otorisasi tetap melalui Gate::before existing.

Database awal: production/MySQL, role web super-system dan test. Enam nama role
operasional belum ada. Kredensial dan identitas akun tidak dicatat di dokumen.

## Risiko Dan Pengaman

- Role baru belum aktif untuk pengguna sampai admin melakukan assignment.
- Izin gabungan menerima/membayar pembelian hanya diberikan pada supervisor.
- Kasir tidak melihat seluruh invoice atau melakukan refund/koreksi stok.
- Tidak menambah permission key atau men-seed demo. Katalog module harus tersedia.
- Semua pembuatan role/audit atomic; cache permission dibersihkan setelahnya.
- Ulang seeder tidak menghapus perubahan manual: matriks berbeda dilaporkan konflik.
- Tidak menjanjikan pembatasan user per gudang atau approval workflow baru.

## Hasil 27 Oktober 2026

Enam role baru dibuat: owner-supervisor (21), master-admin (6),
cashier-operational (2), warehouse-staff (5), purchasing-staff (7), finance-staff
(9 permission). Super-system existing mempertahankan 8 grant eksplisit lamanya
dan bypass Gate existing; role test tetap 3 permission. Total delapan role web,
tujuh di antaranya profil produksi. Enam activity production_role_created dibuat.
Seluruh profil baru belum mempunyai pengguna; assignment tetap dilakukan admin.

Matriks lengkap dan cara assignment: [role produksi](../../production-roles.md).

## Verifikasi

| Pemeriksaan | Hasil |
| --- | --- |
| RED ProductionRolesTest sebelum implementasi | 6 error Class not found, seeder belum ada |
| ProductionRolesTest setelah implementasi | PASS 6 test / 75 assertion |
| Identity focused suite | PASS 67 test / 461 assertion |
| Full backend suite | PASS 827 test / 6669 assertion, dijalankan sekali |
| Pint scoped seeder/test/helper | PASS |
| Preview helper | PASS read-only, enam role akan dibuat dan super-system existing |
| `php artisan pos:backup --verify` | PASS dump + restore disposable identik; database toko tidak ditimpa |
| `php scripts/qa/activate-production-roles.php --apply` | PASS production/MySQL; 62 tabel existing terjaga, pengulangan no-op |
| Pemeriksaan aktual role/audit | PASS enam profil baru dengan jumlah permission tepat, 6 audit, role/assignment lama utuh |
| `route:list --path=platform/identity --except-vendor` | PASS 9 route existing, tidak ada route baru |

Backup sebelum aktivasi tersimpan lokal pada
`backups/pos-20261027-070921-300d4174.sql` (ignored, bukan salinan terpisah).
Laporan aktivasi tanpa identitas akun/credential berada di
`storage/framework/testing/production-roles/activation.json` (ignored).
Fingerprint membandingkan seluruh data existing, termasuk users, pivot role,
direct grant, permission, ledger dan audit lama. Hanya row role/pivot/audit baru
yang diizinkan; pengulangan membandingkan seluruh row untuk memastikan no-op.

## Batas QA Dan Gap Existing

- UI/React/CSS, schema dan route tidak berubah: browser MCP, frontend build dan
  migration tidak diulang. Gate dan endpoint readonly dicoba melalui feature test.
- Bootstrap test awal terhalang APP_NAME pada .env existing yang mengandung
  whitespace tanpa kutip. .env produksi dan cache produksi tidak disentuh.
  QA menggunakan .env.testing sementara berisi konfigurasi SQLite/array dan
  APP_KEY acak lewat environment proses, bukan credential toko. File sementara
  dihapus setelah suite selesai; tidak ikut commit.
- Penyesuaian format .env existing ditunda karena di luar scope role.
- Tidak menjalankan DatabaseSeeder global, seeder akun/demo, migration, reset
  password, role assignment user, perubahan .env atau penjadwal OS.
- Dokumen/PDF panduan sebelumnya adalah snapshot penyusunan tanpa grant database;
  status provisioning terkini dan matriks aktual mengacu pada dokumen role produksi.
- Old-docs dan file LSP staged/untracked user tidak disentuh.

## Pengiriman

Implementasi tersimpan pada `0890024`, matriks/bukti aktivasi pada `9600303`.
Push origin/main berhasil dan SHA remote diverifikasi sama dengan HEAD,
divergence 0/0 sebelum pencatatan penutupan ini. Empat file LSP staged dan
empat untracked user tetap di workspace, tidak ikut commit.
