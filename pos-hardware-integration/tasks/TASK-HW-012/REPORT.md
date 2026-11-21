# REPORT: TASK-HW-012

Tanggal:21 November2026. Approval exact PLAN version1 dan commit/push diberikan
user. PLAN SHA256 tetap
6F452F95669B224E23FDFEFBCEC5EC3D0836C47C78154CA29B9298F58FFE64FD.
Heading mengikuti validator companion; isi laporan berbahasa Indonesia.

## Scope delivered

Workspace Perangkat POS menyediakan profil printer berversi/soft delete,
discovery printer OS melalui QZ manual, katalog barang+satuan, raster preview,
job idempotent, claim tunggal, outcome, riwayat/pagination, reprint beralasan dan
tes scanner fokus/Enter/scan-back. Software tidak mengidentifikasi merek scanner.
Gateway driver-image dipilih, bukan raw TSPL/CPCL atau printer struk ESC/POS.

Aktivasi aplikasi diselesaikan setelah backup verified:3 tabel Hardware kosong,
4 key permission tanpa auto-grant, cache config/route baru. Workspace:
https://pos.test/platform/hardware. Scanner ON, pengiriman print OFF.

## File changes

Perubahan mengikuti exact allowlist83 path pada PLAN; path opsional yang tidak
dibutuhkan tidak dibuat/diubah. Hardware memegang persistence/actions/routes/
schema/seeder/domain dan frontend colocated. Items menambah public
ItemLabelCatalog/DTOs/adapter dengan binding provider. Helper barcode diekstrak
tanpa mengubah API lama melalui re-export. Shell/shared props hanya availability
dan navigasi bersyarat, bukan SDK atau listener scanner global.

SDK qz-tray2.3.0 saja ditambah ke package.json/lock (--ignore-scripts, LGPL-2.1).
Tidak memperbarui package lain. Canonical module docs, ADR-020, panduan Markdown
operator dan in-app guide diselaraskan. PDF operator lama tidak diregenerasi.
File LSP staged/untracked user tidak disentuh atau dimasukkan commit task.

## Tests

| Pemeriksaan | Hasil |
| --- | --- |
| check_plan.py --require-approved dan SHA256 | PASS; PLAN tidak berubah |
| PHPUnit Hardware feature/unit + ItemLabelCatalogTest |21 test/293 assertion PASS |
| PHPUnit PosCartTest, PosCheckoutTest, InvoiceHistoryTest, ItemsHttpTest |58 test/481 assertion PASS |
| npx --no-install tsc --noEmit | PASS |
| Scoped ESLint hardware/shared helper/shell dan Pint --test file PHP terkait | PASS |
| node scripts/qa/barcode-unit.mjs | PASS; encoding/scan/label existing |
| node scripts/qa/hardware-label-unit.mjs | PASS; layout integer, quiet zone, bounds, image config/host |
| node scripts/qa/page-guides.mjs |34 page PASS |
| npm run build | PASS;2975 module |
| node scripts/qa/hardware-label-browser.mjs |12 cek PASS, console tanpa error tak terduga |
| pos:backup --verify | PASS; restore disposable identik, sumber tidak ditimpa |
| migrate --force exact path; HardwareAccessSeeder --force | PASS; batch37 Ran,4 key baru |
| config:cache; route:cache; route:list | PASS;14 route hardware termasuk scanner existing |
| Fingerprint produksi sebelum/sesudah |69 tabel existing dan seluruh baris permission/migration lama identik |
| HTTPS tanpa bypass TLS | login200, workspace guest302, catalog guest401, ssl_verify_result0, backup404 |
| Dokumen/allowlist | PLAN/REPORT validator PASS;84 link relatif valid; git diff --check PASS |

Red-green dilakukan untuk contract/domain/profile/job. Audit failure rollback,
invalid field/host/token, non-owner, flag OFF, version, repeated claim/outcome,
expired ack dan snapshot diuji. Regresi79 test/774 assertion adalah gabungan
dua gate terfokus di atas, bukan full repository suite.

Browser memakai Chrome/Playwright existing, server PHP localhost production-like
dengan SQLite/cache/storage nonce terisolasi. QZ SDK sebenarnya dipakai tetapi
semua WebSocket QZ diintersep FakeQzGateway test-only. Tidak ada real printer I/O.
MCP DevTools sempat dipakai untuk guest smoke/console bersih; namespace kemudian
tidak tersedia. Alur authenticated memakai fallback Playwright, bukan diklaim
sebagai seluruh QA MCP. Owned page/server/browser sudah ditutup.

