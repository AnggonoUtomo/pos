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
- [x] Sebelum Increment 3, baca ulang API/module docs, boundary PurchaseOrders,
  source/routes, QA dan status Git; target PurchaseOrders/Returns diumumkan.
- [x] Sebelum Increment 4, baca ulang spesifikasi lifecycle/demo, action,
  repository, routes, model, source lookup dan pola seeder/test; worktree user
  dipastikan tetap di luar scope.
- [x] Batas Increment 4 dipastikan hanya lifecycle draft dan demo dari source
  posted existing; tidak membuat source, receipt, pembayaran, grant atau ledger.
- [x] Sebelum Increment 5, baca ulang panduan UI/Quality, Return API/DTO dan
  query, pola Purchasing/Sales, permission/shortcut/navigasi, formatter angka,
  status Git serta user artifacts; target Purchasing/Returns diumumkan.
- [x] Kontrak UI dikunci: Inertia page berizin view, JSON source lookup manage,
  operasi CRUD draft memakai Laravel/Inertia form, harga asal readonly, dan
  daftar mengambil snapshot dari query yang sama dengan endpoint data.
- [x] QA Increment 5 dibatasi desktop/tablet; Chrome DevTools MCP dicoba lebih
  dahulu, tanpa mengubah data bisnis lokal atau menguji mobile.
- [x] Sebelum Increment 6, baca ulang QUALITY, ARCHITECTURE, FOLDER-STRUCTURE,
  Returns specification/plan/API, native MySQL/activation patterns dan source;
  target Purchasing/Returns diumumkan.
- [x] Database lokal dicek read-only: migration Returns `Pending`; scope aktivasi
  dibatasi ke migration Returns dan permission catalog, tanpa demo/grant.
- [x] QA native dirancang memakai database MySQL random disposable, verified
  `SELECT DATABASE()`, cleanup `finally`, tanpa mencetak koneksi/credential.
- [x] Worktree sesudah push dicek; `SampleUI/` dan artefak LSP tetap excluded.

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

- [x] Publikasikan source lookup/DTOs PurchaseOrders bersama consumer nyata.
  - Acceptance: eligible posted+receipt, projection safe/exact6 dan search bounded;
    tidak bergantung master live atau mengekspor payment/actor/bank/notes sumber.
  - Verification: projection/privacy/source invalid dan master-change tests.
- [x] Buat query/route baca berizin sebagai consumer lookup dan daftar snapshot.
  - Acceptance: data/source lookup dibatasi view/manage sesuai spec; source lookup
    tidak membutuhkan akses repository/CRUD PurchaseOrders/Finance. Catalog
    permission module didaftarkan tanpa grant bersama consumer HTTP ini.
  - Verification: HTTP guest/viewer/manager/outsider, pagination/search literal.
- [x] Implementasikan create draft atomic setelah test red.
  - Acceptance: membership/duplicate/quantity/forgery ditangani server; ID/nomor/
    snapshot/harga/rasio authoritative; satu audit wajib bersama baris/header.
  - Verification: feature create, invalid input dan audit veto/null rollback.
- [x] Implementasikan update/version/no-op tanpa mengganti sumber pembelian.
  - Acceptance: stale ditolak termasuk super-system; harga asal tetap walau
    PO dibayar/master berubah, no-op tidak menambah audit/version.
  - Verification: feature update/tamper/source immutable dan route:list.

## Increment 4: Lifecycle, Audit Dan Demo

- [x] Lengkapi soft delete/restore draft dan version guards.
  - Acceptance: hanya draft dan state aktif/trashed yang sesuai; restore menjaga
    snapshot/baris, stale/ulang/gagal audit tidak meninggalkan mutation parsial.
  - Verification: feature lifecycle, super-system invariant dan audit rollback.
- [x] Uji strict no-op dan fingerprint seluruh source/ledger.
  - Acceptance: urutan key JSON MySQL tidak membuat audit palsu; long decimal dan
    notes "0" tidak hilang; tidak ada perubahan PO/receipt/Stock/FIFO/Finance.
  - Verification: feature regression dan native current-read/precision saat perlu.
- [x] Tambahkan demo draft relasional module dan regresi catalog akses.
  - Acceptance: catalog tetap tanpa grant; demo CCTV/kelistrikan local/testing eksplisit,
    skip sumber kosong, idempotent termasuk edited/trashed, tanpa auto receipt/pay.
  - Verification: access/demo tests, marker lama dan snapshot source unchanged.

