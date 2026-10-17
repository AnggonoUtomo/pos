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

- [ ] Ledger/writer dan settlement lunas exact.
  - Acceptance: transaction caller, jumlah tepat, metode unik/aktif/compatible,
    replay no-op dan payload beda ditolak, audit wajib, down/FK aman.
  - Verification: RED/GREEN focused, precision/audit/guard master/regresi sale.
- [ ] Guard akun/metode mencakup pengeluaran.
  - Acceptance: histori immutable melindungi konfigurasi; rename tidak ubah snapshot.
  - Verification: lifecycle tests/Pint dan commit.

## Increment 3: Purchasing/PurchaseOrders

- [ ] Schema pointer/snapshot/pay action dan endpoint.
  - Acceptance: posted saja, total server, version/replay, audit kedua rollback,
    stock/FIFO identik, posted lama belum dicatat, permission pay terpisah.
  - Verification: RED/GREEN HTTP/permission/CSRF/whitelist/stale, route dan commit.

## Increment 4: UI Dan Demo

- [ ] Dialog bayar, badge/filter dan detail readonly snapshot.
  - Acceptance: Indonesia exact6, Sonner/error/loading, shortcut/dropdown,
    tanpa aksi bayar lagi pada paid atau edit posted.
  - Verification: TS/lint/build/browser desktop1440/tablet1024/768 Light/Dark,
    expanded/collapsed, framing/console/permission/retry.
- [ ] Demo relasional eksplisit.
  - Acceptance: local/testing, idempotent, preserve user-edit/trashed/paid.
  - Verification: demo tests dan commit.

## Increment 5: QA Dan Delivery

- [ ] Native/full gate dan aktivasi guarded.
  - Acceptance: double payment/current read tidak duplikasi; audit failure
    rollback dua module; schema/catalog lokal tidak ubah data/grant/stock user.
  - Verification: MySQL disposable worker, full tests/Pint/TS/lint/build/audit,
    migration status/fingerprint dua kali.
- [ ] Dokumentasi aktif dan review akhir.
  - Acceptance: API/module/quality sesuai behavior, risk/non-scope jelas.
  - Verification: review source/link/diff/staged/exclusions/secret dan commit.
- [ ] Push dan remote sync.
  - Verification: HEAD=ls-remote, divergence0/0, user untracked terpisah.

## Sesudah Coding

- [ ] Seluruh acceptance dan QA tercatat sesuai hasil nyata.
- [ ] Aktivasi aman, checklist terisi dan commit/push terverifikasi.

## Bukti

- Increment1: scope dan contract user dikonfirmasi, source/ownership konsisten;
  generator tidak relevan karena tidak menambah module.
