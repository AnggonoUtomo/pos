# Tasks: Fondasi Draft Retur Pembelian

## Sebelum Mulai

- [x] AGENTS.md, docs/README.md, ARCHITECTURE.md dan FOLDER-STRUCTURE.md dibaca.
- [x] Workflow/template module/spec/work item/plan/tasks dibaca.
- [x] Scope dan non-scope dikonfirmasi user; target Purchasing/Returns diumumkan.
- [x] Source/schema snapshot PurchaseOrders dan pola draft Sales/Returns ditinjau.
- [x] Worktree dicek; file user dan arsip dikecualikan dari pekerjaan.
- [x] Presisi6 pembelian, source received penuh dan efek payment version dicatat.
- [x] Dampak schema/route/permission/UI/demo/audit/soft delete/ledger ditentukan.
- [x] QA automated/native/browser/aktivasi aman direncanakan.
- [x] Scope tahap ini hanya dokumentasi, tidak coding atau DB/runtime change.
- [x] Sebelum Increment 2, baca ulang source/generator/QA dan status Git terkini.

## Increment 1: Baseline Dokumentasi

- [x] Tambahkan dokumen module mengikuti template aktif.
  - Acceptance: README/specification/plan/tasks membedakan rencana dan runtime;
    tidak mengklaim route/contract/schema sudah tersedia.
  - Verification: review template, source dan konfirmasi scope user.
- [x] Tulis work item beserta increment dan checklist sebelum coding.
  - Acceptance: setiap task punya acceptance/verifikasi, dependency berurutan,
    posting/FIFO/uang supplier bukan scope yang terselip.
  - Verification: review README/plan/checklist work item.
- [x] Hubungkan module index dan roadmap penyedia source.
  - Acceptance: Returns tetap Planned; PurchaseOrders tidak diklaim sudah punya
    published lookup, dan Stock/Finance bukan dependency mutation draft.
  - Verification: review module index/plan dan link relatif.
- [x] Verifikasi dokumentasi dan buat scoped commit save point.
  - Acceptance: link/whitespace lulus, semua perubahan hanya docs, tidak ada
    user files/Old-docs ikut stage. Tidak push saat work item belum selesai.
  - Verification: link scan, git diff --check, staged scope/status dan commit.

## Increment 2: Scaffold, Schema Dan Kuantitas

- [x] Cocokkan preview generator lalu generate module canonical.
  - Acceptance: app/Modules/Purchasing/Returns berisi provider/Routes/Database
    Migrations/Seeders serta tests yang benar; tidak membuka route dummy publik.
  - Verification: module:make --dry-run, --with-tests dan scaffold discovery test.
- [x] Kunci schema draft/reference/decimal dan kebijakan rollback populated.
  - Acceptance: ULID/soft delete/version/JSON/unique per draft, bukan unique
    baris sumber lintas draft; migration tetap di module, tidak merusak sumber.
  - Verification: schema tests dan up/down pada DB disposable, review spec.
- [x] Tulis red-green aturan quantity/base quantity murni.
  - Acceptance: positif <= source, max14/6, exact base scale6, invalid/overflow/
    nonexact ditolak; tidak float atau import framework/Domain module lain.
  - Verification: unit quantity, batas besar dan kasus meter/rol/kemasan.

## Increment 3: Sumber Dan Alur Simpan

- [ ] Publikasikan source lookup/DTOs PurchaseOrders bersama consumer query nyata.
  - Acceptance: eligible posted+receipt, projection safe/exact6 dan search bounded;
    tidak bergantung master live atau mengekspor payment/actor/bank/notes sumber.
  - Verification: projection/privacy/source invalid dan master-change tests.
- [ ] Buat query/route baca berizin sebagai consumer lookup dan daftar snapshot.
  - Acceptance: index/data/lookup dibatasi view/manage sesuai spec; source lookup
    tidak membutuhkan akses repository/CRUD PurchaseOrders/Finance. Catalog
    permission module didaftarkan tanpa grant bersama consumer HTTP ini.
  - Verification: HTTP guest/viewer/manager/outsider, pagination/search literal.
- [ ] Implementasikan create draft atomic setelah test red.
  - Acceptance: membership/duplicate/quantity/forgery ditangani server; ID/nomor/
    snapshot/harga/rasio authoritative; satu audit wajib bersama baris/header.
  - Verification: feature create, invalid input dan audit veto/null rollback.