- [x] Verifikasi lifecycle, demo dan fingerprint lalu buat scoped commit.
  - Acceptance: checklist Increment 4 hanya dicentang setelah seluruh test,
    audit, seeder, whitespace dan scope staged lulus; user artifacts tetap utuh.
  - Verification: focused/full suite, Pint, route:list, diff check, staged name
    review dan commit otomatis; tidak push sebelum seluruh work item selesai.

## Increment 5: UI Dan Browser

- [x] Buat list/source picker/form/detail/lifecycle lokal.
  - Acceptance: shadcn/Lucide/Sonner, quantity Indonesia, harga asal readonly,
    tidak ada tombol/status yang mengklaim posting stok atau refund supplier.
  - Verification: typecheck/ESLint/build dan browser CRUD/restore/decimal.
- [x] Lengkapi permission, shortcut dan navigasi.
  - Acceptance: usePermission/isSuperSystem hanya UX, dropdown di atas workspace,
    nav/palette existing; Ctrl/Cmd+Enter bekerja dari dialog, Esc saat aman.
  - Verification: keyboard, tooltip, viewer readonly, outsider403 dan backend tests.
- [x] Lengkapi loading/empty/error/stale/retry dan request race.
  - Acceptance: abort sumber lama tidak menimpa pilihan baru, double submit dicegah,
    422/error mempertahankan koreksi tanpa sukses palsu; konflik butuh reload.
  - Verification: DevTools offline/500/retry/race/double-submit dan batas kuantitas;
    backend HTTP 403/422/stale, invalid source dan error form.
- [x] Jalankan matriks desktop/tablet dan inspeksi screenshot.
  - Acceptance: 1440x1000,1024x768,768x1024 Light/Dark expanded/collapsed tanpa
    overlap/body overflow; console/network tak terduga kosong. Mobile out of scope.
  - Verification: DevTools MCP dahulu; alasan dan fallback existing bila blocked.

- [x] Perbaiki temuan aksesibilitas input dan tutup QA Increment 5.
  - Acceptance: setiap field input punya identitas stabil `id` atau `name`; tidak
    ada warning/error runtime baru; seluruh hasil QA tercatat di bawah.
  - Verification: Chrome DevTools issue scan, typecheck/ESLint/build, focused test,
    `git diff --check`, dan review scope sebelum commit.

## Increment 6: Native QA, Aktivasi Dan Delivery

- [x] Verifikasi schema/precision/version/audit pada MySQL native disposable.
  - Acceptance: unique/FK/up-down, lock/current read/stale dan rollback terbukti;
    draft tidak mengubah source/Stock/Finance. Cleanup semua DB/worker/server QA.
  - Verification: skrip native dibuat saat implementasi, output/status dicatat.
- [x] Jalankan regresi dan review contract/layer/privacy.
  - Acceptance: focused/full tests, TS/lint/build/Pint relevan lulus; tidak ada
    import privat atau migration global, tidak menambah dependency/branch.
  - Verification: command plan, review diff dan boundary/privacy.
- [x] Siapkan dan jalankan aktivasi lokal additive dua kali setelah QA lulus.
  - Acceptance: hanya migration/catalog, tanpa grant/reset/demo/receipt/pay;
    kolom/data existing/role/grant/source/Stock/Finance/audit identik.
  - Verification: uji aktivasi disposable lebih dahulu, migrate:status dan fingerprint.
- [x] Tutup docs/API/module index dan delivery keseluruhan.
  - Acceptance: status sesuai bukti, checklist dicentang sesudah verified;
    scoped commit/push, SHA remote sama HEAD, divergence0/0, user files excluded.
  - Verification: link/whitespace/staged review, push/ls-remote/rev-list/status.

## Sesudah Tahap Dokumentasi

- [x] Semua dokumen baru dan link dibaca/diperiksa sesuai source.
- [x] Scope staged hanya dokumentasi, whitespace dan pola secret diperiksa.
- [x] Status Increment 1 dan module checklist diperbarui setelah verifikasi.

## Sesudah Coding Keseluruhan Work Item

- [x] Semua acceptance draft terpenuhi, tanpa posting/reservasi/refund.
- [x] Unit/feature/native/full regresi dan fingerprint ledger lulus.
- [x] Typecheck/lint/build/scoped Pint lulus sesuai perubahan.
- [x] Route dan migration/status serta aktivasi aman terverifikasi.
- [x] DevTools QA Increment 5 PASS; Increment 6 tidak mengubah UI.
- [x] Demo aman; tidak dijalankan saat aktivasi lokal.
- [x] Dokumen aktual, risiko, scoped commit/push dan remote verified.

## Hasil Verifikasi

