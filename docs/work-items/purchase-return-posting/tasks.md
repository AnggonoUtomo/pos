# Tasks: Posting Retur Pembelian

## Sebelum Mulai

- [x] `AGENTS.md`, `docs/README.md`, arsitektur, struktur folder, workflow,
  Stock/Returns dan preseden retur penjualan dibaca.
- [x] User menyetujui layer penerimaan asal, posting penuh satu kali, posting
  saat serah barang dan penyelesaian supplier terpisah.
- [x] Target Purchasing/Returns dan Inventory/Stock diumumkan.
- [x] Source receipt/FIFO/issue, draft/version, route/permission/UI ditinjau.
- [x] Worktree diperiksa; `SampleUI/`, file LSP dan `Old-docs/` di luar scope.
- [x] Scope, acceptance, QA native/browser dan rencana increment ditulis
  sebelum coding.

## Increment 1: Baseline

- [x] Finalisasi ADR-017 dan work item lintas module.
  - Acceptance: aturan layer asal dan konsekuensi retur penjualan eksplisit;
    kontrak/lock/owner/non-scope jelas, tanpa klaim runtime.
  - Verification: review dokumen/source, link relatif dan `git diff --check`.
- [x] Selaraskan roadmap/index module dan buat scoped commit dokumentasi.
  - Acceptance: status lama draft selesai dan posting baru In Progress;
    user artifacts tidak masuk commit.
  - Verification: staged path review, commit hanya dokumen.

## Increment 2: Writer Stock

- [ ] Uji alokasi dari satu layer receipt asal dan conservation seluruh saldo.
  - Acceptance: layer lain tidak eligible; partial round-down6, full residual,
    biaya nol turunan, kurang stok dan orphan trace ditolak.
  - Verification: unit/feature red-green serta regression issue sale/receipt.
- [ ] Publikasikan writer Stock/DTOs dengan consumer nyata Returns.
  - Acceptance: issue `purchase_return`, movement negatif, consumption ke layer
    asal, audit atomik, replay aman; source receipt/sale/Finance immutable.
  - Verification: focused test, MySQL native current read/dua draft bersaing.

## Increment 3: Posting Returns

- [ ] Tambahkan migration additive, action dan permission/route post.
  - Acceptance: shared transaction; version/stale/replay, snapshot/source
    authoritative, audit rollback; posted tidak bisa CRUD draft.
  - Verification: feature HTTP/auth/403/422/replay, migration up/down disposable,
    route:list dan rollback audit Stock/Returns.

## Increment 4: UI

- [ ] Tambah konfirmasi posting dan tampilan posted readonly.
  - Acceptance: post hanya terlihat dengan izin, double submit aman, status
    dan waktu posting tampil, HPP/refund tidak disalahartikan.
  - Verification: tsc, ESLint, build, DevTools MCP/fallback desktop/tablet.

## Increment 5: QA Dan Aktivasi

- [ ] Ulang native/focused/full regression dan activation QA disposable.
  - Acceptance: no-negative, audit, lock, ledger conservation; migration dan
    catalog additive idempotent, data lama identik.
  - Verification: test, Pint, native script, route/status, fingerprint.
- [ ] Aktifkan lokal hanya schema/catalog, tutup docs/checklist dan delivery.
  - Acceptance: tanpa grant/demo/posting; staged scope bersih, remote SHA sama
    HEAD dan ahead/behind 0/0.
  - Verification: activation dua kali, migration status, build/browser evidence,
    link/diff check, commit/push/ls-remote.

## Sesudah Coding

- [ ] Semua acceptance work item terbukti dan bukti command dicatat.
- [ ] Tidak ada perubahan pada Finance/source/receipt asal atau file user.
- [ ] Status module/API/ADR sesuai runtime; risiko residual dinyatakan.

## Bukti Increment 1

Source Stock/Returns/PurchaseOrders dan preseden retur penjualan ditinjau.
ADR-017 mencatat empat persetujuan user dan batas layer asal. Sembilan dokumen
baru/berubah diperiksa untuk link relatif; `git diff --check` lulus. Commit
dokumen dilakukan dengan pathspec terfokus agar staged `SampleUI/` dan file LSP
user tetap terpisah.