- [ ] Implementasikan update/version/no-op tanpa mengganti sumber pembelian.
  - Acceptance: stale ditolak termasuk super-system; harga asal tetap walau
    PO dibayar/master berubah, no-op tidak menambah audit/version.
  - Verification: feature update/tamper/source immutable dan route:list.

## Increment 4: Lifecycle, Audit Dan Demo

- [ ] Lengkapi soft delete/restore draft dan version guards.
  - Acceptance: hanya draft dan state aktif/trashed yang sesuai; restore menjaga
    snapshot/baris, stale/ulang/gagal audit tidak meninggalkan mutation parsial.
  - Verification: feature lifecycle, super-system invariant dan audit rollback.
- [ ] Uji strict no-op dan fingerprint seluruh source/ledger.
  - Acceptance: urutan key JSON MySQL tidak membuat audit palsu; long decimal dan
    notes "0" tidak hilang; tidak ada perubahan PO/receipt/Stock/FIFO/Finance.
  - Verification: feature regression dan native current-read/precision saat perlu.
- [ ] Tambahkan demo draft relasional module dan regresi catalog akses.
  - Acceptance: catalog tetap tanpa grant; demo CCTV/kelistrikan local/testing eksplisit,
    skip sumber kosong, idempotent termasuk edited/trashed, tanpa auto receipt/pay.
  - Verification: access/demo tests, marker lama dan snapshot source unchanged.

## Increment 5: UI Dan Browser

- [ ] Buat list/source picker/form/detail/lifecycle lokal.
  - Acceptance: shadcn/Lucide/Sonner, quantity Indonesia, harga asal readonly,
    tidak ada tombol/status yang mengklaim posting stok atau refund supplier.
  - Verification: typecheck/ESLint/build dan browser CRUD/restore/decimal.
- [ ] Lengkapi permission, shortcut dan navigasi.
  - Acceptance: usePermission/isSuperSystem hanya UX, dropdown di atas workspace,
    nav/palette existing; Ctrl/Cmd+Enter bekerja dari dialog, Esc saat aman.
  - Verification: keyboard, tooltip, viewer readonly, outsider403 dan backend tests.
- [ ] Lengkapi loading/empty/error/stale/retry dan request race.
  - Acceptance: abort sumber lama tidak menimpa pilihan baru, double submit dicegah,
    422/error mempertahankan koreksi tanpa sukses palsu; konflik butuh reload.
  - Verification: browser controlled403/422/500/network, invalid source dan retry.
- [ ] Jalankan matriks desktop/tablet dan inspeksi screenshot.
  - Acceptance: 1440x1000,1024x768,768x1024 Light/Dark expanded/collapsed tanpa
    overlap/body overflow; console/network tak terduga kosong. Mobile out of scope.
  - Verification: DevTools MCP dahulu; alasan dan fallback existing bila blocked.

## Increment 6: Native QA, Aktivasi Dan Delivery

- [ ] Verifikasi schema/precision/version/audit pada MySQL native disposable.
  - Acceptance: unique/FK/up-down, lock/current read/stale dan rollback terbukti;
    draft tidak mengubah source/Stock/Finance. Cleanup semua DB/worker/server QA.
  - Verification: skrip native dibuat saat implementasi, output/status dicatat.
- [ ] Jalankan regresi dan review contract/layer/privacy.
  - Acceptance: focused/full tests, TS/lint/build/Pint relevan lulus; tidak ada
    import privat atau migration global, tidak menambah dependency/branch.
  - Verification: command plan, review diff dan boundary/privacy.
- [ ] Siapkan dan jalankan aktivasi lokal additive dua kali setelah QA lulus.
  - Acceptance: hanya migration/catalog, tanpa grant/reset/demo/receipt/pay;
    kolom/data existing/role/grant/source/Stock/Finance/audit identik.
  - Verification: uji aktivasi disposable lebih dahulu, migrate:status dan fingerprint.
- [ ] Tutup docs/API/module index dan delivery keseluruhan.
  - Acceptance: status sesuai bukti, checklist dicentang sesudah verified;
    scoped commit/push, SHA remote sama HEAD, divergence0/0, user files excluded.
  - Verification: link/whitespace/staged review, push/ls-remote/rev-list/status.

