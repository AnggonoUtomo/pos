# PLAN: TASK-HW-013

Status: DRAFT; tidak memberi izin source atau instalasi sebelum APPROVAL.
Version: 1. Tanggal: 21 November 2026.

## Baseline read-only

Repo C:\laragon\www\pos, main pada
dee23b8306fcf90b157fb3fba27323b0df396d41; TASK-HW-012 selesai/dipush.
Empat LSP staged dan lima LSP untracked user tetap dipertahankan.
Root/companion AGENTS, arsitektur/folder, docs module/ADR-020, gateway hook,
panel printer/scanner, approval012 dan referensi deployment resmi dibaca.
Tidak membuka Old-docs. Single company/multi-gudang, bukan tenant/cabang.

SDK qz-tray2.3.0 sudah ada. Platform/Hardware memiliki schema/permission,
preview dan job lifecycle; cetak OFF, diagnostik ON. QZ desktop tidak ditemukan
pada dua lokasi Program Files standar atau proses QZ saat discovery. PC x64;
shell sekarang bukan elevated administrator. Installer default memerlukan
persetujuan UAC; agent tidak dapat menganggap instalasi akan otomatis berhasil.

Printer discovery saat ini manual Cari printer. Scanner sudah menerima input
fokus+Enter, belum menampilkan status kesiapan sesi. User meminta QZ dipasang
di sini, dan memilih "Langsung scan + tes kesiapan", bukan daftar USB/HID.
Tidak menulis ulang PLAN/APPROVAL012; perubahan keputusan dicatat pada task baru.

## Scope

Satu paket: setup QZ Windows sekali, pemeriksaan koneksi/daftar printer otomatis
satu kali saat membuka workspace Hardware berizin, deteksi ulang manual, serta
status tes scanner keyboard yang tidak bergantung pada QZ. Panduan deployment
Windows dan Linux menjelaskan batas setup klien. Tidak menambah backend API,
schema, dependency, permission atau perubahan transaksi.

Native installer yang diusulkan adalah asset resmi qzind/tray tag v2.3.0,
qz-tray-2.3.0+1-x86_64.exe, ukuran metadata142008512 byte, SHA256
01a8ae33da31c78f386ef8335024723f2dd2cb7d8bc44ce33079bf6bc0360011.
URL tetap:
https://github.com/qzind/tray/releases/download/v2.3.0/qz-tray-2.3.0%2B1-x86_64.exe.
Metadata release/asset diperiksa read-only dari GitHub resmi saat perencanaan.
Hash dan Authenticode Valid/publisher QZ harus dicek lagi setelah download;
hash/signature/publisher tidak sesuai berarti berhenti, bukan override.

Native QZ menyimpan konfigurasi/sertifikat TLS lokalnya sendiri dan dapat
mendaftarkan trust certificate QZ serta startup login bawaannya. Ini efek
installer OS yang diminta persetujuan eksplisit, bukan perubahan Laravel.
Tidak memasang sertifikat untuk silent message signing, mengubah sertifikat
pos.test, mematikan TLS/security prompt, atau membuat service/task/runner.

## File allowlist

Path relatif root, tanpa wildcard source. Yang belum dibutuhkan tidak dibuat.
Sebelum approval hanya dokumen task baru dan metadata companion boleh berubah.

1. `resources/js/pages/platform/hardware/gateway/qz-gateway.ts`
2. `resources/js/pages/platform/hardware/gateway/qz-tray.d.ts`
3. `resources/js/pages/platform/hardware/hooks/use-qz-gateway.ts`
4. `resources/js/pages/platform/hardware/components/gateway-panel.tsx`
5. `resources/js/pages/platform/hardware/components/scanner-test-panel.tsx`
6. `resources/js/pages/platform/hardware/index.tsx`
7. `resources/js/pages/platform/hardware/index.guide.ts`
8. `resources/js/pages/platform/hardware/types.ts`
9. `scripts/qa/hardware-label-unit.mjs`
10. `scripts/qa/hardware-label-browser.mjs`
11. `tests/Browser/Hardware/fake-qz-gateway.mjs`
12. `scripts/qa/qz-native-smoke.mjs`
13. `scripts/operations/setup-qz-windows.ps1`
14. `docs/user-guide/SETUP-PERANGKAT.md`
15. `docs/user-guide/PRINTER-LABEL.md`
16. `docs/user-guide/UJI-SCANNER.md`
17. `docs/user-guide/README.md`
18. `docs/modules/Platform/Hardware/README.md`
19. `docs/modules/Platform/Hardware/specification.md`
20. `app/Modules/Platform/Hardware/README.md`
21. `docs/decisions/ADR-020-QZ-LABEL-GATEWAY.md`
22. `docs/decisions/ADR-021-SETUP-QZ-DAN-KESIAPAN-PERANGKAT.md`
23. `docs/DECISIONS.md`
24. `pos-hardware-integration/tasks/TASK-HW-013/README.md`
25. `pos-hardware-integration/tasks/TASK-HW-013/PRD.md`
26. `pos-hardware-integration/tasks/TASK-HW-013/PLAN.md`
27. `pos-hardware-integration/tasks/TASK-HW-013/APPROVAL.md`
28. `pos-hardware-integration/tasks/TASK-HW-013/tasks.md`
29. `pos-hardware-integration/tasks/TASK-HW-013/REPORT.md`
30. `pos-hardware-integration/STATUS.md`
31. `pos-hardware-integration/NEXT_TASK.md`
32. `pos-hardware-integration/SESSION_HANDOFF.md`
33. `pos-hardware-integration/TASK_INDEX.md`

