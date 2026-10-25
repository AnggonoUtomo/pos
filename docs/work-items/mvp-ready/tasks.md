# Tasks: MVP Siap Operasional Toko

## Sebelum Mulai

- [x] AGENTS.md, README, arsitektur dan struktur canonical dibaca.
- [x] Empat paket dan batas MVP langsung lunas disetujui user.
- [x] QA terfokus, reuse bukti lulus, dan scope freeze ditetapkan.
- [x] Perubahan user yang staged di SampleUI/storage diidentifikasi dan dikecualikan.

## Paket 1: Operasional Stok

- [x] Posting transfer penuh antar-gudang melalui public contract Stock.
  - Acceptance: stok asal berkurang FIFO, tujuan bertambah dengan biaya sama;
    status, ledger dan audit atomik; tidak minus/duplikasi posting.
  - Verification: test integrasi sukses, kurang stok, rollback, idempotensi,
    permission, biaya; migration/status dan browser alur posting.
- [x] Koreksi stok minimum dengan alasan dan permission khusus.
  - Acceptance: selisih masuk/keluar masuk ledger/FIFO, posted immutable,
    audit wajib, kuantitas/nilai tetap konsisten.
  - Verification: test terfokus dan browser alur koreksi.
- [x] Gate paket lulus, catatan aktivasi, commit dan push diverifikasi.

## Paket 2: Transaksi POS

- [x] Penerimaan dan pelunasan pembelian satu konfirmasi atomik.
  - Acceptance: alur utama draft -> diterima/lunas; pembayaran gagal tidak
    meninggalkan stok/pembelian posted tanpa uang. Dokumen legacy belum dibayar
    tetap dapat dilunasi, tanpa dianggap utang baru.
  - Verification: sukses, replay, rollback pembayaran dan dua permission.

- [x] Diskon persen/nominal dan pajak server-authoritative.
  - Acceptance: subtotal, pembulatan, total dan pembayaran konsisten;
    harga level/satuan, permission dan stok tidak boleh dilewati.
  - Verification: test perhitungan/checkout/tampering/rollback terfokus.
- [x] Struk, detail invoice dan retur/refund mengikuti snapshot nilai bersih.
  - Acceptance: refund tidak melebihi penjualan setelah diskon/pajak;
    sisa pembulatan terjaga.
  - Verification: test retur dan browser transaksi lengkap.
- [x] Gate paket lulus, commit dan push diverifikasi.

## Paket 3: Administrasi Toko

- [x] Pengaturan identitas toko/struk dengan permission dan audit.
  - Acceptance: input tervalidasi; struk baru memakai snapshot identitas toko.
  - Verification: test update/permission/snapshot dan browser.
- [x] Laporan operasional minimum dari data nyata.
  - Acceptance: ringkasan penjualan, pembelian, retur, stok dan pembayaran;
    filter tanggal/gudang sesuai arti data; bukan klaim saldo bank/laba bersih.
  - Verification: fixture relasional, total/refund/filter/permission.
- [x] Gate paket lulus, commit dan push diverifikasi.

## Paket 4: Persiapan Penggunaan

- [x] Aktivasi aman tanpa reset data, grant berlebih, atau demo transaksi otomatis.
- [x] Backup dan restore database teruji pada database disposable.
- [x] Konfigurasi produksi, build assets dan keamanan dasar diperiksa.
  - Produksi kini aktif pada PC Windows/Laragon yang disetujui user;
    readiness --production PASS. Trust LAN/tablet, pemicu scheduler dan
    persetujuan operator tetap terpisah dari konfigurasi PC.
- [x] Skenario otomatis desktop/tablet: master -> beli/lunas -> stok -> jual/lunas
  -> retur -> uang kembali -> transfer/koreksi -> laporan/struk.
- [x] Blocker teknis ditutup; kebutuhan printer/perangkat dan persetujuan operator dicatat.
- [x] Patch dependency existing sesuai persetujuan user, tanpa major/dependency baru.
  - Verification: Vite6.4.3 ->6.4.5, follow-redirects1.16.0 ->1.16.1;
    npm audit penuh/production0, Composer0; lint/typecheck/build dan browser PASS.
- [x] Gate final sekali, commit/push diverifikasi; tidak mengklaim UAT manusia
  selesai tanpa pelaksanaan operator.

## Bukti Verifikasi