## Sesudah Tahap Dokumentasi

- [x] Semua dokumen baru dan link dibaca/diperiksa sesuai source.
- [x] Scope staged hanya dokumentasi, whitespace dan pola secret diperiksa.
- [x] Status Increment 1 dan module checklist diperbarui setelah verifikasi.

## Sesudah Coding Keseluruhan Work Item

- [ ] Semua acceptance draft terpenuhi, tanpa posting/reservasi/refund.
- [ ] Unit/feature/native/full regresi dan fingerprint ledger lulus.
- [ ] Typecheck/lint/build/scoped Pint lulus sesuai perubahan.
- [ ] Route dan migration/status serta aktivasi aman terverifikasi.
- [ ] DevTools QA PASS atau BLOCKED beralasan dengan fallback terverifikasi.
- [ ] Demo aman atau skip beralasan; tidak dijalankan saat aktivasi user.
- [ ] Dokumen aktual, risiko, scoped commit/push dan remote verified.

## Hasil Verifikasi

| Pemeriksaan | Hasil | Catatan |
| --- | --- | --- |
| Review source/template/scope (Increment 1) | PASS | Baseline pola received penuh dan presisi6 ditemukan; tidak ada source Returns pembelian atau published lookup yang diklaim tersedia. |
| Generator dry-run dan scaffold | PASS | `php artisan module:make Purchasing Returns --dry-run --with-tests`; provider, Routes, Database/Migrations, Seeders dan test scaffold ditemukan. Tidak ada route bisnis. |
| Domain quantity dan schema disposable | PASS | `php artisan test --compact tests/Unit/Modules/Purchasing/Returns tests/Feature/Modules/Purchasing/Returns tests/Feature/Console/MakeModuleCommandTest.php`: 78 tests, 139 assertions. Quantity exact6, batas, unique/FK, soft delete marker, down populated dan source fingerprint lulus. |
| Regresi penuh | PASS | `php artisan test --compact`: 712 tests, 5628 assertions. |
| Pint dan route/status | PASS | Pint scoped lulus setelah format; route:list tidak menemukan route Returns; migrate:status menunjukkan migration baru Pending. Migration tidak dijalankan pada database lokal. |
| Whitespace | PASS | `git diff --check` lulus. |
| Link/whitespace (Increment 1) | PASS | Node memeriksa 10 dokumen dan 22 link lokal, tanpa trailing whitespace; git diff --check lulus. |
| Staged scope/save point (Increment 1) | PASS | Sepuluh file docs terfokus, tanpa source/user/artifact/arsip atau pola credential. Scoped commit dibuat; tidak push work item yang belum selesai. |
| Backend/typecheck/browser (Increment 1) | SKIPPED | Increment 1 hanya dokumentasi. Browser QA desktop/tablet tetap wajib Increment 5. |

## Handoff Increment 2

Generator canonical menghasilkan scaffold Purchasing/Returns. Schema header/baris
mengunci ULID, snapshot, version, soft delete header, FK restrict dan unique
baris per draft; rollback berisi draft ditolak. ReturnQuantity menjaga decimal
tanpa float dan konversi exact6. Verifikasi test menggunakan SQLite disposable;
migration lokal terbukti Pending dan tidak dijalankan. Route bisnis belum dibuat.
Increment 3 berikutnya mulai published source lookup hanya bersama consumer nyata.

## Handoff Increment 1

Baseline empat dokumen module dan tiga dokumen work item mengikuti template
aktif; module index serta roadmap/checklist PurchaseOrders menghubungkan source
lookup yang direncanakan. Tidak ada PHP/JS/schema/route/seeder/DB change.
Scope draft vs ledger, presisi6, version PO vs draft dan demo aman sudah eksplisit.
Tidak membuat ADR/PRD tambahan karena scope jelas dan tidak mengambil keputusan
posting/FIFO/refund di luar approval. Implementasi memulai Increment 2, bukan
menganggap dokumen ini bukti fitur dapat digunakan. Commit otomatis setelah
review staged selesai; tidak push sampai work item selesai atau diminta.

Jangan menambahkan scope di luar persetujuan user. Commit otomatis setiap
task/increment verified; push hanya saat seluruh work item selesai atau diminta.