Output build biasa public/build dan QA ignored root nonce
storage/framework/testing/hardware-label-{nonce} diizinkan setelah approval.
Output download dan snapshot OS/setup hanya root privat
storage/app/private/qz-setup-{nonce}; installer/key/log tidak di-commit/dipublikasi.
Tidak mengubah .env, bootstrap cache produksi, root AGENTS, npm/Composer lock,
module POS lain atau dokumen historis task012.

Efek OS yang diminta, hanya melalui installer QZ resmi pada Windows sekarang:
file program/runtime di C:\Program Files\QZ Tray, state QZ pada %APPDATA%\qz
dan %PROGRAMDATA%\qz, temporary installer yang dikelola installer, entri QZ
uninstall/protocol/shortcut/startup login serta trust certificate TLS QZ pada
certificate store yang dikelola installer. Bukan izin mengubah entri/cert milik
aplikasi lain. Tidak edit registry/certificate store secara generik. Jika
installer memerlukan scope berbeda yang material, berhenti dan minta arahan.

## Contract and regressions

| Contract | Dipertahankan/perubahan sempit |
| --- | --- |
| HTTP/auth/CSRF/permission Hardware | Tidak berubah |
| Hardware enabled/printing flags | Tidak berubah; print tetap OFF |
| QZ WSS host allowlist | Tidak berubah, tanpa WS fallback |
| Snapshot/claim/outcome/reprint | Tidak berubah; tidak test-print fisik |
| Workspace Hardware | Satu initial connect/discovery berizin; manual refresh setelahnya |
| Login/dashboard/POS/module lain | Tidak menambah SDK/socket/listener di sana |
| Scanner keyboard | Fokus/Enter, status sesi jujur; tanpa QZ/USB claim |
| Business/master/role/grant | Tidak ada mutation atau migration/seed |

Initial discovery hanya jika user memiliki manage atau print. Viewer tidak
menjalankan QZ. Tidak connect saat module OFF, tidak melakukan auto-save profil,
auto-pilih printer pertama atau auto-print. Batasi satu attempt per mount,
hindari duplicate async/StrictMode/host-change/unmount race dan retry loop.
Kegagalan koneksi tetap dapat diperbaiki dengan Deteksi ulang; permission QZ
dapat meminta klik user. Hasil nol printer adalah empty state, bukan error.

Status scanner dibatasi sesi halaman: Belum diuji/Input diterima, serta kode
Sesuai/Berbeda bila ada pembanding. Tidak menyatakan device terhubung/model
tertentu dari keystroke. Input fokus saat tab scanner aktif, tanpa listener
global atau penangkapan password/input lain. Status bukan bukti UAT hardware.

## Implementation steps

1. Validasi PLAN fingerprint dan --require-approved sebelum source/download.
   Implementasikan setup Windows admin sekali-jalan, pinned release/hash dan
   Authenticode. Existing installation dideteksi dulu; jangan upgrade/uninstall
   versi lain tanpa izin, jangan menjalankan installer dua kali secara paralel.
   Tidak memakai remote-script pipe-to-exec atau mengganti ExecutionPolicy.
2. Implementasikan discovery workspace satu kali, refresh manual dan tes
   scanner sesi. Pertahankan semua invariant/boundary Hardware. Uji software
   dengan fake gateway tanpa native/print I/O; dokumentasi/ADR diselaraskan.
3. Setelah gate software lulus, snapshot metadata QZ/startup/trust yang relevan
   secara privat, download/verifikasi installer, lalu jalankan default setup
   dengan izin UAC user. Gunakan Start-Process -WindowStyle Hidden untuk helper;
   dialog UAC Windows dapat muncul. Jika elevated shell/consent tidak tersedia,
   tandai pemasangan BLOCKED dan sediakan langkah admin, jangan bypass.
4. Setelah instalasi berhasil, jalankan aplikasi tray resmi dan verifikasi
   versi/process, startup QZ, koneksi WSS tepercaya serta daftar printer OS
   secara read-only. Native smoke menggunakan browser owned dan SDK existing,
   bukan akun/transaksi toko. User dapat perlu menyetujui prompt koneksi QZ;
   tidak menulis allowed.dat atau membypass prompt secara otomatis.
5. Lengkapi REPORT dan metadata dengan status nyata: software, native setup,
   native connection, physical device acceptance terpisah. Commit save point
   setelah gate relevan dan push setelah paket selesai bila izin Git diberikan.
   Jika ada blocker OS, source yang lulus boleh committed; jangan klaim native
   setup/physical lengkap atau push paket sebagai complete secara keliru.

## Test/UAT