Paket stok: test terfokus baru 9/71 PASS; regresi backend 783/6194 PASS.
Typecheck, ESLint Stock/Transfers, build2751 dan Pint scoped PASS. Browser
operasional DevTools PASS: transfer posted, koreksi keluar 0,5, ledger, screenshot
desktop1440/tablet1024 dan console. Bukti ignored pada
storage/framework/testing/transfer-draft-browser-a9c4e904f019871a. Aktivasi lokal
batch27 sudah PASS. Savepoint source 132477c; guard transfer kosong/replay stale
cbb52a1 dengan focused5/45 PASS.

Paket POS: focused POS/Returns 87/694 PASS; sesudah tambahan DTO snapshot,
checkout16/153 PASS. Tambahan receive-and-pay7/53 PASS. Savepoint f38430d,
f9c82c5 dan 29bc3e8. Test client Node meliputi diskon/pajak nol, locale, negatif
pecahan kecil di laporan, recovery payload pricing, payment dan ULID PASS.

Regresi gabungan backend `php artisan test --compact`: 795/6266 PASS. Sesudahnya
behavior backend hanya berubah pada guard transfer dan readiness;
focused transfer5/45 dan readiness1/3
PASS, bukan mengulang seluruh suite. Backup CLI unsupported SQLite1/2 PASS.
Fixture filter laporan menggunakan tanggal WIB agar stabil saat pergantian hari;
focused laporan1/11 PASS sesudah pergantian hari, tanpa perubahan perhitungan.
ESLint resources/js, typecheck, Pint scoped dan build Vite6.4.5/2754 module PASS.
Review source dan screenshot desktop/tablet PASS; tidak mengulang matrix tema.

`php scripts/qa/mvp-integrity-mysql.php`: database MySQL disposable dengan nama
acak/actual connection guard, migrate lengkap, fixture alur beli -> jual diskon/
pajak -> retur/refund kedua arah -> transfer -> koreksi -> laporan PASS.
7 movement; invoice199800; refund pelanggan99900; penerimaan supplier120000;
nilai stok900000; selisih arus uang-980100. Semua exact, source/grant toko bukan
target fixture; database dibersihkan. Bukan klaim uji race worker tambahan.

`php scripts/qa/activate-mvp-local.php`: lima migration additive batch27 dan
katalog permission PASS. Fingerprint kolom/row existing, pivot/grant dan ledger
tetap identik; tanpa reset/password/demo/posting/grant otomatis.
`php artisan migrate:status`: seluruh migration Ran.

`php artisan pos:backup --verify`: dump private, restore ke MySQL disposable,
fingerprint sumber/restored identik dan cleanup PASS sebelum/sesudah aktivasi.
Backup/artefak QA diabaikan Git. `php artisan pos:readiness`: semua cek runtime
lokal PASS termasuk identitas toko dan rekonsiliasi FIFO/no-negative. Mode
--production secara benar menolak environment local/debug/HTTP; konfigurasi
produksi tidak diubah otomatis.

`node scripts/qa/mvp-checkout-devtools.mjs`: Chrome DevTools MCP executable
existing/isolated, fixture SQLite disposable, credential random stdin PASS.
Pengaturan toko -> terima dan bayar -> POS diskon10%/pajak11% -> total199800 ->
receipt snapshot -> laporan. Screenshot desktop1440/tablet1024, console bersih,
DB assertion invoice1/pembelian lunas2. Bukti ignored:
storage/framework/testing/mvp-browser-97aba51144c0e504. Toast quote yang semula
menutupi Pembayaran dipindah ke kanan atas dan alur diuji ulang sampai selesai.

Sesudah label navigasi dipendekkan menjadi Pengaturan toko, hanya
`--navigation-only` yang diulang: batas teks/badge tablet tidak bertumpuk,
tanpa mengulang workflow checkout di UI. Bukti mvp-browser-6b6705fd47e07ed8.

## Pengiriman Git

Implementasi, patch dependency, QA dan panduan operasional sudah dikirim ke
`origin/main` sampai `12ad441`. Push berhasil; HEAD lokal cocok dengan
`refs/heads/main` remote dan divergence `0 0`. Checklist pengiriman diperbarui
sesudah verifikasi tersebut, bukan sebelum push. Perubahan staged milik user
di SampleUI/storage tidak dimasukkan; Old-docs tidak disentuh.

## Pending Operator / Go-Live

- [ ] Persetujuan UAT pemilik/operator dengan data dan perangkat toko nyata.
- [ ] Printer fisik/lebar kertas dan prosedur cetak diverifikasi operator.
- [x] Jadwal backup lokal ditetapkan user pukul22:00WIB dan disimpan aktif.
- [ ] Penanggung jawab, pemicu scheduler dan salinan backup terpisah ditetapkan.
- [x] Konfigurasi production/HTTPS/debug/cookie PC server diverifikasi.

