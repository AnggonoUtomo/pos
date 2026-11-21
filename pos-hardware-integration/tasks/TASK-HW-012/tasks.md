# Tasks: Gateway QZ Dan Konfigurasi Printer/Scanner

## Sebelum Coding

- [x] Baca aturan root/companion, baseline dan dokumen module yang relevan.
- [x] Scope label CT420D + scanner HC-4208DB dikonfirmasi user.
- [x] QZ Tray dan instalasi desktop manual dipilih eksplisit.
- [x] Audit seam source/public contract, barcode helper, permission dan backup.
- [x] Susun PRD/layout, exact allowlist dan pengaman cetak tidak pasti.
- [x] PLAN tervalidasi dan fingerprint dicatat.
- [x] APPROVAL source/SDK/QA/aktivasi diberikan eksplisit.
- [x] check_plan --require-approved lulus sebelum coding; fingerprint tetap sama.

## Increment 1: Konfigurasi Persisten Dan Contract Label

- [x] Pasang SDK yang disetujui, tanpa aplikasi/service/driver OS.
  - Acceptance: hanya qz-tray versi disetujui dan lock terkait berubah.
  - Verification: lock diff, dependency audit; tidak install package lain.
- [x] Buat schema/port/adapter/actions konfigurasi serta permission seeder.
  - Acceptance: ULID, soft delete profil, audit, version conflict, tanpa auto-grant.
  - Verification: focused feature test red-green dan migration SQLite disposable.
- [x] Publikasikan contract katalog label di Inventory/Items.
  - Acceptance: baca identitas semua satuan aktif berbarcode, tanpa private
    import Hardware atau perubahan jual/beli/pricing/stock.
  - Verification: Items label tests; barcode kosong/nonaktif/terhapus ditolak.
- [x] Tambahkan menu/palette bersyarat flag dan izin.
  - Acceptance: OFF tidak membuka fitur/socket; shared availability tidak query DB.
  - Verification: backend OFF/permission serta UI smoke saat gate akhir.

## Increment 2: Alur Label Dan Gateway Utuh

- [x] Buat snapshot/job/attempt, claim sekali, outcome dan reprint beralasan.
  - Acceptance: idempotency, dua tab tidak dispatch sama; uncertain tidak retry.
  - Verification: focused lifecycle/feature tests, invalid token/owner/replay.
- [x] Selesaikan QZ connect/discovery/pilih profil dan renderer preview.
  - Acceptance: image-only, WSS allowlist, printer kosong/offline/izin ditolak
    jelas, barcode tidak dipaksa mengecil atau memilih printer pertama otomatis.
  - Verification: fake gateway/transport dan renderer unit/snapshot tests.
- [x] Lengkapi history/pagination, scanner input/scan-back, shortcuts dan guide.
  - Acceptance: UI dapat dipakai desktop/tablet, tanpa capture keyboard global,
    status gateway bukan klaim fisik dan tanpa placeholder aktif.
  - Verification: TypeScript, scoped lint, PageGuide dan final browser.

## Increment 3: QA Akhir, Aktivasi Aplikasi Dan Handoff

- [x] Jalankan satu gate regresi akhir dan QA browser alur berubah.
  - Acceptance: POS existing utuh, fingerprint existing data/grants tidak berubah,
    simulated jobs/attempts/audit sesuai, software/physical status terpisah.
  - Verification: focused regression, barcode-unit, build, guides, browser
    desktop/tablet, disconnect/ack lost/no auto-retry dan report.
- [x] Bila disetujui, backup --verify sebelum migration/seeder/cache toko.
  - Acceptance: hanya tabel Hardware baru dan4 permission, tidak demo/grant;
    scanner tetap ON, pengiriman print OFF, tanpa QZ/driver OS dipasang.
  - Verification: backup restore-disposable PASS, migration status, no-data-change,
    cached flags/routes dan HTTPS smoke. Jika gagal, aktivasi ditunda.
- [x] Lengkapi ADR/canonical docs/operator, REPORT dan validators.
  - Acceptance: batas driver-image dan langkah manual QZ/cert/UAT dapat dipahami.
  - Verification: check_report, exact allowlist/link/diff review.
- [ ] Commit save point dan push hanya sesuai izin Git eksplisit.
  - Acceptance: file user, backup/QA/secret/cache tidak ikut Git.
  - Verification: status/index user, diff, hash commit dan remote bila dipush.

## Handoff Fisik Yang Ditunda

- [ ] Admin memasang QZ dan driver CT420D sesuai OS secara manual.
- [ ] Pilih printer sebenarnya, periksa media dan cetak test label terkendali.
- [ ] HC-4208DB memindai label dengan kode/leading zero/satuan tepat.
- [ ] Uji driver/ukuran/gap/scan-back dan USB/dongle/Bluetooth yang dipakai.
- [ ] Pemilik menyetujui pilot dan aktivasi flag pengiriman print.

Checklist fisik tidak menjadi selesai dari fake/browser test. Tidak memasang
hardware/driver/service atau menyalakan print production tanpa instruksi.

## Bukti Implementasi Dan Aktivasi

- check_plan.py --require-approved PASS; approval source/SDK/aktivasi/Git eksplisit.
- PLAN SHA256:
  6F452F95669B224E23FDFEFBCEC5EC3D0836C47C78154CA29B9298F58FFE64FD.
- qz-tray2.3.0 terpasang --ignore-scripts; lock hanya menambah SDK tersebut.
- Hardware/domain/catalog:21 test/293 assertion PASS. Regresi POS/Items:
  58 test/481 assertion PASS. Typecheck, scoped ESLint/Pint, barcode-unit,
  hardware-label-unit, build2975 module dan guide34 halaman PASS.
- Browser final12 cek PASS; artefak ignored hardware-label-1f955754a7cfea6d.
  Kegagalan awal berasal dari selector/wait harness, dikoreksi tanpa mengubah
  alur aplikasi. Tidak mengulang gate backend/build yang source-nya sama.
- Backup pos-20261121-214732-3e2d347b.sql: restore disposable identik PASS.
  Snapshot cache privat hardware-label-deploy-46b06cb33d5a7ca8 hash-identik.
- Migration Hardware batch37 Ran, seeder4 permission, config/route cache PASS.
  69 tabel existing dan baris permission/migration lama identik;3 tabel baru
  kosong, role/grant tidak berubah, scanner ON/printing OFF.
- HTTPS login200/workspace guest302/catalog guest401/TLS valid; backup404.
- REPORT/PLAN validator PASS;84 link relatif valid; diff/allowlist review PASS.
  Snapshot cache privat juga mengembalikan404 lewat HTTPS.
- Npm audit belum bersih:8 advisory existing, bukan rantai qz-tray. Tidak
  memperluas update dependency tanpa approval. Detail/batas pada REPORT.