Focused QA selama coding dan satu gate akhir pada alur yang berubah. Reuse
bukti backend TASK012 selama file backend/contract/transaksi tidak berubah.
Tidak mengulang gate POS79 test hanya untuk edit UI/installer.

- Unit fake: satu discovery saat mount, no global connect, no retry loop,
  empty/permission rejected/disconnect dan refresh manual, WSS allowlist.
- Browser existing Chrome/Playwright atau MCP jika tersedia: desktop dan
  tablet representatif, izin viewer, preview/history/reprint unchanged,
  scanner Enter/nol awal/input manual/pembanding, fokus dan QZ unavailable
  tidak memblokir scanner. Fake transport tidak menjadi real print request.
- Setup script parse/inspection: hash salah, signature salah, arsitektur
  salah, existing install dan no-admin berhenti aman; tidak mengeksekusi
  installer untuk menguji error path. Tidak menurunkan ExecutionPolicy.
- npx tsc --noEmit, scoped ESLint, hardware-label-unit, barcode-unit,
  page-guides, build, git diff --check dan allowlist/link/validator.
- Native gate setelah installer: versi file/QZ API, WSS tanpa bypass trust,
  printer list read-only, tray/startup saja tanpa console reload. Tidak
  membuat profil/job atau transaksi pada database toko.
- Tidak install dependency npm/Composer atau menutup advisory existing012.
  Tidak ada schema change sehingga migration/seed dan backup MySQL ulang
  bukan kebutuhan task ini. Pemulihan file/state QZ memakai snapshot privat.

## Virtual testing and physical gate

Software VIRTUAL_PASSED hanya bila fake/browser gate lulus. Native desktop
installation dan native WSS diberi PASS/BLOCKED/NOT_RUN tersendiri, bukan
digabung sebagai physical printer verified. Printer list dapat berisi PDF
virtual; jangan menjadikannya bukti CT420D terpasang/cetak/scan-back berhasil.

CT420D dan HC-4208DB nyata tetap PHYSICAL_PENDING. Tidak menyalakan flag print,
memasang driver, mengirim test label, pairing Bluetooth atau membuka LAN.
Driver Linux, tablet dan media/gap/scan-back fisik tetap pending. Scanner
keyboard tidak membutuhkan QZ; ketikan sintetis tetap bukan bukti scanner.

## Rollback

Source dapat dikembalikan hanya perubahan task sendiri setelah persetujuan;
build diselaraskan, database dan data transaksi tidak direstore/ditulis ulang.
Jika setup gagal, hentikan hanya proses milik setup, simpan laporan privat dan
identifikasi instalasi parsial. Jangan menghapus Program Files/state/cert
secara rekursif atau uninstall QZ existing yang bukan dibuat task.

Sebelum setup catat existing installation/startup/QZ cert metadata. Penghapusan
instalasi baru bila diminta dilakukan melalui uninstaller resmi setelah
persetujuan, bukan wildcard cleanup/trust-store reset. Jangan menganggap exit
code uninstall sebagai bukti sukses tanpa pemeriksaan. Riwayat Hardware dan
audit Laravel tetap dipertahankan. Tidak melakukan uninstall/reinstall hanya
untuk QA pada PC toko. Pemasangan native tidak dianggap atomik; jika UAC
ditolak atau gagal, laporkan state nyata dan langkah admin yang diperlukan.

## Risk & mitigation

| Risiko | Pengaman |
| --- | --- |
| Shell tidak elevated | UAC oleh user; blocked bila ditolak, tanpa bypass/password |
| Installer palsu/berubah | Pin release+SHA256, signature/publisher valid; fail closed |
| Perubahan trust/startup mengejutkan | Efek installer resmi eksplisit pada approval, snapshot privat |
| Gangguan konsol seperti runner lama | Tray startup bawaan QZ; tidak task/service/PowerShell berulang |
| Prompt berulang karena unsigned | Jelaskan trust/signing terpisah, tidak menjanjikan silent |
| Autodiscovery membuka socket global | Hanya mount Hardware berizin, satu attempt, cleanup |
| Scanner keyboard disebut detected | Status input/session saja, tanpa merek/USB claim |
| Printer ditemukan dianggap siap | Pilihan eksplisit dan physical gate terpisah |
| Setup klien disalahpahami | QZ tetap dipasang sekali per PC gateway; Linux guide bukan runtime PASS |
| Scope bertambah | Tidak backend, transaksi, driver, LAN, print ON atau package upgrade |

## Owner approval

Pilihan scanner sudah eksplisit, tetapi exact PLAN baru belum disetujui.
[APPROVAL.md](APPROVAL.md) PENDING. Persetujuan yang diminta satu paket:
source allowlist, script setup reusable Windows, installer resmi OS berikut
trust TLS/startup bawaannya pada PC ini, QA software/native read-only/build,
canonical docs/ADR, commit dan push setelah paket lulus. Linux hanya panduan.
Tidak termasuk driver/service/task/firewall/signing, backend/data atau print ON.

Rujukan primer: [Deployment](https://qz.io/docs/deployment),
[Getting Started](https://qz.io/docs/getting-started),
[release2.3.0](https://github.com/qzind/tray/releases/tag/v2.3.0).
