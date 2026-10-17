# Tasks: Pembayaran Pembelian Lunas

## Sebelum Mulai

- [x] AGENTS/README/ARCHITECTURE/FOLDER-STRUCTURE dan source terkait dibaca.
- [x] Scope lunas sesudah terima penuh dikonfirmasi user.
- [x] Module target, boundary, presisi, non-scope dan lock order ditetapkan.
- [x] Worktree user dipisahkan; tidak menyentuh arsip atau memasang dependency.

## Increment 1: Spesifikasi

- [x] Tulis scope/contract/plan/checklist sebelum coding.
  - Acceptance: ledger pengeluaran terpisah dari kas masuk, total exact6,
    tidak mengklaim saldo kas/utang, existing tidak otomatis lunas.
  - Verification: review manual/link dan git diff --check.

## Increment 2: Finance/Payments

- [x] Ledger/writer dan settlement lunas exact.
  - Acceptance: transaction caller, jumlah tepat, metode unik/aktif/compatible,
    replay no-op dan payload beda ditolak, audit wajib, down/FK aman.
  - Verification: RED/GREEN focused, precision/audit/guard master/regresi sale.
- [x] Guard akun/metode mencakup pengeluaran.
  - Acceptance: histori immutable melindungi konfigurasi; rename tidak ubah snapshot.
  - Verification: lifecycle tests/Pint dan commit.

## Increment 3: Purchasing/PurchaseOrders

- [x] Schema pointer/snapshot/pay action dan endpoint.
  - Acceptance: posted saja, total server, version/replay, audit kedua rollback,
    stock/FIFO identik, posted lama belum dicatat, permission pay terpisah.
  - Verification: RED/GREEN HTTP/permission/CSRF/whitelist/stale, route dan commit.

## Increment 4: UI Dan Demo

- [x] Dialog bayar, badge/filter dan detail readonly snapshot.
  - Acceptance: Indonesia exact6, Sonner/error/loading, shortcut/dropdown,
    tanpa aksi bayar lagi pada paid atau edit posted.
  - Verification: TS/lint/build/browser desktop1440/tablet1024/768 Light/Dark,
    expanded/collapsed, framing/console/permission/retry.
- [x] Demo relasional eksplisit.
  - Acceptance: local/testing, idempotent, preserve user-edit/trashed/paid.
  - Verification: demo tests dan commit.

## Increment 5: QA Dan Delivery

- [x] Native/full gate dan aktivasi guarded.
  - Acceptance: double payment/current read tidak duplikasi; audit failure
    rollback dua module; schema/catalog lokal tidak ubah data/grant/stock user.
  - Verification: MySQL disposable worker, full tests/Pint/TS/lint/build/audit,
    migration status/fingerprint dua kali.
- [x] Dokumentasi aktif dan review akhir.
  - Acceptance: API/module/quality sesuai behavior, risk/non-scope jelas.
  - Verification: review source/link/diff/staged/exclusions/secret dan commit.
- [ ] Push dan remote sync.
  - Verification: HEAD=ls-remote, divergence0/0, user untracked terpisah.

## Sesudah Coding

- [x] Seluruh acceptance dan QA tercatat sesuai hasil nyata.
- [ ] Aktivasi aman, checklist terisi dan commit/push terverifikasi.

## Bukti

- Increment1: scope dan contract user dikonfirmasi, source/ownership konsisten;
  generator tidak relevan karena tidak menambah module.
- Increment2: RED4 DTO belum ada; GREEN5 receipt/purchase writer tests, gabungan
  regresi Finance26/181 PASS. Exact6, under/over/zero/duplicate, hash replay
  snapshot sesudah master nonaktif, guard metode, audit veto dan posted down
  teruji. Pint scoped PASS. Tidak mengubah receipt penjualan/saldo stok.
- Increment3: RED5 (pay route/action belum ada), GREEN31/309 bersama regresi
  posting/draft/Finance sale. Versi asal replay, payload berubah/stale/draft/
  trashed ditolak, supplier posted nonaktif tetap dapat dibayar, dua audit veto
  rollback pointer/Finance, seluruh tabel Stock identik, permission terpisah
  termasuk pay-only tanpa view, CSRF419 dan super invariant PASS. Snapshot JSON
  diuji sesudah key order berubah (kompatibel MySQL). Pint/8 route PASS.
- Increment4: demo RED3 belum ada, GREEN3/15; typecheck/ESLint resources/js,
  build2724 dan scoped Pint PASS. Playwright existing PASS pada desktop1440,
  tablet1024/768 Light/Dark expanded/collapsed: underpayment/error preserve,
  split/replay dua operator, payer tanpa manage/post, filter/detail readonly,
  audit sekali dan fingerprint Stock identik. Console kosong. Artefak ignored
  storage/framework/testing/purchase-payment-browser-d4eadadded57da69; screenshot
  light-payment-768 dan paid-readonly-detail diperiksa visual.
  Run pertama fixture jumlah hardcoded salah, diperbaiki memakai total exact
  authoritative fixture; rerun PASS, bukan perubahan business rule aplikasi.
  Sesudah user restart VSCode, Chrome DevTools MCP tersedia dan PASS langsung:
  login/assets pos.test200 tanpa console, akun payer di server disposable51042,
  dialog, POST pay302/GET200, Lunas dan snapshot readonly. Tidak ada console error.
  Viewport tool aktual768x637 (tinggi jendela dibatasi host), dialog framed;
  matriks viewport penuh dibuktikan Playwright. Simpan screenshot MCP ke workspace
  ditolak roots tool, fallback screenshot inline berhasil; bukan bypass access.
  Server/browser QA ditutup. Credential random tidak ditulis ke source/artefak.
- Increment5: full476 test/3757 assertion PASS (54,85s), npm audit0 dan composer
  audit --locked bersih. Native MySQL PASS: empty up/down legacy, double payment
  worker LOCK WAIT menghasilkan satu pengeluaran, replay MySQL JSON sesudah
  master nonaktif, audit veto rollback dua module, nominal10,123456 exact,
  peer nonaktifkan metode sesudah discovery dengan LOCK WAIT pada akun ditolak
  lewat current read tanpa mutation. Down kedua migration posted ditolak,
  FK restrict dan receipt sale tetap kosong. DB QA dibersihkan.
  Regresi browser penerimaan penuh PASS, console kosong; artefak
  storage/framework/testing/purchase-receipt-browser-c0186bb90e63aa11.
  Aktivasi php scripts/qa/apply-purchase-payment-local.php dua kali PASS,
  seluruh kolom/data existing serta permission lama identik. Finance000005
  batch18 dan PurchaseOrders000006 batch19 Ran. Tanpa seed/pay/grant/reset.
  ADR014/API/MODULES/module docs/QUALITY diselaraskan; source review tidak
  menemukan blocker dalam scope. Belum ada running balance/kecukupan kas,
  utang/parsial/reversal; tidak mengklaim dana bank benar-benar ditransfer.
