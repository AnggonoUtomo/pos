# Tasks: Stock Opname

## Sebelum Mulai

- [x] Baca AGENTS, indeks, arsitektur, struktur, Stock dan writer terkait.
- [x] User menyetujui freeze gudang serta harga pokok selisih tambah.
- [x] Batasi scope; LSP user dan Old-docs tidak disentuh.

## Increment 1: Sesi dan Pengaman Gudang

- [x] Buat schema, repository dan guard pada seluruh pintu ledger.
  - Acceptance: satu sesi aktif, gudang lain normal, semua writer tertahan.
  - Verification: focused test RED/GREEN, schema dan rollback.
- [x] Commit save point setelah focused gate lulus.

Bukti awal: test guard RED karena capability belum tersedia; sesudah implementasi
seluruh suite Stock 66 PASS/433 assertion. Mencakup freeze, guard context palsu,
cancel/release, selisih tambah/kurang/nol/habis, replay, stale version, permission
serta audit veto yang mengembalikan seluruh posting ke status review.

## Increment 2: Alur Operasional Utuh

- [ ] Snapshot, tambah barang, hitung parsial, review/kembali dan batal.
  - Acceptance: kosong bukan nol, stale version ditolak, pelepasan audited.
- [ ] Posting selisih tambah/kurang/nol atomic dan idempotent.
  - Acceptance: HPP wajib positif untuk tambah, FIFO keluar, no-negative,
    kegagalan audit/ledger rollback dan gudang tetap tertahan.
- [ ] UI, navigasi/palette, permission, pagination, shortcut dan panduan.
  - Acceptance: desktop/tablet, angka Indonesia, konfirmasi operasi kritis.
- [ ] Commit save point setelah focused gate lulus.

## Increment 3: QA dan Aktivasi

- [ ] MySQL disposable: freeze versus writer/posting bersamaan dan cleanup.
- [ ] Browser alur berubah via MCP/fallback, fingerprint readonly/ledger.
- [ ] Satu regresi backend, typecheck/lint/build dan page-guides.
- [ ] Aktivasi migration/catalog additive tanpa grant atau sesi demo toko.
- [ ] Isi bukti nyata, commit akhir, push dan verifikasi remote.
