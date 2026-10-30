# Filter Tanggal Penjualan dan Patch Dependency

## Tujuan dan Batas

Menutup kegagalan lama filter tanggal Sales/POS serta advisory `qs`
GHSA-6pq8-jhm2-2vcw dan `symfony/mime` CVE-2026-45070. Tidak mengubah
timezone, data transaksi toko, role, schema, scheduler, atau aturan bisnis.

## Temuan Awal

- Reproduksi test tanggal gagal: `date_from=2026-10-17` mengembalikan 0,
  padahal fixture mengharapkan 1.
- Writer invoice menyimpan `now()` dalam timezone aplikasi. Query mengubah
  batas hari WIB ke timezone penyimpanan tersebut. Fixture lama justru
  memasukkan timestamp UTC mentah tanpa konversi.
- `qs` 6.16.0 digunakan melalui `@inertiajs/core` 2.3.28; constraint
  `^6.16.0` menerima versi patched tanpa perubahan Inertia.
- `symfony/mime` 7.4.18 memerlukan patch minimal 7.4.22. Tidak mengirim
  email atau mengubah adapter email aplikasi.

Sumber: [advisory qs](https://github.com/advisories/GHSA-6pq8-jhm2-2vcw)
dan [advisory Symfony](https://symfony.com/cve-2026-45070).

## Status

Selesai: implementasi, verifikasi dan delivery origin/main sudah dilakukan.
Urutan dan bukti ada pada [plan](plan.md) dan [checklist](tasks.md).
Data dan cache produksi tidak dipakai untuk QA.

## Hasil Verifikasi

- Fixture timestamp UTC kini dikonversi ke timezone penyimpanan aplikasi.
  Query runtime sudah benar dan tidak diubah. Coverage UTC/Asia/Jakarta
  memeriksa 00.00 WIB, 23.59.59 WIB, batas eksklusif hari berikutnya, rentang
  terbuka, identitas baris dan timestamp ISO, tanpa mutation saat membaca.
- `qs` 6.16.0 -> 6.17.1 melalui `npm update qs --ignore-scripts --no-audit`.
  Tetap kompatibel dengan constraint Inertia ^6.16.0; tidak ada dependency
  langsung baru. `npm ci --ignore-scripts --no-audit` menyelaraskan tree lokal
  karena update awal mengubah lock tetapi kode paket lokal masih 6.16.0.
  Probe keamanan dijalankan lagi pada paket aktual, bukan hanya metadata audit.
- `symfony/mime` 7.4.18 -> 7.4.22 melalui update exact terarah,
  `--minimal-changes --no-scripts`; tidak menjalankan vendor publish otomatis.
- `node scripts/qa/dependency-security.mjs` PASS: sumber JSON/query/null
  prototype dan kedua opsi encodeValuesOnly tidak lagi memicu TypeError;
  serialisasi comma normal dan roundtrip filter Inertia tetap benar.
- `php vendor/bin/phpunit tests/Unit/MimeHeaderSecurityTest.php`:
  33 PASS/36 assertion. CRLF/LF/spasi/tab/separator/nama kosong/NUL ditolak
  melalui constructor, setter tunggal/bulk dan Headers; parameter normal,
  quoted filename dan pembuatan attachment email tanpa delivery tetap lulus.
- `php artisan test --compact`: 933 PASS/7444 assertion (130,50 detik),
  satu regresi penuh. Tidak ada gap test yang masih gagal.
- `npm audit`, `npm audit --omit=dev`, `composer audit --locked`:
  tidak ada advisory. `composer validate --strict`, check-platform-reqs,
  typecheck, ESLint script berubah dan build 2930 module PASS.
- Pint kedua test PHP PASS; file fixture browser lolos php -l. Pint fixture
  shared juga mengungkap style existing di luar baris baru; tidak memformat
  ulang bagian lama di luar scope.
- Browser Chrome/Playwright existing pada SQLite disposable Asia/Jakarta:
  filter Inertia tanggal yang sama/rentang terbuka, URL/reload/reset,
  desktop1440 dan tablet1024 PASS, console kosong. Fingerprint invoice,
  Stock/Finance dan audit identik. Screenshot tablet ditinjau di
  `storage/framework/testing/mvp-browser-d5b5334d9de25bd2/invoice-date-tablet.png`.
- Chrome DevTools MCP tersedia: isolated page mengakses history anonim,
  redirect login benar, DOM tanpa overflow dan console kosong. Cakupan
  authenticated filter memakai Playwright di atas, bukan diklaim sebagai MCP.
- Review menemukan risiko DB_URL menimpa path QA: harness menetralkan DB_URL,
  fixture memeriksa driver/path koneksi efektif sebelum PRAGMA/migration/write.
  Setup browser awal membuat invoice tambahan dari fixture refund; diganti
  dengan tiga fixture penjualan langsung sebelum QA final PASS.

Tidak ada migration baru, perubahan .env/cache produksi, mutasi toko,
perubahan role/grant, scheduler, Old-docs, atau file LSP user. Panduan operator
tidak diubah karena alur dan semantik tanggal WIB tetap sama.
