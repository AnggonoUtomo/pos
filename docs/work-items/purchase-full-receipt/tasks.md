# Tasks: Penerimaan Pembelian Penuh

## Sebelum Mulai

- [x] AGENTS/README/ARCHITECTURE/FOLDER-STRUCTURE dan module terkait dibaca.
- [x] Target lintas PurchaseOrders/Stock diinformasikan, full receiving disetujui.
- [x] Query saldo inner join opening dan FK nullable yang diperlukan dipetakan.
- [x] Boundary public DTO/contract, lock order, replay, snapshot/audit ditetapkan.
- [x] Finance/partial/reversal bukan scope, aktivasi tidak menambah stok user.

## Increment 1: Spesifikasi

- [x] Tulis PRD/contract/ADR/checklist sebelum coding.
  - Acceptance: receipt bukan opening/payment, rollback tidak membuang posted ledger.
  - Verification: review/link/diff; tidak membuat module/port tanpa consumer.

## Increment 2: Inventory/Stock

- [x] Tulis RED writer/precision/integrity/replay/query lalu implementasi.
  - Acceptance: tanpa outer transaction ditolak, saldo baru/additive exact,
    consistency layer/saldo dan overflow, source/line unique, replay sama no-op,
    beda ditolak, satu movement/layer/baris, audit veto rollback.
  - Verification: focused tests RED/GREEN, legacy saldo/movement dan sale FIFO.
- [x] Schema ledger/FK/query purchase-only.
  - Acceptance: nullable opening anchor, metadata fallback, legacy tidak hilang;
    rollback posted ditolak, base unit/master guard tetap berlaku.
  - Verification: SQLite migration up/down disposable, Pint/boundary/commit.

## Increment 3: Purchasing/PurchaseOrders

- [x] Posting action/HTTP/version/snapshot/replay atomic.
  - Acceptance: full saja, harga/qty authoritative, ratio berubah ditolak,
    stale/trashed/posted mutation ditolak, kedua audit wajib, Finance tidak berubah.
  - Verification: RED/GREEN, inject failure sesudah receipt, retry/replay dan CSRF.
- [x] Permission post dan filter/status.
  - Acceptance: view/manage bukan post, post-only bukan view, super tetap invariant;
    query posted snapshot readonly, endpoint payload whitelist version saja.
  - Verification: HTTP matrix/route:list/Pint dan commit.

## Increment 4: UI Dan Demo

- [x] Tombol berizin, dialog konfirmasi, badge/filter/detail posted readonly.
  - Acceptance: Sonner/loading/error, data tetap saat gagal, shortcut dialog,
    tidak ada kesan lunas atau aksi edit/hapus pada posted.
  - Verification: TS/lint/build, isolated browser screenshot/console/network.
- [x] Seeder demo posting eksplisit dan fixture relasional.
  - Acceptance: CCTV/kelistrikan, local/testing, idempotent posted tidak diubah,
    tidak otomatis mem-posting data user atau menambah Finance.
  - Verification: demo/replay tests dan browser desktop1440/tablet1024/768,
    Light/Dark expanded/collapsed, detail/total/footer framing dan commit.

## Increment 5: QA Dan Delivery

- [x] Full test/build/Pint/audits dan native MySQL.
  - Acceptance: duplicate/concurrent post dan receipt-vs-sale tidak lost update,
    FK/posted-down guard, rollback lintas module dan legacy reports terbukti.
  - Verification: disposable native QA/barrier, full gates; bukan klaim SQLite lock.
- [x] Aktivasi lokal hanya schema/catalog.
  - Acceptance: seluruh master/account/grant/stock/finance/audit lama identik;
    tidak seed/post draft user, script ulang idempotent.
  - Verification: guarded helper/fingerprint dua kali dan migrate:status.
- [x] Dokumentasi aktif/API/MODULES/QUALITY dan review.
  - Acceptance: tidak lagi menyatakan draft-only/anchor opening wajib; hasil aktual.
  - Verification: link/diff/staged/secret/exclusion review dan commit.
