# Tasks: Setup QZ Dan Kesiapan Perangkat

## Sebelum Coding

- [x] Baca aturan dan baseline module/source yang relevan tanpa Old-docs.
- [x] Catat permintaan pemasangan QZ dan konfirmasi scanner keyboard.
- [x] Cek OS/x64, lokasi QZ standar dan status elevated secara read-only.
- [x] Verifikasi referensi resmi, release/asset/hash installer yang diusulkan.
- [x] Susun PRD, PLAN exact allowlist, efek OS dan rollback.
- [x] PLAN validator/fingerprint dicatat.
  - check_plan.py PASS struktur. SHA256:
    7CEA2517308C91611DC353E1CC2D938CC7952D1B6AF40297BB9DCF15487E1187.
- [x] Approval exact PLAN/source/native installation/Git diberikan user.
  - User menyetujui PLAN dan mengambil alih instalasi lokal secara manual;
    agent tidak menjalankan installer/download/upgrade kedua.
- [x] check_plan --require-approved lulus sebelum download/source/install.

## Increment 1: Setup Reusable Dan Pengalaman Perangkat

- [x] Buat setup Windows sekali-jalan dengan pin/hash/signature dan preflight.
  - Acceptance: no remote-script exec, no service/runner, existing install dan
    privilege ditangani tanpa override; installer belum berjalan selama unit QA.
  - Verification: parse/review dan negative preflight terfokus.
- [x] Implementasikan autodiscovery hanya di workspace Hardware berizin.
  - Acceptance: satu initial attempt, refresh manual, no auto-save/select/print,
    viewer/module lain tidak membuka koneksi.
  - Verification: fake unit dan browser pada gate akhir.
- [x] Lengkapi kesiapan scanner sesi tanpa ketergantungan QZ.
  - Acceptance: input fokus+Enter, nol awal/pembanding, status input jujur;
    tidak merek/device detection atau global keyboard capture.
  - Verification: browser QZ absent/disconnected dan scan sintetis.
- [x] Selaraskan guide/operator/canonical docs dan ADR keputusan setup.
  - Acceptance: Windows setup sekarang, Linux panduan; signing/driver/UAT
    terpisah dan print tetap OFF.
  - Verification: page-guides dan relative links.

## Increment 2: QA, Pemasangan Native Dan Handoff

- [x] Jalankan satu gate source/UI akhir sesuai perubahan.
  - Acceptance: typecheck/lint/unit/build/browser PASS; reuse backend source012
    yang tidak berubah, fingerprint data QA tetap identik.
  - Verification: command/result/artifact ringkas pada REPORT.
- [x] Verifikasi QZ yang dipasang manual oleh user, tanpa installer kedua.
  - Acceptance: installed executable signature/publisher valid, metadata privat;
    agent tidak download, mengubah trust/startup, atau mengklaim hash installer
    manual yang tidak diperiksa. Script klien diuji tanpa install ulang.
  - Verification: Authenticode Valid/QZ Industries, WSS8181 aktif; native
    getVersion2.3.0+1. User mengonfirmasi QZ terpasang/berjalan.
  - Skip beralasan: download/hash installer serta pemasangan/UAC oleh agent
    digantikan instalasi manual user sesuai APPROVAL. Startup user tidak diubah;
    pemeriksaan HKCU tidak menemukan entri, tetapi shortcut startup QZ ditemukan
    dan targetnya sesuai executable. Reboot/login cycle tidak diuji.
- [x] Uji koneksi native WSS dan daftar printer secara read-only.
  - Acceptance: tanpa TLS bypass/DB mutation; daftar OS bukan hardware proof.
  - Verification: native smoke; prompt user bila diperlukan.
- [x] Lengkapi REPORT, validator, allowlist dan status acceptance terpisah.
  - Acceptance: software/native install/native connection/physical dibedakan.
  - Verification: check_report, diff review, checklist berbasis bukti.
- [x] Commit dan push setelah scope selesai serta Git diizinkan.
  - Acceptance: file user tetap terpisah; hash remote cocok.
  - Verification: status/index/diff/log dan ls-remote.

## Tetap Pending Di Luar Paket

- [ ] Driver CT420D dan pairing/OS setup scanner fisik oleh admin.
- [ ] UAT media/gap/ukuran/cetak dan scan-back fisik.
- [ ] Persetujuan pemilik untuk pengiriman print ON.
- [ ] Driver/runtime Linux dan tablet/LAN nyata bila nanti dibutuhkan.

Tidak mencentang physical checklist hanya dari instalasi QZ atau input manual.

## Bukti Sementara

- Guard autodiscovery unit statis RED sebelum implementasi, GREEN sesudahnya;
  runtime behavior diverifikasi pada browser, bukan dibuktikan dari regex saja.
- Typecheck/scoped ESLint PASS; build2975 module PASS; guides34 page PASS;
  barcode-unit dan hardware-label-unit PASS. Initial warning cleanup ref pada
  ESLint diperbaiki tanpa disable rule, kemudian scoped lint lulus.
- Helper parser/platform/arch/hash/signature/no-admin dan existing-install no-op
  PASS, tanpa installer dijalankan. Tidak mengubah ExecutionPolicy/OS.
- Native smoke PASS: storage/app/private/qz-setup-08d4c7d4af43e493/native-smoke.json,
  browser ditutup; versi2.3.0+1 dan6 printer OS, tanpa print.
- Metadata signature/listener privat: qz-setup-2f5698e60c674022aee7a17740f1d0c1.
- Browser final12 cek PASS: hardware-label-409605eb9fc1ba58/report.json;
  fingerprint business/master/grants existing QA identik, server/browser ditutup.
  Screenshot desktop/tablet Light/Dark diperiksa; tidak memakai database toko.
- Helper juga berjalan dalam mode pemeriksaan di Windows PowerShell5.1 tanpa
  install ulang. Cached flags hardware ON/printing OFF; native report URL404,
  TLS valid dan output privat ignored.
- Native startup shortcut menunjuk executable QZ yang benar, diperiksa baca-saja;
  agent tidak mengubah shortcut/registry/trust. Installer baru oleh agent tidak
  dijalankan; native installation diambil alih user sesuai APPROVAL.
- PLAN/REPORT validator, frozen fingerprint dan exact allowlist PASS;
  95 link relatif valid, git diff --check PASS. Regresi backend012 dipakai
  kembali karena tidak ada file backend/contract/transaksi berubah.
- Source d00cb82 dan report aa38d54 dipush; origin/main diverifikasi cocok.
  Empat hash LSP staged identik dan lima LSP untracked tetap dipertahankan;
  tidak ada artifact storage/installer/secret pada commit task.
