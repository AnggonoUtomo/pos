# Perbaikan Sumber Retur Pembelian

Status: selesai. Perbaikan, regresi backend dan QA MySQL lulus untuk scope retur.
Gate seluruh project masih memiliki satu FAIL existing di luar scope.

## Scope

Memperbaiki pembelian diterima yang tidak dikenali oleh lookup retur pada
Purchasing/PurchaseOrders, beserta regresi consumer Purchasing/Returns.
Tidak mengubah data transaksi toko, aturan FIFO, permission, atau pagination.
Gap filter tanggal invoice bukan bagian pekerjaan ini.

## Bukti Awal

`php artisan test --compact --filter=ReturnPurchaseLookupTest`: tiga test gagal;
sumber valid menghasilkan null dan pencarian kosong. Pengujian memakai database
testing disposable, bukan database produksi.

## Penyebab Dan Perbaikan

Stock menyimpan timestamp SQL dalam timezone aplikasi, sedangkan hasil public
contract berupa ISO UTC. Repository PurchaseOrders sebelumnya menyimpan ISO UTC
langsung melalui cast datetime Eloquent tanpa mengubah timezone terlebih dahulu.
Pada Asia/Jakarta terjadi selisih tujuh jam (termasuk potensi pergantian tanggal),
sehingga join waktu receipt yang ketat menolak sumber valid. Pola yang sama pada
paid_at membuat proof pembayaran untuk refund tidak cocok.

Adapter persistence kini mengonversi timestamp contract ke timezone aplikasi
sebelum menyimpan posted_at/paid_at. Query eligibility dan proof tidak dilonggarkan.
Tidak ada perubahan contract, schema, timestamp transaksi lama atau ledger.

## Verifikasi

- RED: tiga test lookup existing gagal; regresi baru memperlihatkan selisih
  waktu WIB, sedangkan UTC tidak mengalami selisih.
- Setelah fix, lookup dan seluruh feature Purchasing/Returns: 45 PASS,
  381 assertion (sebelum tambahan empat kasus metadata tidak cocok).
- Gate akhir `php artisan test --compact`: 885 PASS, 1 FAIL, 7264 assertion.
  Satu FAIL existing adalah InvoiceHistoryTest filter tanggal WIB, bukan retur.
  Seluruh test retur/refund/lifecycle yang sebelumnya terhambat kini PASS.
- Regresi tambahan mencakup WIB/UTC, pergantian tanggal, posted_at/paid_at,
  penolakan waktu berbeda satu detik, nomor/sumber/baris/nilai receipt tidak cocok.
- Audit baca-saja database toko: satu pembelian posted, nol mismatch waktu
  receipt dan pembayaran. Tidak perlu dan tidak dilakukan koreksi data lama.
- Frontend build/typecheck tidak diulang: tidak ada perubahan frontend.

## Penutupan

- `php scripts/qa/purchase-return-refund-mysql.php` PASS dengan APP_ENV=local,
  APP_TIMEZONE=Asia/Jakarta, serta path cache konfigurasi/route testing terpisah.
  Database random disposable: migration, quote readonly, race scope/refund,
  retry bersamaan, snapshot replay, residual mikro, nominal besar exact6,
  audit veto rollback, FK dan cleanup lulus. Database toko tidak dimutasi.
- Browser visual SKIPPED karena tidak ada perubahan UI/UX. Gate backend
  mencakup endpoint pencarian sumber, CRUD draft, posting dan settlement;
  QA native memverifikasi lifecycle nyata. Tidak mengulang kombinasi tema/viewport.
- Pint scoped PASS dan `git diff --check` PASS. Review dependency, integritas
  receipt, atomicity dan scope selesai; tidak ada query yang dilonggarkan.
- Increment perbaikan tersimpan pada commit `19c1a93`; penutupan dokumentasi
  dibuat sebagai commit terpisah. File LSP user tetap di luar commit.
- Tidak ada migration/seed/reset produksi, perubahan permission, dependency,
  runner OS, frontend atau Old-docs. Panduan operator tidak berubah karena alur
  dan langkah penggunaan tetap sama; yang diperbaiki hanya adapter penyimpanan.

Risiko terbuka di luar scope: InvoiceHistoryTest filter tanggal WIB masih FAIL.
Tidak ada perbaikan maupun klaim lulus untuk bug tersebut dalam pekerjaan ini.
