# Tasks: Pengaturan Operasional

## Sebelum Mulai

- [x] AGENTS, README, arsitektur, folder canonical dan CompanySettings dibaca.
- [x] User menyetujui scope printer browser/format dan backup opsional/jam/status.
- [x] Target Windows/Laragon LAN; secret/.env tidak diubah via UI.
- [x] Staged/untracked LSP user dan Old-docs dikecualikan.

## Increment 1: Fondasi Dan Runner Backup

- [x] Generate module canonical dan migration/seeder milik module.
  - Acceptance: ULID, default backup mati, tidak ada reset/grant otomatis.
  - Verification: generator dry-run, test migration/default/idempotent seed.
- [x] Settings version/permission/audit dan jadwal due yang aman.
  - Acceptance: validasi allowlist, no-op tanpa audit ganda, stale ditolak;
    sekali sukses/hari WIB, retry terbatas, overlap dicegah, error sanitized.
  - Verification: focused tests update/403/rollback/disabled/due/retry/lease.
- [x] Adapter memanggil pos:backup existing; scheduler didaftarkan module.
  - Acceptance: tidak ada dump dari request web; gagal tampak sebagai gagal.
  - Verification: executor fake dalam test; schedule:list dan command smoke.
- [ ] Commit increment terverifikasi.

## Increment 2: UI Dan Cetak

- [x] Form pengaturan, status backup, shortcut, Sonner dan permission guard.
  - Acceptance: desktop/tablet, save conflict jelas, jam WIB, toggle opsional.
- [x] Opsi A4/58/80 tersambung ke receipt dan preview tanpa membuat transaksi.
  - Acceptance: dialog browser tetap dipakai; struk thermal terbaca dan tidak
    memotong nominal/nama panjang; invoice/stock/payment tidak berubah.
  - Verification: focused frontend/HTTP tests dan browser print/screenshot.
- [x] Sidebar/palette/module index/API/dokumen operasional diselaraskan.
- [ ] Commit increment terverifikasi.

## Increment 3: Aktivasi Dan Handoff

- [x] Runner Windows dengan path tool absolut, default dry-run instalasi,
  tanpa secret di argumen dan tanpa overwrite task lain.
- [x] Aktivasi migration dan katalog permission lokal dengan backup terlebih dulu.
  - Acceptance: tanpa grant/password/reset data; backup otomatis tetap mati.
- [ ] Gate akhir terfokus, catat command/bukti dan commit/push diverifikasi.
- [x] Catat instalasi task aktual terpisah dari preferensi jadwal dan UAT fisik.

## Bukti

- Generator dry-run dan generate canonical PASS; route:list dua endpoint dan
  schedule:list pos:backup-due setiap menit PASS.
- RED sebelum implementasi: endpoint/class belum ada. GREEN module: 11 test /
  96 assertion PASS; defaults/403/version/no-op/allowlist/audit rollback,
  shared paper-only props, due WIB, retry/lease/fencing/limit/seed/adapter.
- Regresi terfokus middleware/POS/history/auth/dashboard bersama module:
  52 test / 384 assertion PASS. CompanySettings/PosReadiness/BackupPos juga
  sudah PASS pada gate sebelumnya; tidak mengulang suite seluruh MVP.
- Typecheck, ESLint source terdampak, Pint scoped dan build Vite6.4.5 /
  2757 module PASS. Formatting scoped tidak mengubah behavior.
- Chrome DevTools MCP executable existing, profile isolated dan SQLite
  disposable: --operational-only PASS; save/reload paper+jadwal, preview
  58/80/A4, desktop1440/tablet1024, console bersih dan DB tanpa transaksi/
  stock movement/backup run. Artefak ignored mvp-browser-b27a5a0aa4293573.
- Perbaikan harness InputTime: gunakan role InputTime dan event keyboard native,
  karena fill Puppeteer untuk time input tidak memberi notifikasi ke React.
  Ini bukan perubahan validasi aplikasi atau pengulangan workflow transaksi.
- Print-to-PDF memakai Playwright existing khusus rendering print (bukan
  koneksi printer): ketiga MediaBox width sesuai, portal saja tercetak,
  file PDF nonkosong dan tanpa overflow PASS. Tidak mengganti QA MCP utama.
  Artefak ignored mvp-browser-8ca36420a0011ac0.
- pos:backup --verify PASS: pos-20261024-173701-1a3c879c.sql, sumber/restore
  identik, target disposable dibersihkan, database bisnis tidak ditimpa.
- activate-operational-settings-local.php PASS; fingerprint data/grant/ledger
  existing identik. Migration Ran batch28; default backup tetap mati.
- Installer dry-run dan -Install PASS; POS-OperationalScheduler Ready,
  principal Limited/Interactive, manual tick LastTaskResult=0. Hasil ini
  membuktikan runner, bukan dump otomatis saat preferensi masih mati.
- pos:readiness lokal PASS. Tidak mengedit .env, vhost, firewall, DNS, cert,
  grant/password, Old-docs atau staged/untracked LSP milik user.
- User menyatakan tablet belum tersedia. UAT operator/printer fisik, akses
  tablet/TLS, media backup terpisah dan production activation tetap pending
  pada [checklist operasional](../../../../../work-items/mvp-ready/tasks.md).
