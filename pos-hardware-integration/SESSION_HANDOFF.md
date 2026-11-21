# Handoff Integrasi Hardware POS

**Diperbarui:** 21 November 2026.

## Kondisi Aktual

Repo C:\laragon\www\pos telah diperiksa. TASK-HW-001 discovery/mockup disetujui
dan dipush pada5e3b4a51ca3da1689ee224ffc393f6971b564984. User mengoreksi bundle:
single company + multi-gudang, tidak menggunakan tenant atau cabang.

User kemudian menyetujui PLAN TASK-HW-002 version1 SHA256
665F6076563C5F7355B8FBE433894A89D0728ED7797C532F365732204861DFAA.
Implementasi Platform/Hardware scanner virtual selesai; user telah mengizinkan
commit/push sebagai save point berikut pada 21 November 2026.
Empat file LSP staged dan lima untracked user tetap dipertahankan.

## Yang Sudah Berjalan Dalam QA

- Halaman /platform/hardware/scanner dan resolve read-only; public Items
  SaleItemCatalog, auth/web/verified existing, inventory.view, rate limit.
- Feature/env middleware: OFF atau production selalu404, termasuk super-system
  dan compiled-route collection. Tidak mengubah .env atau cache produksi.
- Input fokus/Enter, nol awal, repeated scans, serial queue maksimal20,
  hasil sementara20, timeout/cancel/stale, shortcut, Sonner dan PageGuide.
- Tanpa persistent data/migration SQL; HardwareDemoSeeder no-op. Fixture
  CCTV/kelistrikan hanya SQLite QA, tidak data toko.
- Regresi69 test/680 assertion PASS, focused checks sesudah koreksi test/
  harness PASS; typecheck/lint/build2959, guides33 PASS.
- Browser final12 cek PASS: storage/framework/testing/
  hardware-scanner-87953e352ba32224/report.json; fingerprint71 tabel identik.
- MCP guest smoke PASS/console bersih; ekspor artefak MCP ditolak workspace
  roots tool. Authenticated browser dan screenshot melalui Playwright existing.
- Browser test dan server PHP sementara sudah ditutup, tanpa service OS baru.

## Batas Yang Tetap Berlaku

Software VIRTUAL_PASSED hanya scanner; physical PHYSICAL_PENDING. HC-4208DB
USB/dongle/Bluetooth, Android nyata dan CT420D label TSPL/CPCL/media/scan-back
belum diuji. Tidak ada receipt ESC/POS/cutter/drawer/LAN CT420D yang diklaim.
Fake gateway/print transport/command builder belum dibuat pada slice scanner.
Production tetap tertutup, tidak ada pilot atau perangkat dipasang.

PLAN tidak diubah sesudah approval; status aktual di APPROVAL/tasks/REPORT.
Seluruh source existing, dependency, role/grant toko, FIFO/payment/invoice/
receipt tidak diubah. Detail: [REPORT](tasks/TASK-HW-002/REPORT.md).

## Lanjut Dari Sini

Selesaikan commit/push TASK-HW-002 tanpa file user, lalu susun PLAN akses
produksi sesuai permintaan terbaru. Jangan menganggap approval
TASK-HW-002 memberi izin task lain, install, service, live migration/seed,
produksi, root AGENTS atau refactor source existing. Baca exact allowlist sebelum
melanjutkan. Jangan membaca/mengubah Old-docs. Jangan mengulang QA source yang
sudah lulus tanpa perubahan atau temuan baru.