Rincian [OPERASIONAL.md](OPERASIONAL.md). Pending manusia/deploy ini tidak
ditandai selesai hanya karena QA automated lulus. Tidak menambah fitur baru.

## Lanjutan: Penerimaan Operasional Windows/LAN

Target dikonfirmasi user: PC Windows/Laragon ini melayani kasir dan tablet
di jaringan toko. Ini penyelesaian Paket 4, bukan penambahan fitur/modul.

### Increment 1: Inventaris Dan Lembar Penerimaan

- [x] Baca panduan aktif dan periksa environment tanpa mengubah data.
  - Acceptance: printer, scheduler, HTTPS dan virtual host aktual diketahui;
    credential/key tidak dibaca atau ditampilkan.
  - Verification: inventaris Windows, request HTTPS dan konfigurasi vhost.
- [x] Siapkan lembar UAT operator/printer dan runbook Windows/LAN.
  - Acceptance: hasil harapan terukur, persetujuan manusia tetap pending;
    `.env` aktif, data, firewall dan service tidak diubah tanpa kesiapan target.
  - Verification: review skenario, link relatif dan `git diff --check`.
- [x] Commit increment dokumentasi yang sudah diverifikasi.

### Increment 2: Jadwal Dan Media Backup

- [x] Konfirmasi jadwal opsional/jam WIB dikelola module Pengaturan Operasional.
- [ ] Konfirmasi lokasi salinan terpisah bersama penanggung jawab toko.
- [x] Koreksi pemicu scheduler sesuai keputusan user: cabut task PowerShell
  buatan agent, hapus installer otomatis, serahkan cron/penjadwal OS ke admin.
  - Acceptance: Laravel Scheduler tetap menjadi standar lintas OS, tanpa task
    atau service pengganti otomatis; status backup tidak diklaim off-device.
  - Verification: task spesifik tidak ada; schedule:list dan source/dokumen.
- [ ] Operator menetapkan penanggung jawab, media terpisah dan restore berkala.

### Increment 3: Konfigurasi Dan Persetujuan Penggunaan

- [ ] Uji akses tablet lewat hostname yang sama dan sertifikat terpercaya.
- [x] Terapkan document root public, HTTPS, production/debug/cookie PC server;
  pertahankan APP_KEY/data/grant existing. Validasi client LAN tetap pending.
  - Verification: sintaks Apache, readiness production, login dan asset
    PC/tablet; perubahan server tidak mengganggu virtual host project lain.
- [ ] Operator melaksanakan UAT dan printer fisik; agent hanya merekam bukti.
- [ ] Penanggung jawab menyetujui go-live; commit/push hasil yang terverifikasi.

### Inventaris Awal

- Windows hanya menampilkan printer virtual; default Microsoft Print to PDF.
  Printer fisik/model/lebar kertas/koneksi belum dikonfirmasi.
- Tidak ditemukan scheduled task bernama POS untuk backup.
- PHP 8.4.16 dan MySQL/mysqldump 8.0.30 tersedia lewat executable Laragon.
- `https://pos.test/login` mengembalikan HTTP 200 tanpa melewati validasi TLS
  pada PC ini. Ini bukan bukti akses atau trust sertifikat pada tablet.
- Vhost `auto.pos.test.conf` melayani 80/443, tetapi DocumentRoot masih root
  project. Penyesuaian public direncanakan sebelum produksi.
- Tidak mengulang gate backend/frontend karena source runtime belum berubah.

### Hasil Lanjutan

User meminta module baru Platform/OperationalSettings, scope eksplisit format
struk A4/58/80 dan dialog OS, backup opsional/jam WIB/status, tanpa secret/.env/
raw printer dari UI. Implementasi dan QA tercatat pada
[work item module](../../modules/Platform/OperationalSettings/work-items/initial-settings/tasks.md).
Ini pengecualian freeze yang disetujui user, bukan penambahan fitur bisnis lain.

Runbook Windows/LAN dan lembar UAT operator sudah tersedia. Module aktif lokal
batch28 tanpa reset/grant. Task Windows POS-OperationalScheduler buatan agent
sudah dicabut atas arahan user; tidak memasang pengganti. Admin deployment
menyiapkan cron/penjadwal untuk Laravel Scheduler. Backup otomatis memerlukan
opsi aktif di UI DAN pemicu server yang berjalan. Backup/restore terverifikasi,
preferensi/status, preview desktop/
tablet dan rendering PDF tiga ukuran PASS. PDF bukan bukti cetak fisik.

User mengonfirmasi tablet BELUM tersedia. Maka pengujian LAN/TLS tablet,
UAT manusia/printer serta salinan backup di
perangkat terpisah masih pending; checklist tersebut sengaja tidak dicentang.

