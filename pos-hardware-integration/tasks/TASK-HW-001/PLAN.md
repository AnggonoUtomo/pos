# PLAN: TASK-HW-001

**Status:** DRAFT, MENUNGGU REVIEW PEMILIK.
**Owner:** Pemilik project POS (user).
**Vertical Slice:** VS-HW-01 bagian discovery dan mockup saja.
**Date:** 2026-11-20.
**Version:** 1.

Judul bagian English dipertahankan agar sesuai validator bundle. Isi memakai
Bahasa Indonesia. Task ini bukan rencana implementasi source aplikasi.

## Baseline read-only

Repo `C:\laragon\www\pos`, branch main, HEAD
`1d6ca5bdcf22b569b92125b56cb69eed5a010fc0`. Worktree memiliki empat LSP staged,
lima LSP untracked dan companion untracked sebelum task dimulai. Tidak
menyentuh/stage perubahan user. Rincian source, route, schema declaration,
permissions, tests dan batas pemeriksaan ada di [BASELINE.md](BASELINE.md).

Stack nyata Laravel12/Inertia React19, auth session web/CSRF, modular monolith
hexagonal. Scan HID/barcode per satuan, Code128 label browser, receipt posted,
shift, FIFO dan pembayaran sudah tersedia. Belum ada alasan menggantinya.

Keputusan user pada sesi ini mengoreksi semua asumsi tenant/cabang dalam
bundle: tetap single company + multi-gudang, bukan multibranch atau SaaS baru.
Assignment dan credential perangkat kelak dibatasi backend tanpa tenant/branch
scope. Gudang bukan pengganti cabang. Root aturan dan source tetap authority.

## Scope

Selesaikan satu task dokumentasi: baseline, matriks dampak, mockup workspace
hardware desktop/tablet, PLAN, checklist dan approval PENDING. Semua artefak
berada di folder task ini. Review visual mencakup ringkasan, terminal/printer,
scanner, label, antrean dan panduan; bukan fitur yang sudah berjalan.

Feature flag kelak diusulkan OFF secara global instalasi, bukan per tenant.
Tidak membuat config flag sekarang. Pembuatan app shell, fake executable,
driver, gateway, DB schema, scan/print transaksi nyata, menu global, auto-print,
ESC/POS/drawer/cutter, shipment, offline POS dan migration/grant tidak masuk
scope. Tidak melakukan seed; demo hanya teks fiktif CCTV/kelistrikan di mockup.

Label print/scan-back menjadi vertical slice tersendiri setelah rencana
implementasinya diajukan. Preview software bukan bukti barcode fisik terbaca.

## File allowlist

File existing yang boleh diubah **pada task dokumentasi ini saja**:

- `pos-hardware-integration/tasks/TASK-HW-001/README.md`:
  placeholder discovery diganti index aktual dan koreksi scope dari user.
  Tidak mengubah perilaku runtime.

File baru yang diizinkan sebagai artefak task:

- `pos-hardware-integration/tasks/TASK-HW-001/BASELINE.md`
- `pos-hardware-integration/tasks/TASK-HW-001/IMPACT_MATRIX.md`
- `pos-hardware-integration/tasks/TASK-HW-001/MOCKUP.md`
- `pos-hardware-integration/tasks/TASK-HW-001/MOCKUP.html`
- `pos-hardware-integration/tasks/TASK-HW-001/PLAN.md`
- `pos-hardware-integration/tasks/TASK-HW-001/APPROVAL.md`
- `pos-hardware-integration/tasks/TASK-HW-001/tasks.md`
- `pos-hardware-integration/tasks/TASK-HW-001/mockup-desktop-1366.png`
- `pos-hardware-integration/tasks/TASK-HW-001/mockup-desktop-1920.png`
- `pos-hardware-integration/tasks/TASK-HW-001/mockup-tablet-landscape.png`
- `pos-hardware-integration/tasks/TASK-HW-001/mockup-tablet-portrait.png`

**Exact existing application paths approved for change: NONE.** Tidak ada
allowlist wildcard. Screenshot hanya artefak render lokal, bukan capture
transaksi user. Blueprint asli, root AGENTS, docs aktif dan source di luar
task tidak ditulis. Opsi seam source kelak ada di IMPACT_MATRIX, belum izin.

## Contract and regressions

