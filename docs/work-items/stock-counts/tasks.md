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

- [x] Snapshot, tambah barang, hitung parsial, review/kembali dan batal.
  - Acceptance: kosong bukan nol, stale version ditolak, pelepasan audited.
- [x] Posting selisih tambah/kurang/nol atomic dan idempotent.
  - Acceptance: HPP wajib positif untuk tambah, FIFO keluar, no-negative,
    kegagalan audit/ledger rollback dan gudang tetap tertahan.
- [x] UI, navigasi/palette, permission, pagination, shortcut dan panduan.
  - Acceptance: desktop/tablet, angka Indonesia, konfirmasi operasi kritis.
- [x] Commit save point setelah focused gate lulus.

## Increment 3: QA dan Aktivasi

- [x] MySQL disposable: freeze versus writer/posting bersamaan dan cleanup.

`php scripts/qa/stock-counts-mysql.php` PASS: schema native, tiga worker
terbukti LOCK WAIT (start versus writer, writer versus start, post versus
retry), snapshot decimal exact, replay tanpa duplikat, release setelah commit
dan guard rollback histori. Database generated dibersihkan pada finally.

Gate penuh pertama: 937 PASS, 5 FAIL, 7476 assertion. Tiga kegagalan seeder
latihan akibat tabel opname additive sudah ditutup dengan allowlist hanya
tabel kosong; tabel berisi sesi tetap memblokir reset. Tidak menjalankan
seeder/reset pada database toko. Dua kegagalan ringkasan retur existing dini
hari WIB diperbaiki dengan izin user pada commit 28086eb. Seluruh paket
terdampak 104 PASS/981 assertion; StockCountTest tambahan 10 PASS/78 assertion.

Typecheck, build 2938 module dan page-guides 29 halaman PASS pada source UI
saat ini. MCP namespace tidak tersedia; executable cached bisa dijalankan,
tetapi smoke berhenti menunggu dashboard sesudah login. QA UI penuh memakai
fallback Chrome/Playwright existing. Probe HTTP Inertia awal diperbaiki agar
mengirim asset version, bukan mengabaikan respons 409.
- [x] Browser alur berubah via MCP/fallback, fingerprint readonly/ledger.
- [x] Satu regresi backend, typecheck/lint/build dan page-guides.
- [x] Aktivasi migration/catalog additive tanpa grant atau sesi demo toko.
- [x] Isi bukti nyata, commit akhir, push dan verifikasi remote.

Penutupan implementasi: UI/QA `a6a7f72`, patch laporan `28086eb` terpisah.
Browser final PASS pada mvp-browser-02479e0ae585c002, console kosong dan
fingerprint sumber/Finance identik. Typecheck, ESLint scoped, build2938,
page-guides29, Pint scoped dan diff check PASS. Nama barang tidak dipersempit
pada tablet; tabel scroll; sidebar hanya menyorot menu paling spesifik.
Backup restore verified dan aktivasi native PASS; migration batch34, 9 route,
HTTPS anonymous302, data/grant lama identik dan tidak ada sesi toko otomatis.

Delivery: backend b1e1f7a, patch laporan 28086eb, UI/QA a6a7f72 dan
penutupan a75d1bd sudah dipush ke origin/main; hash remote cocok dan
divergence 0/0. Pencatatan delivery disimpan dan dipush sebagai commit docs
terakhir. File LSP user tetap di luar seluruh commit.
