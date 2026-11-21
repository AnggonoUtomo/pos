# REPORT: TASK-HW-013

Tanggal: 21 November 2026. PLAN version1 SHA256
7CEA2517308C91611DC353E1CC2D938CC7952D1B6AF40297BB9DCF15487E1187 tetap identik.
User menyetujui PLAN lalu mengambil alih pemasangan QZ secara manual.
Heading mengikuti validator companion; isi laporan berbahasa Indonesia.

## Scope delivered

Workspace Hardware melakukan initial discovery satu kali untuk pemegang izin
manage/print, dengan Deteksi ulang manual. Tidak ada polling, auto-pilih printer,
auto-save profil atau auto-print. Viewer tidak membuka koneksi. SDK/socket tidak
ditambahkan di shell/login/dashboard/POS. Tes scanner keyboard otomatis fokus
dan menampilkan Belum diuji/Input diterima serta pembanding kode, tanpa QZ
atau klaim deteksi merek/perangkat USB/HID.

Helper setup Windows x64 sekali-jalan tersedia untuk deployment admin. Mode
default hanya memeriksa; -Install memerlukan admin, arsitektur tepat, tidak ada
existing install/proses, SHA256 dan Authenticode/publisher valid. Versi installer
resmi dipin2.3.0+1. Panduan Windows/Linux dan konfigurasi printer sudah ditulis.
Agent tidak menjalankan installer kedua karena QZ lokal dipasang user.

## File changes

Perubahan hanya path yang diperlukan dalam exact allowlist33 PLAN: hook/panel/
workspace/guide Hardware, harness/unit/fake QZ, helper setup, native smoke,
canonical docs, ADR-021 dan metadata/task013. ADR-020 ditautkan sebagai keputusan
yang digantikan sebagian; PLAN/APPROVAL012 tidak diubah. Path opsional yang
tidak diperlukan tidak dibuat, termasuk adapter/DTOs baru tanpa consumer.

Tidak ada package install/update, lock/config/env/cache produksi, controller,
route, migration/seeder, permission, model atau source transaksi yang berubah.
Empat file LSP staged dan lima untracked user tetap dipisahkan dari Git task.

## Tests

| Pemeriksaan | Hasil |
| --- | --- |
| check_plan --require-approved + fingerprint | PASS, sebelum source |
| Guard statis unit autodiscovery/scanner | RED sebelum implementasi, GREEN sesudah; bukan bukti runtime tunggal |
| node scripts/qa/hardware-label-unit.mjs | PASS; layout/quiet zone/size/copies/host dan guard source |
| node scripts/qa/barcode-unit.mjs | PASS; scan/encoding/nol awal/jumlah/beli/POS existing |
| npx --no-install tsc --noEmit | PASS |
| Scoped ESLint Hardware --max-warnings0 | PASS setelah koreksi cleanup ref tanpa disable rule |
| page-guides.mjs |34 halaman PASS |
| npm run build |2975 module PASS |
| hardware-label-browser.mjs |12 pemeriksaan PASS; console tanpa error tak terduga |
| Parser/preflight helper Windows | PASS: platform/arch salah, hash salah, unsigned, no-admin, existing-install no-op |
| Windows PowerShell5.1 helper default | PASS, tidak download/install ulang |
| qz-native-smoke.mjs | PASS, native QZ2.3.0+1,6 printer OS, tanpa print |
| Executable QZ lokal | Authenticode Valid, publisher QZ Industries, WSS8181 aktif |
| Cached availability | hardware ON, printing OFF; tidak diubah |
| Privasi output | artifact ignored dan URL native report404, TLS valid |
| Dokumen/source scope | PLAN/REPORT validator, fingerprint/allowlist dan diff --check PASS;95 link relatif valid |

Browser final memakai Chrome/Playwright existing, SQLite/cache/view/storage nonce
disposable, server production-like dan FakeQzGateway yang mengintersep SDK QZ
sebenarnya. MCP DevTools tidak tersedia pada discovery tool; fallback dicatat
jujur, tidak memasang ulang MCP/browser/dependency.

Artefak ignored:
storage/framework/testing/hardware-label-409605eb9fc1ba58/report.json,
before.json/after.json dan screenshot desktop-label/tablet-landscape/
tablet-portrait/tablet-dark.png. Dua viewport tablet representatif serta desktop,
F1, pagination, permission, preview PNG nonblank, reprint beralasan dan
uncertain/no auto-retry lulus. Scanner input/fokus/Enter/nol awal/pembanding
tetap lulus setelah gateway fake diputus; koneksi tidak dipolling ulang.
Fingerprint business/master/grants/audit existing QA identik. Server/browser
QA sudah ditutup; tidak memakai database toko untuk fixture.

Script QA negative menggunakan file kosong privat, bukan executable berbahaya.
Lookup existing dimock hanya dalam proses QA untuk mencapai guard no-admin;
tidak menjalankan cabang instalasi. Full installer client dari helper belum
dieksekusi karena user memasang QZ sendiri. Tidak mengklaim end-to-end install
helper atau hash installer manual telah dibuktikan.

