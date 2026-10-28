# Tasks: Buku Kas

## Sebelum Mulai

- [x] AGENTS, README, arsitektur, struktur folder dan Payments dibaca.
- [x] Keputusan cutover per akun dan peringatan saldo minus disetujui user.
- [x] Scope/increment disusun sebelum coding; file LSP user tetap dipertahankan.

## Increment 1: Backend Dan Integritas Saldo

- [x] Schema ULID di module, opening unik dan ledger manual immutable.
  - Acceptance: tidak ada reset/backfill/saldo produksi otomatis.
  - Verification: migration disposable, status dan diff schema.
- [x] Reader saldo menggabungkan empat ledger existing dan mutasi manual.
  - Acceptance: cutoff WIB, applied cash, per akun, presisi enam desimal dan
    filter tanggal tidak menghilangkan saldo sebelum periode.
  - Verification: feature tests known balances, pagination dan cutoff.
- [x] Action opening, pemasukan/pengeluaran, transfer dan reversal.
  - Acceptance: akun aktif, audit atomic, idempotent, reversal sekali,
    tanpa mutation posted lama atau pengaman dana pada checkout.
  - Verification: focused tests termasuk invalid, replay, rollback dan akses.
- [x] Commit backend setelah gate terfokus lulus.

Hasil backend: RED 404 sebelum route tersedia; GREEN CashbookTest 7 test /
107 assertions. Payments 61 test / 621 assertions lulus sebelum dua test
tambahan refund/presisi. Pint file berubah lulus. Schema SQLite disposable
teruji melalui RefreshDatabase; native MySQL/aktivasi ada pada increment akhir.

## Increment 2: UI Operasional

- [ ] Halaman saldo/mutasi, filter akun/tanggal dan dialog operasi lengkap.
  - Acceptance: format Indonesia, peringatan minus/belum inisialisasi,
    permission UX, konfirmasi sebelum pencatatan, retry tidak menggandakan.
  - Verification: typecheck/lint/build dan browser desktop/tablet disposable.
- [ ] Sidebar/palette, shortcut, Sonner dan panduan operator.
  - Acceptance: tidak ada perubahan theme/POS atau grant role diam-diam.
  - Verification: review scope dan browser.
- [ ] Commit UI setelah verifikasi yang terdampak lulus.

## Increment 3: Aktivasi Dan Delivery

- [ ] Satu gate regresi akhir, MySQL disposable dan QA alur berubah.
- [ ] Migrasi additive dan catalog permission lokal tanpa saldo awal otomatis.
- [ ] Perbarui checklist berdasarkan hasil nyata, commit/push dan verifikasi
  remote. Pending operasional lama tetap pending.