Kontrak existing yang dijaga: GET pos.scan/catalog, POST pos.quote/invoices,
GET pos.invoices.show; field, auth, CSRF, invoice ownership, status/error dan
retry idempotency tidak berubah. Tidak menambah payload tenant/cabang atau
hardware ke checkout. Snapshot receipt tetap berasal invoice committed.

Task ini tidak mengubah API/data/UI aplikasi sama sekali. Mockup tidak
mengimpor app source, mengakses API, browser storage user atau perangkat.
Tidak ada script submit/fetch/print di HTML; kontrol operasi hanya tampilan.
Pergantian tab/panduan lokal boleh memakai HTML/CSS tanpa server.

Regresi executable feature OFF kelak harus membuktikan nol hardware network
calls/job/listener/IO dan perilaku checkout/scan/receipt identik, termasuk
direct endpoint access. Belum dapat diklaim lulus sekarang. RBAC tetap pada
backend; hardware view tidak memberikan hak melihat invoice kasir lain.

Alternatif terpilih untuk direncanakan: pertahankan HID/window.print existing,
usulkan workspace opt-in terpisah dan harness virtual pada PLAN berikutnya.
Mengubah checkout atau memasang runner bukan solusi task ini.

## Implementation steps

1. Baca aturan root/bundle, catat keputusan user, git baseline dan source
   relevan tanpa bootstrap aplikasi atau koneksi data toko.
2. Tulis baseline/matriks aktual, bedakan asumsi bundle dari kontrak existing.
3. Buat mockup seluruh workspace dan detail status gagal/uncertain. Jangan
   mengganti theme engine existing atau menambahkan UI runtime.
4. Render file HTML lokal pada viewport desktop/tablet memakai browser/tool
   yang sudah tersedia, tanpa install, local web server atau akses pos.test.
   Jika tool tidak tersedia, tandai render BLOCKED dan tetap serahkan wireframe.
5. Jalankan validator struktur PLAN dan check whitespace; catat hasil aktual
   di MOCKUP/tasks. Jangan menjalankan test transaksi, migrasi atau build
   aplikasi untuk perubahan dokumentasi ini.
6. Serahkan baseline + mockup + PLAN kepada pemilik. STOP. APPROVAL tetap
   PENDING. Tidak commit, push, membuat task implementasi lain atau coding.

Urutan/checklist acceptance dan verification rinci ada di [tasks.md](tasks.md).

## Test/UAT

Pemeriksaan yang dijalankan untuk task dokumentasi ini:

```text
python3 pos-hardware-integration/scripts/check_plan.py pos-hardware-integration/tasks/TASK-HW-001/PLAN.md
git diff --check
git status --short
```

Jika python3 adalah alias Windows Store yang tidak tersedia, gunakan Python
existing dengan `python` pada command sama; catat interpreter yang dipakai.
Tidak menginstal interpreter baru. Review relative links dan allowlist artefak.

Render mockup offline 1366x768, 1920x1080, 1024x768 dan 768x1024. Periksa
overflow, teks/aksi dapat terlihat, tab dan Sheet panduan, status error,
request eksternal nol, console error nol. Ini bukan uji Android OS/perangkat.
Chrome DevTools MCP dicoba melalui discovery; bila tool tidak tersedia,
fallback browser existing untuk file mockup saja dicatat terpisah.

Perintah berikut hanya **kandidat setelah PLAN implementasi disetujui**, tidak
dijalankan pada fase dokumentasi:

```text
php artisan test --compact tests/Feature/Modules/Sales/POS
php artisan test --compact tests/Feature/Modules/Inventory/Items/ItemsHttpTest.php tests/Unit/Modules/Inventory/Stock/FifoAllocationTest.php
node scripts/qa/barcode-unit.mjs
npx tsc --noEmit
npx eslint resources/js
npm run build
```

Gunakan cache/testing DB terisolasi sesuai QUALITY; jangan clear cache produksi.
Browser regresi kelak hanya flow berubah serta scan/modal/receipt desktop dan
tablet representatif. Jangan ulang suite source identik. Command migrasi,
seed dan runner browser yang membuat fixture wajib izin execution tersendiri.

## Virtual testing and physical gate

Hardware software acceptance saat ini NOT_RUN. Mockup render PASS, bila
berhasil, tidak boleh dinaikkan menjadi VIRTUAL_PASSED.

Desain pengujian slice implementasi kelak:

- FakeBarcodeInput: fixture Code128 CCTV, leading zero string, Enter, unknown,
  scan sama dua kali, konteks berubah dan fokus modal pembayaran (VUAT01/02).
  Tidak menangkap keyboard global aplikasi user.