- [ ] Push dan buktikan remote.
  - Verification: HEAD=ls-remote, divergence0/0, user untracked tetap terpisah.

## Sesudah Coding

- [x] Acceptance/test/native/browser/gates lulus sesuai bukti.
- [x] Aktivasi dan ledger lama aman; risk/non-scope dicatat.
- [ ] Checklist/commit/push selesai dan terverifikasi.

## Bukti Command

- Increment1: scope/PRD/ADR013/boundary/checklist dan relative links direview;
  git diff --check PASS. Tidak ada module baru, generator tidak relevan.
- Increment2: receipt writer RED6 test (DTO/action belum ada), GREEN6/28;
  regresi Stock40 test/298 assertion dan draft/checkout terkait25/254 PASS.
  Saldo purchase-only/legacy, additive setelah sale, FIFO old-first, hash replay/
  source-line conflict, audit veto, corrupt balance/overflow dan posted down
  guard teruji. Pint PASS. Regresi schema JSON key order diperbaiki dengan
  pembandingan terstruktur, tidak mengubah data/production behavior.
- Increment3: RED5 posting tests (route/action belum ada), GREEN22/207 bersama
  draft/HTTP/receipt terkait. Header/line snapshot refreshed, ratio/stale/master/
  trashed guard, dua audit rollback/retry, replay no-op sesudah supplier nonaktif,
  posted readonly/down guard, Finance tidak berubah, permission matrix+CSRF dan
  whitelist versi PASS. Pint/7 route PASS. UI belum diverifikasi pada checkpoint.
- Increment4: demo RED3 (seeder belum ada), GREEN3/14. Typecheck, ESLint seluruh
  resources/js, build2723 module dan Pint PASS. DevTools MCP BLOCKED: tool tidak
  tersedia pada sesi ini; fallback Playwright existing tanpa dependency baru.
  Browser receipt PASS: stale dua tab, receiver view/post tanpa manage, replay,
  detail readonly, filter, saldo/movement purchase-only, Finance tetap kosong.
  Desktop1440/tablet1024/768 Light/Dark expanded/collapsed, dialog/total/footer
  tidak terpotong; console kosong. Screenshot konfirmasi768 dan saldo direview.
  Artefak storage/framework/testing/purchase-receipt-browser-276d5c6316c8c338.
  Regresi draft browser juga PASS, console kosong; artefak
  storage/framework/testing/purchase-drafts-browser-fdf050aa443efb10.
- Increment5: full backend463 test/3666 assertion PASS; scoped Pint, typecheck,
  ESLint resources/js, build2723, npm audit0 dan composer audit --locked bersih.
  php scripts/qa/purchase-full-receipt-mysql.php PASS: tiga barrier LOCK WAIT
  worker nyata (double posting, receipt dahulu vs sale, sale dahulu vs receipt),
  receipt3 bukan ganda, saldo5/nilai320000 old-first, audit posted3.
  Audit Purchasing veto rollback Stock, source-line unique/FK restrict, empty
  native up/down mempertahankan legacy, kedua posted down ditolak, query saldo/
  kartu dan Finance0 PASS. Database QA dibersihkan, database user tidak dimutasi.
  php scripts/qa/apply-purchase-receipt-local.php dua kali PASS fingerprint
  seluruh kolom/data existing; catalog tanpa grant/seed/posting user. Migration
  Stock000003 batch16 dan PurchaseOrders000004 batch17 Ran. Review kode final
  tidak menemukan blocker pada scope; limit master1000/partial/reversal/utang
  tetap di luar scope. DDL tidak dijanjikan rollback bersama transaction catalog.
  route:list7 PASS, relative link dokumen dan git diff --check PASS. Stage hanya
  source/QA/dokumen pekerjaan; Old-docs/SampleUI/empat LSP/secret tidak diikutkan.