| Pemeriksaan | Hasil | Catatan |
| --- | --- | --- |
| Review source/template/scope (Increment 1) | PASS | Baseline pola received penuh dan presisi6 ditemukan; tidak ada source Returns pembelian atau published lookup yang diklaim tersedia. |
| Generator dry-run dan scaffold | PASS | `php artisan module:make Purchasing Returns --dry-run --with-tests`; provider, Routes, Database/Migrations, Seeders dan test scaffold ditemukan. Tidak ada route bisnis. |
| Domain quantity dan schema disposable | PASS | `php artisan test --compact tests/Unit/Modules/Purchasing/Returns tests/Feature/Modules/Purchasing/Returns tests/Feature/Console/MakeModuleCommandTest.php`: 78 tests, 139 assertions. Quantity exact6, batas, unique/FK, soft delete marker, down populated dan source fingerprint lulus. |
| Regresi penuh (Increment 2) | PASS | `php artisan test --compact`: 712 tests, 5628 assertions. |
| Pint dan route/status | PASS | Pint scoped lulus setelah format; route:list tidak menemukan route Returns; migrate:status menunjukkan migration baru Pending. Migration tidak dijalankan pada database lokal. |
| Lookup source dan draft backend | PASS | `php artisan test --compact tests/Feature/Modules/Purchasing/Returns tests/Feature/Modules/Purchasing/PurchaseOrders/ReturnPurchaseLookupTest.php tests/Unit/Modules/Purchasing/Returns`: 87 tests, 218 assertions; source/privacy/receipt/header+line consistency/search, HTTP permission/catalog no-grant, exact snapshot, audit veto/no-op/version lulus. |
| Regresi penuh (Increment 3) | PASS | `php artisan test --compact`: 724 tests, 5727 assertions. |
| Route Increment 3 | PASS | `php artisan route:list --path=purchasing/returns`: data, purchases, purchase, store, update; GET root belum ada sampai UI Increment 5. |
| Lifecycle, version dan audit (Increment 4) | PASS | Focused Returns + PurchaseOrders source lookup: 28 tests, 216 assertions; delete/restore, stale/state, permission, super-system invariant dan audit rollback. |
| Demo dan fingerprint (Increment 4) | PASS | Demo returns eligible CCTV/kelistrikan existing, no-source skip, edited/trashed idempotency, no grants; source/receipt/Stock/FIFO/Finance/master/audit-source fingerprint identik. |
| Regresi penuh (Increment 4) | PASS | `php artisan test --compact`: 733 tests, 5800 assertions. |
| Pint dan routes (Increment 4) | PASS | Pint scoped lulus; `route:list --path=purchasing/returns` menunjukkan 7 route termasuk DELETE dan restore. |
| UI, permission dan browser flow (Increment 5) | PASS | Chrome DevTools isolated context: create `1,5`, detail readonly, edit `1,25`, soft delete, filter trashed, restore; revisi dan Sonner sesuai. Viewer hanya baca, shortcut dropdown/command palette tampil, Ctrl/Cmd+Enter menyimpan dan Esc menutup. |
| Validasi/error dan concurrency (Increment 5) | PASS | Kuantitas di atas sumber ditolak dan koreksi tetap ada; double-click hanya membuat satu draft. Offline dan HTTP 500 menampilkan error, retry pulih. Respons lookup pertama ditunda, sumber kedua dipilih, lalu respons lama tidak mengganti pilihan. HTTP 403/422/stale dicakup feature tests. |
| Matriks desktop/tablet (Increment 5) | PASS | Light/Dark, sidebar expanded/collapsed, 1440x1000, 1024x768, 768x1024; lebar dokumen sama viewport, tabel scroll internal, dialog tablet di dalam viewport. Tidak ada temuan console error/warning pada reload final. |
| Aksesibilitas input (Increment 5) | PASS | DevTools menemukan input pencarian tanpa `id`/`name`; ditambahkan `id="return-search" name="search"`. Reload final: 0 input tanpa identitas dan 0 console error/warning/issue. |
| Typecheck/lint/build (Increment 5) | PASS | `npx tsc --noEmit`, ESLint scope UI, dan `npm run build` lulus setelah perbaikan aksesibilitas. |
| Test HTTP/focused (Increment 5) | PASS | `php artisan test --compact tests/Feature/Modules/Purchasing/Returns`: 26 tests, 219 assertions; `php artisan test --compact`: 734 tests, 5824 assertions setelah perubahan UI. |
| Fixture dan cleanup (Increment 5) | PASS | SQLite disposable hanya di `storage/framework/testing`; fingerprint stock/FIFO/payment sebelum dan sesudah sama (`7235da28...fc74`). Server port 18767 dan file SQLite QA telah dihentikan/dihapus. |
| MySQL native disposable (Increment 6) | PASS | `php scripts/qa/purchase-returns-mysql.php`: native migration up/down/up, DECIMAL(20,6), stale current-read pada REPEATABLE READ, audit rollback, unique/FK, populated rollback guard, fingerprint PO/receipt/Stock/FIFO/Finance/audit sumber; database QA dibersihkan. |
| Aktivasi disposable (Increment 6) | PASS | `php scripts/qa/activate-purchase-returns-mysql.php`: baseline terisi tanpa Returns, aktivasi dua kali, hanya satu migration dan dua permission catalog; existing data/schema/role/grant/audit tidak berubah, tabel Returns kosong, database QA dibersihkan. |
| Aktivasi lokal (Increment 6) | PASS | `php scripts/qa/apply-purchase-returns-local.php` dua kali: pertama migration selesai, kedua `Nothing to migrate`; tanpa demo/grant/reset/receipt/payment. `migrate:status` menampilkan migration Returns `Ran`; tabel draft kosong. |
| Regresi Increment 6 | PASS | `php artisan test --compact`: 734 tests, 5824 assertions. `npx tsc --noEmit`, `npx eslint resources/js`, `npm run build`, `php vendor/bin/pint --test` dan `php -l` ketiga skrip QA lulus. |
| Routes/status Increment 6 | PASS | `php artisan route:list --path=purchasing/returns` memuat 8 route; migration module terdaftar `Ran`. |
| Diff/docs/scope (Increment 6) | PASS | `git diff --check`, validasi link dokumen, staged scope review; `SampleUI/`, `Old-docs/` dan artefak LSP dikecualikan. |
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