Pengiriman software/panduan lanjutan: b11cb54 sudah di origin/main;
HEAD/ls-remote identik dan divergence0/0. Ini bukan persetujuan go-live manusia.

### Perapian Menu Harga

- [x] Hapus placeholder Harga dari sidebar dan command palette sesuai user.
- [x] Pertahankan pengaturan harga per satuan/level pada form Barang; tidak
  mengubah backend, data harga atau permission.
- [x] Verifikasi terfokus: assertion source RED/GREEN, ESLint dua komponen,
  typecheck dan build PASS. Chrome DevTools MCP --navigation-only PASS:
  Harga tidak tampil di sidebar/palette, palette tetap terbuka, tablet tanpa
  overlap dan console bersih. Fixture disposable tanpa transaksi; artefak
  ignored mvp-browser-2559ae1ffb84045e. Tidak mengulang QA harga/transaksi.

### Perbaikan UX Navigasi Sidebar

- [x] Reproduksi request hover, kestabilan form aktif dan reset scroll sidebar.
  - Acceptance: hover tidak menavigasi/mengubah draft; sidebar tidak kembali
    ke atas saat memilih modul lain; scroll workspace baru tetap independen.
  - Verification: browser RED/GREEN, network/timeOrigin/draft dan scroll offset.
- [x] Matikan prefetch hover sidebar dan pertahankan offset sidebar ketika
  shell halaman dibuat ulang, tanpa refactor seluruh page/layout.
  - Scope: NavMain/AppSidebar; tidak mengubah business flow, permission, theme
    atau perilaku form modul. Tidak memakai preserveScroll global untuk body.
- [x] Verifikasi expanded desktop dan collapsed tablet, console, lint,
  typecheck/build. Commit/push dilakukan setelah gate ini lulus.

Hasil Chrome DevTools MCP `--sidebar-ux-only`: RED membuktikan request hover
ke Gudang serta reset sidebar 208 -> 0 px (desktop) dan 200 -> 0 px (tablet
collapse). `performance.timeOrigin` dan draft format kertas tetap sama:
prefetch bukan reload dokumen penuh. GREEN tanpa request hover, draft tetap,
offset 208 -> 208 px dan 200 -> 200 px; workspace baru kembali ke atas.
Console bersih dan fixture SQLite disposable tidak memutasi data bisnis atau
pengaturan. Artefak ignored: `mvp-browser-3ea8d188fe4b4eaf`.

ESLint dua komponen, `tsc --noEmit`, `npm run build`, Pint fixture,
`node --check scripts/qa/mvp-checkout-devtools.mjs` dan `git diff --check`
PASS. Tidak mengulang QA transaksi/tema yang tidak berubah. Tidak mengubah
schema, permission, data lokal, Old-docs atau file LSP milik user.

### Dashboard Data Operasional Nyata

- [x] Increment 1: tulis test RED untuk data posted, periode hari ini WIB,
  kondisi kosong, super-system dan batas akses laporan/riwayat kasir.
  - Acceptance: tidak ada angka contoh atau kebocoran lintas kasir; read-only.
- [x] Increment 2: hubungkan dashboard ke Reporting/OperationalReports melalui
  contract laporan dan InvoiceHistoryReader yang ada; tanpa tabel/permission baru.
  - Acceptance: penjualan/pembelian/arus pembayaran hari ini, posisi stok saat
    ini dan lima penjualan terbaru sesuai akses. Total penjualan tidak dikurangi
    retur; refund/uang kembali ditampilkan terpisah. Arus kas bukan saldo.
  - Scope: hapus shift/antrean/tren/persentase gudang dan stok menipis semu;
    tidak membuat aturan stok minimum, shift, grafik atau polling otomatis.
- [x] Increment 3: UI kosong/akses terbatas, refresh manual dan shortcut aktif;
  verifikasi focused PHP, lint/typecheck/build dan browser desktop/tablet.
  - Verification: cocokkan angka fixture dengan UI, refresh, detail invoice,
    tidak ada mock/console error/overflow; data MySQL lokal tidak dimutasi.
- [x] Review scope dan catat hasil; commit/push dilakukan setelah gate lulus.

Hasil: RED lima test gagal karena props data nyata belum tersedia; GREEN
DashboardTest 7 test/157 assertion. Regresi terfokus DashboardTest, laporan
operasional, pengaturan operasional dan login: 24 test/291 assertion PASS.
Route `dashboard` tunggal, web/auth, kini dimiliki controller module Reporting;
nama page/route dan akses shell pengguna login tetap kompatibel.

