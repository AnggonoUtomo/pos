# Rencana Refund Retur Penjualan

## Scope Dan Urutan

Menyelesaikan uang retur posted tanpa memposting stok lagi. Urutan dependency:
baseline -> valuasi/ADR -> writer Finance -> settlement Returns -> UI/history
-> QA/aktivasi/delivery. [Checklist](tasks.md) memuat acceptance dan verifikasi
tiap task; checkbox baru dicentang setelah pekerjaan dan pemeriksaan selesai.

## Increment 1: Baseline Dokumentasi

Status: Passed. Owner: Returns dan Finance. Dokumentasi dan 31 link lokal
diverifikasi, whitespace PASS; tidak ada perubahan runtime.

Catat hasil interview, kondisi source, acceptance, risiko, ownership, QA dan
non-scope. Tautkan dari rencana/checklist module. Tidak membuat class, route,
migration atau perubahan data pada increment ini.

## Increment 2: Valuasi, Contract Dan ADR

Status: Passed. Owner: Returns; desain boundary Finance tercatat pada ADR-016.
Domain valuasi, reconciliation dan test selesai; kontrak runtime/migration/
writer belum dibuat. Full592 test/5188 assertion dan scoped Pint PASS.

Mulai dari test red aturan murni dan kunci desain sebelum menambah persistence:

- Hak uang per original invoice line berasal dari subtotal - discount_amount
  + tax_amount yang tersimpan, bukan unit price terbaru, HPP atau tendered cash.
  Verifikasi hubungan dengan total invoice/net penerimaan melalui kontrak POS;
  snapshot invalid/tidak didukung harus ditolak tanpa mutation, bukan diabaikan.
- Pisahkan kuantitas yang sudah diretur secara fisik dari yang sudah diselesaikan
  uangnya. Retur lama posted belum dibayar, bukan otomatis lunas.
- Tetapkan `remaining_quantity = sold_quantity - settled_quantity` dan
  `remaining_amount = original_net - settled_amount` per original line. Untuk
  jumlah sebagian, alokasikan `remaining_amount * quantity / remaining_quantity`
  dengan round down scale2; penghabisan sisa kuantitas mengambil seluruh residual.
  Gunakan satuan snapshot baris asal dan aritmetika decimal exact, bukan float.
- Rp100,00 yang dibagi atas retur 0,333333; 0,333333; dan 0,333334 dari kuantitas
  asal 1 menghasilkan Rp33,33; Rp33,33; dan Rp33,34. Nol tidak menghabiskan hak
  nominal tetapi kuantitasnya tetap diselesaikan. Uji pula urutan refund yang
  berbeda dari urutan penerimaan fisik.
- Quote bersifat preview, bukan reservasi atau ledger. Settlement menghitung
  ulang di bawah lock dan menolak quote stale sehingga kasir perlu konfirmasi
  nilai baru; jangan diam-diam membayar nominal berbeda dari yang dikonfirmasi.
- Pilih persistence alokasi/metadata, unique source, current read/lock order
  untuk invoice sama, canonical hash/replay dan guard rollback. Jika butuh guard
  invoice, miliki di Returns atau gunakan contract publik, bukan model privat POS.
- Public boundary baru hanya dibuat untuk consumer nyata. Writer refund tidak
  menyamar sebagai PurchasePaymentWriter atau memakai source_type purchase.
  Tentukan penyimpanan refund di Finance serta dampaknya pada history/guard master
  dalam ADR tanpa mengubah arti transaksi pembelian existing.
- Tentukan payload quote/settlement, version, hasil nol/positif, permission baru,
  serta response aman; perbarui dokumen API ketika contract dikunci.

ADR mengikuti penomoran project yang tersedia saat increment dimulai. Nilai
ledger, allocation dan metadata harus memiliki batas presisi/overflow eksplisit.

## Increment 3: Pengeluaran Refund Finance

Status: Passed. Owner: Finance/Payments. Dependency: Increment 2.
Focused26/full618 test dan native MySQL PASS. Migration lokal masih Pending;
consumer Returns/endpoint/history/UI belum dipasang. Detail bukti pada tasks.