Artefak final ignored:
storage/framework/testing/hardware-label-1f955754a7cfea6d/report.json,
before.json/after.json dan screenshot desktop-label/tablet-landscape/
tablet-portrait/tablet-dark.png. Tiga attempt sintetis: submitted/reprint dan
uncertain saat ack terputus. Fingerprint business/master/grants/audit existing
tetap identik. Preview480x240 nonblank dan barcode001234567890 tetap tepat.

Run awal berhenti karena selector mengenai option tersembunyi, pagination
belum selesai sebelum memilih row, lalu role menu tema yang salah pada harness.
Selector/wait diperbaiki; aplikasi tidak dilonggarkan. Gate backend/build yang
source-nya identik tidak diulang. Skrip fingerprint produksi dikoreksi untuk
membatasi listing ke schema koneksi aktif; verifikasi akhir PASS.

## Invariants and regressions

Tidak mengubah transaksi/master toko, stok/FIFO, harga, payment/invoice/receipt,
checkout, akun/role/grant atau .env. Single company/multi-gudang dipertahankan,
tanpa tenant/cabang. Hardware OFF menolak termasuk super-system; auth/permission
dan creator invariant tetap backend. SDK tidak terkoneksi pada login/dashboard.

Backend menyusun snapshot dari public Items contract, menolak raw command dan
field asing. Token claim hanya dikembalikan sekali dan disimpan sebagai hash;
audit tidak memuat token/hash/raw PNG. Duplicate click/claim tidak menggandakan
dispatch. Setelah kemungkinan transmisi, error menjadi uncertain tanpa retry
otomatis. Reprint membuat job baru dengan alasan, bukan mutation transaksi.

## Hardware evidence

Tidak ada perangkat nyata, QZ desktop, driver, Java, service, runner OS, pairing,
firewall atau sertifikat dipasang/diubah. Npm SDK bukan QZ desktop.
Discovery membuktikan protocol software terhadap fake daftar printer, bukan
penemuan CT420D fisik. Input keyboard sintetis bukan bukti USB/dongle/Bluetooth
HC-4208DB. Windows/Linux/Android fisik, driver dan media belum disertifikasi.

## Virtual and physical acceptance

Software acceptance: VIRTUAL_PASSED
Physical acceptance: PHYSICAL_PENDING

Preview/raster, fake transport/ack loss/claim/outcome, permission/OFF dan
scanner input relevan pada VUAT01/02/03/05/06/07/09. VUAT08 diterapkan sebagai
isolasi aktor/job satu instalasi, bukan tenant. Raw TSPL VUAT04 tidak applicable
pada driver-image dan tidak ditandai lulus. Printing production tetap OFF.

## Rollback verification

Backup privat backups/pos-20261121-214732-3e2d347b.sql telah direstore ke database
acak disposable dan fingerprint dibandingkan dengan sumber: PASS. Database
disposable milik command dibersihkan; tidak melakukan restore ke toko.

Dua cache sebelum deployment disalin hash-identik ke
storage/app/private/hardware-label-deploy-46b06cb33d5a7ca8; before.json/after.json
menyimpan hash, bukan baris data pengguna. Backup/snapshot ignored dan bukan
artefak Git. URL backup dan snapshot cache404. Migrasi additive saja; tidak
migrate:fresh/down.

Rollback produksi tidak sengaja dieksekusi karena deployment lulus. Bila perlu,
matikan print, pertahankan histori/audit dan selaraskan source/cache setelah
persetujuan; jangan drop job/data atau mengulang sending. Snapshot cache bukan
bukti rollback source nyata. Scanner existing tidak perlu dimatikan.

## Remaining risks

- QZ/driver CT420D perlu dipasang admin; jalur gambar, ukuran/gap/media dan
  scan-back nyata harus diuji sebelum pilot/print ON. Tidak ada klaim ESC/POS.
- QZ unsigned menampilkan prompt. Silent signing/certificate terpisah; tablet
  perlu PC print-server WSS, host allowlist dan trust admin. Tidak bypass TLS.
- Npm audit masih menemukan8 advisory existing (follow-redirects high, minimatch
  serta rantai typescript-eslint moderate); lock membuktikan bukan dependency
  baru QZ. Tidak npm audit fix/upgrade di luar izin. Tidak mengklaim audit bersih.
- Belum menjalankan full repository suite, driver Linux/Windows fisik, HC-4208DB
  USB/BT/dongle, UAT operator/perangkat, atau regenerasi PDF. Bukan bukti produksi
  hardware siap pakai. Permission role operasional ditetapkan admin tersendiri.

## Final status

COMPLETE untuk implementasi software dan aktivasi aplikasi sesuai approval.
Physical/pilot tetap pending, pengiriman cetak OFF. Penutupan commit/push
dicatat setelah command Git benar-benar berhasil pada checklist task.