ESLint dashboard, `tsc --noEmit`, Vite build 2757 module dan Pint scoped PASS.
Chrome DevTools MCP melalui harness existing `--dashboard-only` PASS:
empat angka ringkasan dan nilai stok cocok dengan fixture posted, refresh tombol
dan Alt+R tidak memuat ulang dokumen, data berubah setelah transaksi tambahan
disposable, nomor invoice membuka riwayat terfilter dan bukti existing.
Desktop1440/tablet1024 tanpa overflow halaman, kondisi kosong/akses terbatas,
scope invoice kasir, console bersih serta fingerprint ledger readonly PASS.
Artefak ignored: `mvp-browser-9a5f7f092faa32ae`. Assertion awal empty state
disesuaikan ke textContent karena snapshot memisahkan node teks React; tidak
memerlukan perubahan runtime. Gate frontend/backend lulus dipakai kembali.

Tidak menambah schema, dependency, permission/grant, seeder produksi, polling,
atau runner OS. MySQL lokal, tema, Old-docs dan file LSP user tidak diubah.
Migration/status tidak diperlukan karena schema tidak berubah. Data dashboard
adalah posisi saat dimuat/refresh, bukan stream realtime otomatis.

### Aktivasi Produksi Dan Penerimaan Operator

- [x] Baca runbook aktif dan inventaris konfigurasi; backup/restore lokal
  terverifikasi sebelum perubahan. Tidak mengganti APP_KEY/data/grant.
- [x] Terapkan custom vhost POS ke public dan redirect HTTP ke HTTPS; simpan
  rollback private, uji sintaks sebelum reload, tidak mengubah host lain.
- [x] Aktifkan environment production/debug off, URL HTTPS dan secure cookie;
  cache konfigurasi/route/view, cek readiness dan smoke HTTP/TLS/asset.
- [x] Siapkan folder backup root project (bukan akses web), jadwal22:00WIB
  sesuai user, dan tombol manual backup yang telah dikonfirmasi user.
  - Salinan lokal bukan media terpisah; tidak memasang pemicu OS otomatis.
- [x] Catat bukti aktual; commit hasil software setelah gate lulus. Printer fisik belum
  tersedia menurut user; UAT operator, media eksternal dan tablet tetap pending
  sampai benar-benar diuji/disetujui. Jangan membuat transaksi uji di DB toko.

Readiness --production PASS pada25Oktober2026: env production/debugfalse,
HTTPS/secure cookie, build tanpa Vite, MySQL/migration/identitas dan FIFO.
Config/route/view cache aktif. APP_KEY dan seluruh DB_* identik dengan backup
konfigurasi sebelum aktivasi (nilai tidak dicetak). Apache Syntax OK; user
melakukan Reload karena Apache Laragon bukan service. Custom pos.test.conf
aktif80/443 tanpa vhost POS duplikat, host project lain tidak diubah.
PC HTTPS/login200 dengan validasi TLS normal; HTTP/login301 menuju HTTPS
dengan query terjaga; cookie Secure/HttpOnly/SameSite=lax; /up200, guest
dashboard302, JS/CSS200. /.env, /composer.json, /storage/app/private,
/backups dan URL berkas dump404. Slash akhir private path menghasilkan301
normalisasi sebelum404, bukan akses file. Firewall/DNS/tablet tidak diubah.

Dump sebelum aktivasi private dan dump baru `backups/pos-20261025-115403-a74088fd.sql`
lulus pos:backup --verify: restore disposable identik, DB bisnis tidak ditimpa.
Folder baru ignored Git, dapat diakses File Explorer, bukan off-device.
Preferensi lokal aktif22:00WIB disimpan melalui action/audit anonim CLI sesuai
instruksi user, tanpa mengubah receipt_paper atau grant. Pemicu OS tetap belum
dipasang; schedule:list adalah bukti definisi Laravel, bukan eksekusi periodik.

Backup manual: [rencana/checklist/QA module](../../modules/Platform/OperationalSettings/work-items/manual-backup/tasks.md).
Regresi terfokus30/329 PASS; lint/typecheck/build2758 dan Pint PASS. Sesudah
cache produksi diaktifkan, QA mendapat path cache sendiri: focused6/38 dan
DevTools desktop/tablet PASS, tanpa membersihkan cache/memuat DB produksi.

User kemudian mengonfirmasi login HTTPS dan tombol backup manual produksi
berhasil. Audit succeeded cocok dengan file root backups
pos-20261025-115343-9d118eb8.sql (205869byte). Uji operator awal ini tercatat
pada lembar UAT; tidak menggantikan skenario transaksi, printer atau go-live.