Implementasikan public writer/DTOs minimal untuk consumer Returns dan adapter
schema module sendiri. Shared transaction caller wajib, nominal refund scale2,
1..10 metode unik dengan kontribusi positif dan total tepat, tanpa kembalian.
Metode/akun aktif dan kompatibel dicek ulang di bawah lock terurut. Rekening
keluar berasal dari master, bukan caller. Nilai nol tidak memanggil writer.

Simpan snapshot aman, source refund yang berbeda dari purchase, timestamp
server dan audit wajib. Replay identik sebelum validasi master live harus
stabil; payload berbeda ditolak. Guard histori master mencakup refund.
Tidak membangun running balance, integrasi bank atau endpoint pengeluaran bebas.

Verifikasi: test red-green, migration up/down disposable serta down guard data
posted, payload/duplicate/replay/audit veto, presisi besar, regresi pembayaran
penjualan/pembelian dan perlindungan master.

## Increment 4: Quote Dan Settlement Returns

Status: Passed. Owner: Sales/Returns. Dependency: Increment 2 dan 3.
Focused15 test/124 assertion, full633/5428 serta native MySQL empat worker PASS.
Migration lokal dua file masih Pending; UI/history masuk Increment 5.

Quote hanya untuk posted yang eligible, membaca snapshot safe tanpa HPP. Pada
settlement, lock scope invoice/alokasi dan dokumen sesuai urutan ADR; current
read menghitung ulang hak dan quote. Jalankan writer Finance untuk total positif;
untuk nol, tolak baris metode dan simpan penyelesaian tanpa ledger pembayaran.
Keduanya mencatat alokasi kuantitas/nominal, status dan audit dalam transaksi sama.

Replay identik tidak menghitung ulang hak yang telah dikonsumsi dan tidak
menambah payout/audit. Stale version, quote berubah, retry isi berbeda,
draft/trashed dan snapshot invalid ditolak. Permission backend terpisah melalui
HasMiddleware/policy; frontend tidak menjadi pengaman bisnis.

Verifikasi: feature quote/settlement/zero, HTTP authorization termasuk
super-system, batas total per original line, version/replay, tamper nominal/
account, audit Returns gagal setelah Finance berhasil dan fingerprint stok/
invoice/penerimaan asal tetap. Native MySQL dua proses menguji invoice sama,
duplicate source dan current read, bukan hanya simulasi SQLite berurutan.

## Increment 5: UI Refund Dan History

Status: Passed. Owner: Returns dan Finance. Dependency: Increment 4.

Form/modal lokal Returns menampilkan quote uang format Indonesia, pilihan metode
aktif dan sisa tagihan; nominal total bukan field bebas. Quote stale memerlukan
muat ulang/konfirmasi, loading tidak boleh submit ganda. Nol menampilkan
Tanpa pengembalian uang tanpa field metode. Status fisik diterima tetap berbeda
dari status penyelesaian uang. Selesai menjadi readonly dengan snapshot hasil.

Pakai shadcn/ui, icon semantic/tooltip, Sonner, dropdown shortcut di workspace
dan command palette existing, tanpa mengubah tema. Uji empty/error/retry,
403/422, timeout dengan retry aman, serta abort agar quote lama tidak menimpa
pilihan baru. Detail publik tidak membocorkan HPP, rekening atau hash.

Integrasikan refund positif dalam riwayat Finance existing, bukan halaman ledger
paralel. Pertahankan kontrak route/detail/filter dan presisi historis penerimaan
scale2/pembelian scale6. Jenis sumber refund harus dapat dikenali dari snapshot;
saldo berjalan tidak ditampilkan. Penyelesaian nol hanya di Returns.

Verifikasi: typecheck/lint/build dan browser authenticated menggunakan fixture
disposable pada desktop1440x1000, tablet1024x768 dan768x1024; Light/Dark serta
sidebar expanded/collapsed. Tidak ada QA mobile pada scope ini.