- FakePrintTransport: capture byte/snapshot tanpa USB/Bluetooth/serial/socket;
  validasi media/protokol/copies serta mismatch ESC/POS ke CT420D ditolak
  (VUAT04/05). Sintaks menunggu manual otoritatif, bukan tebakan.
- FakeGateway: assignment terminal/printer, revoked credential, lease/duplicate
  claim/idempotency, failure-before-send, lost-ack menjadi uncertain tanpa
  auto-resend (VUAT06/07/08). Tidak membuat service OS.
- Fake/fixture hanya isolated testing/development, tidak dapat aktif pada jalur
  production. Feature OFF diuji termasuk user super-system (VUAT09).
- VUAT08 disesuaikan dengan konfirmasi pemilik: unauthorized device/terminal/job
  ditolak, bukan pengujian tenant/branch. VUAT10 receipt ESC/POS tidak dipakai
  untuk mensertifikasi CT420D.

Physical acceptance tetap PHYSICAL_PENDING: CT420D firmware/driver/USB,
Bluetooth/serial, paper gap/margin/printable width/darkness, scan-back
HC-4208DB dan Android tablet nyata belum diuji. Kelak PG1-PG4 memerlukan
perangkat/manual, cetak label nyata dan kesamaan hasil scan persis, recovery
yang teramati serta persetujuan rollout. Receipt ESC/POS menunggu printer
struk yang benar; bukan prasyarat untuk menyelesaikan dokumen ini.

## Rollback

Tidak ada perubahan state aplikasi, DB, config, service atau deployment untuk
di-rollback. Bila rancangan ditolak, revisi dokumen task; jangan reset git
atau menghapus perubahan user. Tidak memerlukan backup database untuk menulis
dokumen. Hindari menafsirkan ini sebagai izin menghapus companion untracked.

Desain rollback runtime kelak: flag OFF menghentikan semua side effect hardware,
tetap tersedia scan/window.print existing; simpan audit/job historis, jangan
auto-resend job uncertain atau drop tabel populated. Stateful change harus
memiliki backup/restore dan rollback assessment pada PLAN implementasi baru.

## Risk & mitigation

| Risiko | Mitigasi |
| --- | --- |
| Template tenant/branch terbaca sebagai requirement | Keputusan user dicatat pada README/task; tidak membuat scope tersebut |
| MOCKUP disangka fitur aktif | Label DRAFT/SIMULASI, operasi disabled; tidak dilayani Laravel |
| Reuse scan/label menjadi refactor critical path | Tidak ada source allowlist; seam baru wajib revisi PLAN + approval |
| Protocol ack disangka hasil cetak | Pisahkan sent/accepted/uncertain dan physical pending |
| Model CT420D salah diklasifikasikan | Label TSPL/CPCL saja; tidak ada receipt/cutter/drawer/LAN assumption |
| Sintaks/ukuran firmware ditebak | Belum buat bytes; cek manual otoritatif dan physical UAT kelak |
| New gateway membuka akses perangkat lain | Assignment/credential/RBAC milik instalasi, bukan raw command browser |
| Source/user files terganggu | Hanya artefak task; staged LSP tidak disentuh |

STOP bila ada kebutuhan menulis source, install, koneksi perangkat, service,
DDL/seed, perubahan flag produksi atau path di luar allowlist. Revisi dan
minta izin, jangan memakai approval diskusi ini untuk melewati gate.

## Owner approval

[APPROVAL.md](APPROVAL.md) berstatus PENDING. Persetujuan user tentang tidak
memakai tenant/cabang hanya mengesahkan koreksi scope, bukan coding.

Pemilik perlu meninjau baseline, matriks dan mockup. Persetujuan TASK-HW-001
hanya menerima hasil discovery/desain, tidak mengesahkan implementasi source.
Coding berikutnya membutuhkan PLAN exact-file implementasi terpisah, APPROVAL
tertulis berstatus APPROVED, validator `--require-approved`, dan izin terpisah
untuk migration/install/deployment/commit/push sesuai tindakan yang diminta.

Pertanyaan review: apakah susunan workspace pada MOCKUP disetujui, dan apakah
arah slice virtual label/scan-back dapat disiapkan dalam PLAN berikutnya?
Tidak perlu keputusan protokol atau instalasi pada fase ini.
