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

- [ ] Tulis RED writer/precision/integrity/replay/query lalu implementasi.
  - Acceptance: tanpa outer transaction ditolak, saldo baru/additive exact,
    consistency layer/saldo dan overflow, source/line unique, replay sama no-op,
    beda ditolak, satu movement/layer/baris, audit veto rollback.
  - Verification: focused tests RED/GREEN, legacy saldo/movement dan sale FIFO.
- [ ] Schema ledger/FK/query purchase-only.
  - Acceptance: nullable opening anchor, metadata fallback, legacy tidak hilang;
    rollback posted ditolak, base unit/master guard tetap berlaku.
  - Verification: SQLite migration up/down disposable, Pint/boundary/commit.

## Increment 3: Purchasing/PurchaseOrders

- [ ] Posting action/HTTP/version/snapshot/replay atomic.
  - Acceptance: full saja, harga/qty authoritative, ratio berubah ditolak,
    stale/trashed/posted mutation ditolak, kedua audit wajib, Finance tidak berubah.
  - Verification: RED/GREEN, inject failure sesudah receipt, retry/replay dan CSRF.
- [ ] Permission post dan filter/status.
  - Acceptance: view/manage bukan post, post-only bukan view, super tetap invariant;
    query posted snapshot readonly, endpoint payload whitelist version saja.
  - Verification: HTTP matrix/route:list/Pint dan commit.

## Increment 4: UI Dan Demo

- [ ] Tombol berizin, dialog konfirmasi, badge/filter/detail posted readonly.
  - Acceptance: Sonner/loading/error, data tetap saat gagal, shortcut dialog,
    tidak ada kesan lunas atau aksi edit/hapus pada posted.
  - Verification: TS/lint/build, isolated browser screenshot/console/network.
- [ ] Seeder demo posting eksplisit dan fixture relasional.
  - Acceptance: CCTV/kelistrikan, local/testing, idempotent posted tidak diubah,
    tidak otomatis mem-posting data user atau menambah Finance.
  - Verification: demo/replay tests dan browser desktop1440/tablet1024/768,
    Light/Dark expanded/collapsed, detail/total/footer framing dan commit.

## Increment 5: QA Dan Delivery

- [ ] Full test/build/Pint/audits dan native MySQL.
  - Acceptance: duplicate/concurrent post dan receipt-vs-sale tidak lost update,
    FK/posted-down guard, rollback lintas module dan legacy reports terbukti.
  - Verification: disposable native QA/barrier, full gates; bukan klaim SQLite lock.
- [ ] Aktivasi lokal hanya schema/catalog.
  - Acceptance: seluruh master/account/grant/stock/finance/audit lama identik;
    tidak seed/post draft user, script ulang idempotent.
  - Verification: guarded helper/fingerprint dua kali dan migrate:status.
- [ ] Dokumentasi aktif/API/MODULES/QUALITY dan review.
  - Acceptance: tidak lagi menyatakan draft-only/anchor opening wajib; hasil aktual.
  - Verification: link/diff/staged/secret/exclusion review dan commit.
- [ ] Push dan buktikan remote.
  - Verification: HEAD=ls-remote, divergence0/0, user untracked tetap terpisah.

## Sesudah Coding

- [ ] Acceptance/test/native/browser/gates lulus sesuai bukti.
- [ ] Aktivasi dan ledger lama aman; risk/non-scope dicatat.
- [ ] Checklist/commit/push selesai dan terverifikasi.

## Bukti Command

- Increment1: scope/PRD/ADR013/boundary/checklist dan relative links direview;
  git diff --check PASS. Tidak ada module baru, generator tidak relevan.