## Handoff Increment 3

PurchaseOrders kini menerbitkan ReturnPurchaseLookup/ReturnPurchaseData dari
snapshot order/baris yang receipt metadata-nya cocok. Returns memakai boundary
tersebut untuk lookup, list data, create dan update draft. Validasi source,
membership, kuantitas, permission, version/no-op dan audit satu transaksi diuji;
PO/receipt/Stock/FIFO/Finance tidak berubah. Route Inertia root dan lifecycle
delete/restore belum dibuat. Migration lokal tetap Pending.

## Handoff Increment 4

Draft kini dapat dihapus lunak dan dipulihkan hanya dengan version aktif yang
sesuai; status non-draft ditolak termasuk super-system. Kedua lifecycle
mempertahankan snapshot/baris dan mencatat audit atomik. Demo Returns hanya
mencari PO posted existing melalui published lookup, lalu membuat draft
bermarker untuk baris CCTV dan kelistrikan yang tersedia; tanpa source/baris
relevan ia skip. Marker edited/trashed tidak ditimpa. Test membuktikan no-op
dengan key snapshot yang diurut ulang, quantity decimal6 dan notes string "0",
serta fingerprint PO/receipt/Stock/FIFO/Finance/master pembayaran/audit sumber
yang tidak berubah oleh seeder. Migration lokal tetap Pending; UI dan QA native
belum masuk scope increment ini.

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

## Handoff Increment 6

- Perubahan: tiga skrip QA reusable untuk native MySQL, aktivasi lokal terbatas,
  dan pengujian aktivasi pada database disposable; dokumen module/API/quality
  diselaraskan dengan route dan hasil runtime.
- Verifikasi native: schema DECIMAL(20,6), up/down/up, lock/current-read stale,
  audit veto rollback, unique/FK dan rollback populated lulus. Fingerprint
  PurchaseOrders/receipt/Stock/FIFO/Finance serta audit asal tidak berubah.
- Aktivasi: baseline disposable terisi diaktifkan dua kali; hanya migration dan
  dua permission catalog bertambah. Aktivasi lokal dijalankan dua kali; tidak
  memberi grant atau membuat demo/receipt/payment. Migration lokal kini `Ran`,
  tabel draft tetap kosong.
- Regresi: 734 tests/5824 assertions, TypeScript, ESLint, build, Pint, route dan
  status lulus. QA desktop/tablet tetap PASS dari Increment 5; UI tidak berubah.
- Batas tersisa: posting/pengiriman retur, perubahan stok/FIFO, utang dan refund
  supplier belum diimplementasikan dan memerlukan work item/keputusan terpisah.
- Delivery: increment disimpan dan dipush setelah scope staged, commit serta
  remote SHA diverifikasi; `SampleUI/`, `Old-docs/` dan artefak LSP tidak masuk.
