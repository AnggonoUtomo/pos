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

Sedang dikerjakan. Urutan dan bukti ada pada [plan](plan.md) dan
[checklist](tasks.md). Data dan cache produksi tidak dipakai untuk QA.