Hasil aktual: full637 test/5509 assertion, focused93/874, native history exact
sale/refund28digit scale2 dan purchase24digit scale6 PASS. Chrome DevTools MCP
existing stdio isolated authenticated positif/nol/history dan framing PASS;
Playwright timeout30s, 5xx, respons hilang setelah commit, replay identik, stale
quote nyata, nol/residual, readonly/permission, Light/Dark dan sidebar PASS.
Bug shortcut nol saat fokus di luar form ditemukan RED pada browser lalu
diperbaiki pada level dialog; rerun GREEN. Bukti rinci pada tasks.md.

## Increment 6: QA, Aktivasi Dan Delivery

Status: Passed. Owner: Returns dan Finance. Dependency: seluruh increment.

Jalankan seluruh regresi dan review diff/boundary. Siapkan aktivasi local/MySQL
additive yang hanya menjalankan migration baru dan catalog permission. Fingerprint
kolom/data existing, role/grant, source invoices, pembayaran, Stock/FIFO dan audit
harus tetap; jangan otomatis membayar retur lama atau menjalankan demo transaksi.
Uji aktivasi dua kali. Catat skip seeder refund otomatis: payout bukan data master.

QA full637/5509 dan frontend gates lulus. Native writer/settlement/history dan
native aktivasi berulang sebelum/sesudah settlement lulus pada DB disposable.
DevTools dan Playwright desktop/tablet terbaru lulus. Aktivasi lokal dua kali
Ran batch22 dengan fingerprint seluruh kolom/data existing, metadata existing,
user/role/pivot, source/Stock/Finance/audit identik. Tidak grant/reset/demo/payout.
Checkpoint delivery cabae83 sudah cocok dengan remote origin/main, divergence0/0.
Commit dokumentasi penutup mengikuti dengan pemeriksaan remote ulang;
hasil command ada pada tasks.md dan riwayat Git.

Dokumen API/spec/module index/QA diperbarui mengikuti bukti aktual, bukan rencana.
Commit otomatis setiap task/increment verified; push hanya setelah keseluruhan
work item selesai atau diminta eksplisit. Buktikan SHA remote dan divergence.

## QA Automated

- Valuasi: unit tests pada `tests/Unit/Modules/Sales/Returns` (ditambah saat coding).
- Returns: `php artisan test --compact tests/Feature/Modules/Sales/Returns`.
- Finance: `php artisan test --compact tests/Feature/Modules/Finance/Payments`.
- Regresi keseluruhan: `php artisan test --compact`.
- Frontend: `npx tsc --noEmit`, `npx eslint resources/js`, `npm run build`.
- PHP: scoped `vendor/bin/pint --test` pada file PHP yang diubah.
- Route/status: `php artisan route:list --path=sales/returns`,
  `php artisan route:list --path=finance`, `php artisan migrate:status`.
- Native MySQL refund dan fixture browser dibuat pada increment implementasi;
  nama command final dicatat setelah file ada, bukan diklaim sudah tersedia.
- Chrome DevTools MCP dicoba lebih dahulu sesuai [QUALITY](../../QUALITY.md);
  bila BLOCKED/SKIPPED, tulis alasan dan gunakan fallback browser existing tanpa
  install dependency/perubahan konfigurasi/profile user tanpa persetujuan.
- Semua QA mutation memakai DB disposable, credential stdin/redacted dan cleanup;
  artifact di storage tidak di-commit. Tidak memakai akun/session user.

## Batas Berhenti Dan Rollback

User menyetujui lanjut Increment 6: QA, aktivasi aman dan delivery. Aktivasi
lokal sudah lulus dua kali setelah verifikasi disposable/gate relevan. Perubahan scope di luar README
perlu persetujuan user.

Dokumentasi dapat dipulihkan melalui revert commit terfokus, bukan reset file
user. Setelah ada settlement, down migration tidak boleh menghapus ledger atau
alokasi posted; perbaikan memakai forward fix/pekerjaan reversal yang disetujui.
