# Tasks: Keranjang Tertahan

## Sebelum Mulai

- [x] Baca aturan, arsitektur/struktur, POS, roadmap dan recovery existing.
- [x] User mengesahkan own-only tanpa pengalihan/reservasi dan no double-pay.
- [x] Informasikan module target, siapkan plan/checklist dan pertahankan scope.

## Increment 1: Backend

- [x] RED test own/permission/validation/no ledger dan retry/version/cancel.
  - Acceptance: hanya actor sendiri; tahan tidak mengubah uang/stok.
  - Verification: HeldCartTest SQLite disposable.
- [x] Schema ULID, repository port, save/list/resume/cancel dan audit.
  - Acceptance: reprice current, invalid lines dipertahankan; konflik tidak
    menimpa perubahan; cancel soft delete, completed bukan mutable draft.
  - Verification: focused test dan migration disposable.
- [ ] Integrasi source checkout dan replay/rollback.
  - Acceptance: hanya satu invoice per sumber, pembayaran gagal tidak menutup
    draft, replay key sama tetap snapshot lama.
  - Verification: focused checkout serta MySQL row-lock dua worker.
- [ ] Commit backend sesudah focused/Pint lulus.

## Increment 2: UI

- [ ] Tahan/daftar/panggil/batal dengan dialog, loading/error/empty dan Sonner.
  - Acceptance: cart kosong hanya setelah save sukses; replace perlu konfirmasi;
    field diskon/pajak/satuan/pelanggan/gudang tidak hilang.
  - Verification: browser desktop/tablet, reload, reprice dan cancel.
- [ ] F6/F7, active source dan pending recovery.
  - Acceptance: operasi held terkunci saat pending payment; ID/version dipulihkan,
    konflik dua tab tidak membuat invoice ganda.
  - Verification: recovery parser dan browser alur terkait.
- [ ] Commit UI sesudah gate yang relevan lulus.

## Increment 3: Penutupan

- [ ] Satu regresi backend, lint/typecheck/build, Pint/diff check.
- [ ] DevTools discovery atau BLOCKED+fallback; screenshot diperiksa.
- [ ] Migration MySQL disposable, concurrency dan aktivasi additive lokal.
- [ ] Panduan/API, checklist dan status roadmap berdasarkan hasil nyata.
- [ ] Commit/push scope tanpa LSP; local/remote diverifikasi.

## Bukti

- RED: endpoint belum ada, 404 sesuai harapan.
- HeldCartTest: PASS 8 tests / 102 assertions sebelum interupsi; source tetap.
- Focused HeldCartTest + PosCheckoutTest setelah resume: PASS 25 tests /
  261 assertions. Ownership super-system, no ledger, optimistic version,
  quote current, soft cancel, replay dan rollback audit/payment tercakup.
- Pint PHP scope berubah: PASS. Native concurrency dan UI masih pending.