Backend/HTTP/transaksi tidak berubah, sehingga hasil gate TASK01279 test/774
assertion dipakai kembali sebagai baseline, bukan dijalankan/diklaim sebagai
test baru task013. Tidak mengulang build/backend source identik setelah lulus.

## Invariants and regressions

Auth/permission backend, flag OFF, owner claim, snapshot, idempotency, reprint
dan conservative outcome tetap existing. Tidak mengubah penjualan/pembelian,
stok/FIFO, harga, pembayaran, akun/role/grant atau receipt. Single company dan
multi-gudang dipertahankan; tidak tenant/cabang. Tidak migration/seed/backup
MySQL ulang karena tidak ada stateful Laravel deployment pada task ini.

Hook membatasi satu attempt awal, mencegah klik paralel dan mengabaikan respons
stale/unmount dengan cleanup. Empty/disconnected/permission-denied dapat dipulihkan
dengan Deteksi ulang. Input scanner tidak menangkap keyboard secara global,
tidak claim hardware, dan tidak bergantung pada koneksi QZ.

## Hardware evidence

User menyatakan instalasi manual selesai dan berjalan, lalu melaporkan tampilan
Gateway terhubung. Native smoke read-only mengonfirmasi API2.3.0+1 dan6 printer
OS melalui WSS tepercaya, tanpa TLS bypass atau operasi print.

Report native privat:
storage/app/private/qz-setup-08d4c7d4af43e493/native-smoke.json.
Metadata executable/signature/listener/startup:
storage/app/private/qz-setup-2f5698e60c674022aee7a17740f1d0c1/installed-state.json.
Shortcut startup QZ ditemukan; target diperiksa baca-saja. Tidak melakukan
reboot/login cycle atau mengubah shortcut/startup/certificate store user.
Agent tidak mengunduh/memasang QZ, Java, driver atau runtime kedua; tidak ada
service/task/runner/firewall/pairing yang ditambahkan oleh agent.

Daftar6 printer OS tidak menyatakan CT420D terpasang/online. Driver CT420D
dipasang user terpisah, dan tidak ada cetak/scan-back fisik atau USB/dongle/BT
scanner diuji pada task ini.

## Virtual and physical acceptance

Software acceptance: VIRTUAL_PASSED
Physical acceptance: PHYSICAL_PENDING

Native installation: PASS, dipasang manual oleh user; signature/API diperiksa.
Native connection: PASS, WSS/read-only printer discovery, tanpa cetak.
Helper installer end-to-end: NOT_RUN; negative/preflight dan existing no-op PASS.
Startup: shortcut diperiksa; reboot/login cycle NOT_RUN.

Fake gateway/label/scanner merupakan bukti software saja. Native QZ bukan bukti
driver/media CT420D atau scanner fisik kompatibel. Printing production tetap OFF.
Raw TSPL/CPCL/ESC-POS dan enumerasi/claim USB/HID tidak menjadi implementasi.

## Rollback verification

Tidak ada installer agent, state OS atau database toko yang perlu dibalikkan.
Preflight membuktikan hash/signature/privilege/arch salah tidak menjalankan
installer; existing installation tidak diupgrade atau dihapus. Tidak mencopot
QZ yang baru dipasang user untuk QA atau membuat snapshot sebelum-instalasi
palsu. Native browser/socket milik QA telah ditutup, QZ user tetap berjalan.

Source disimpan sebagai save point; rollback source hanya perubahan task sendiri
dengan build yang diselaraskan setelah persetujuan. Belum melakukan rollback
source/install nyata. Riwayat job/audit/konfigurasi tetap dipertahankan. File
privat ignored, URL native report404; tidak ada key/config/certificate privat
atau installer di Git. Jangan drop state atau menulis ulang data untuk reset.

## Remaining risks

- Driver CT420D, ukuran/media/gap dan cetak/scan-back fisik belum diuji; print
  OFF sampai persetujuan UAT. Scanner keyboard input bukan deteksi hardware.
- Unsigned QZ masih dapat meminta izin; silent signing dan tablet/LAN WSS tetap
  pekerjaan terpisah. Tidak mematikan dialog/TLS atau mengubah trust user.
- Full install helper di PC klien baru, runtime/driver Linux dan reboot/startup
  belum diuji. Panduan Linux bukan klaim kompatibilitas CT420D di Linux.
- Advisory npm existing yang tercatat TASK012 tidak diubah/ditutup oleh task ini.
  Tidak ada dependency baru atau klaim audit penuh bersih.
- PDF operator global/per-role lama belum diregenerasi; panduan tambahan terbaru
  berupa Markdown dan in-app guide.

## Final status

COMPLETE untuk source, QA software, helper/panduan dan verifikasi native QZ
yang dipasang user, sesuai pengurangan eksekusi OS pada APPROVAL. Implementasi
save point d00cb82; laporan/Git final ditutup setelah verifikasi dokumen.
Physical UAT/print tetap pending/OFF, bukan hardware produksi verified.
