# Tasks: Backup Manual

## Sebelum Mulai

- [x] AGENTS/runbook/module dan persetujuan user dibaca.
- [x] Backup/restore sumber sebelum perubahan PASS; tidak reset akun/ledger.
- [x] Scope folder lokal, tombol manual dan jam22:00WIB ditetapkan user.

## Increment 1: Backend Dan Folder

- [x] Test RED permission, hasil sukses/gagal, audit serta command overlap.
  - Acceptance: hanya manage, exception/output sensitif tidak dibocorkan,
    tidak mengubah jadwal atau business ledger.
- [x] Command menyimpan ke root backups, ignored, lock lintas manual/scheduler.
  - Verification: focused test, command manual dan restore disposable.
- [x] Action/controller/route/status melalui boundary module existing.
  - Acceptance: manual berulang diperbolehkan, bukan replay/sukses jadwal palsu.

## Increment 2: UI Dan Verifikasi

- [x] Tombol manual, konfirmasi, loading, Sonner, status dan shortcut.
- [x] Focused PHP, lint/typecheck/build dan browser desktop/tablet lulus.
- [x] Readiness produksi dan endpoint private/asset/cookie diperiksa.
- [x] Pisahkan path cache config/route PHPUnit dan browser disposable dari
  cache produksi agar QA tidak memuat koneksi/config produksi workspace ini.
- [x] Simpan jadwal lokal22:00WIB sesuai user; pemicu OS tetap pending admin.
- [x] Catat bukti/risiko; commit/push software setelah gate selesai.

UAT manusia, printer fisik, tablet dan media terpisah tidak dicentang otomatis.

## Bukti

RED: endpoint404 dan overlap belum dicegah. GREEN awal17/151, final terfokus
OperationalSettings/CLI backup/dashboard/login/report30 test329 assertion PASS.
Audit veto mencegah executor, error sanitized, payload path ditolak, manual
berulang tidak mengisi jadwal atau ledger bisnis. Pint scoped, ESLint halaman,
tsc --noEmit, build2758 dan node --check harness PASS.

Chrome DevTools MCP --manual-backup-only memakai SQLite disposable dan executor
stub khusus router test (guard env/path SQLite), bukan dump data toko. Konfirmasi,
cancel, Alt+B, status/file aman, audit, viewer tidak melihat tombol, console
bersih serta desktop1440/tablet1024 tanpa overflow PASS. Artefak ignored awal
mvp-browser-35945f6bba3f5806. Sesudah cache produksi aktif, QA cache diisolasi:
focused6/38 dan browser ulang PASS, artefak mvp-browser-f18ac2b849b3b8d5.
Tidak mengulang gate frontend yang source-nya tidak berubah.

Engine nyata diuji melalui CLI pos:backup --verify ke root backups: restore
disposable identik, file nonkosong; bukan klaim sudah dicetak atau disalin ke
perangkat lain. Tidak ada migration/permission/dependency baru. Konfigurasi
server, HTTPS/cookie dan jadwal yang aktif dicatat di checklist MVP.

Batas operasional: request manual sinkron; tool mysql/mysqldump harus tersedia
di environment Apache. Audit hasil tidak menjamin restore/off-device. Backend
mencegah dump bersamaan melalui cache lock; instalasi multi-proses harus memakai
cache bersama (database existing), bukan cache array. Admin tetap menyiapkan
pemicu Laravel Scheduler. UAT operator/printer dan media eksternal pending.
