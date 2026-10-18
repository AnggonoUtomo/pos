# Tasks: Posting Retur Penjualan Layak Jual

## Sebelum Mulai

- [x] AGENTS.md, docs/README.md, ARCHITECTURE.md dan FOLDER-STRUCTURE.md dibaca.
- [x] Spesifikasi Returns/Stock dan ADR-012 dibaca; kontrak/source terkait diperiksa.
- [x] User menyetujui scope barang layak jual/gudang asal/biaya FIFO asal,
  kumulatif terjual, atomicity dan replay; refund/karantina/tukar ditunda.
- [x] Module target diumumkan; worktree user dipisahkan dan dipertahankan.
- [x] Baseline rencana/checklist dibuat sebelum coding.

## Increment 1: Baseline Dan Keputusan Domain

- [x] Catat scope, ownership, dependency dan batas pekerjaan.
  - Acceptance: status rencana dibedakan dari implementasi; Finance tidak berubah.
  - Verification: review source/dokumen aktif, link relatif dan diff --check.
- [x] Tetapkan urutan layer barang retur.
  - Acceptance: jawaban eksplisit user; ADR membedakan biaya asal dari umur FIFO.
  - Verification: review keputusan terhadap Stock dan invariant posted immutable.
- [x] Tetapkan valuasi partial/residual termasuk alokasi biaya nol.
  - Acceptance: tidak mengubah HPP asal, menciptakan nilai, membuang residual,
    atau menerima layer yang membuat pengeluaran berikutnya gagal.
  - Verification: contoh partial/full dan test plan conservation.
- [x] Finalisasi ADR serta contract/schema/lock plan sebelum mutation ledger.
  - Acceptance: ada consumer nyata dan aturan replay, master nonaktif, privacy,
    overflow serta urutan lock yang jelas.
  - Verification: review dependency dan keputusan accepted.

## Increment 2: Stock Writer

- [x] Tambahkan unit test red, domain alokasi dan test conservation.
  - Acceptance: exact quantity/value, partial berulang, full drain residual,
    beberapa source consumption, invalid/overflow dan batas kumulatif.
  - Verification: focused unit test red-green.
- [x] Tambahkan schema/port/DTOs/adapter Stock dengan trace consumption asal.
  - Acceptance: receipt/movement/layer immutable dan source unik; histori sale
    utuh; tidak membaca model/DB privat POS atau Returns.
  - Verification: feature test, migration disposable dan route inspection.
- [x] Buktikan locking, replay dan rollback audit.
  - Acceptance: dua draft bersaing tidak melewati sisa barang terjual; retry sama
    tidak menggandakan stok; audit gagal membatalkan seluruh mutation.
  - Verification: feature tests dan native MySQL disposable dengan cleanup.

## Increment 3: Returns Dan UI

- [x] Tambahkan action/metadata/status posting dan permission backend terpisah.
  - Acceptance: shared transaction, stale ditolak, replay aman, posted tidak
    edit/hapus/restore; tidak menghitung atau mengeluarkan refund.
  - Verification: focused test termasuk super-system dan rollback lintas module.
- [x] Tambahkan konfirmasi, status/detail/filter, shortcut dan Sonner.
  - Acceptance: HPP tidak bocor; posted readonly; loading/error/retry berfungsi.
  - Verification: typecheck, ESLint, build, DevTools MCP dan screenshot.
- [x] Verifikasi desktop/tablet Light/Dark dan expanded/collapsed.
  - Acceptance: tidak overflow/overlap; permission visibility benar.
  - Verification: QA1440x1000,1024x768,768x1024; mobile di luar scope.
- [x] Pertimbangkan demo posting relasional yang eksplisit dan aman.
  - Acceptance: local/testing, idempotent, tidak menimpa/posting draft user;
    alasan skip dicatat bila tidak ada fixture yang aman.
  - Verification: seeder test atau review alasan skip.

## Increment 4: Penutupan

- [x] Jalankan full regression, formatter, frontend gate dan native MySQL.
  - Acceptance: semua command tercatat dengan hasil aktual; no-negative/FIFO
    penjualan dan pembelian existing tidak regresi.
  - Verification: test, Pint, tsc, ESLint, build, QA native/browser.
- [x] Aktifkan schema/katalog lokal tanpa grant/demo/reset/posting.
  - Acceptance: data existing dan invoice/Finance asal identik; activation repeat
    idempotent; migration status sesuai realita.
  - Verification: fingerprint, activation dua kali, migrate:status.
- [x] Perbarui API/module/ADR dan checklist, scoped commit.
  - Acceptance: klaim sesuai bukti; remote sama HEAD; file user tidak ikut.
  - Verification: diff --check, review staged dan link relatif dokumen.
- [ ] Push dan verifikasi checkpoint remote.
  - Acceptance: HEAD sama remote main, ahead/behind 0/0; file user tetap terpisah.
  - Verification: push, ls-remote, rev-list dan status.

## Bukti Verifikasi

Baseline: source Returns, POS, Stock dan ADR-012 diperiksa; interview mengunci
urutan layer baru serta dukungan biaya nol hasil alokasi secara eksplisit.
Link relatif dan git diff --check PASS sebelum coding dimulai.

Increment 1 Passed: user mengonfirmasi biaya nol hasil alokasi. ADR-015 accepted,
schema/lock/port plan ditetapkan; harga input tetap positif. Belum ada mutation
ledger pada penutupan baseline ini.

Increment 2, slice biaya nol: RED dua kasus (layer nol ditolak/flag belum ada),
lalu FifoAllocationTest/ReceiptBalanceTest PASS32 test/887 assertion. Layer qty
positif/value0 dapat dikeluarkan partial/full/campuran; qty0 dengan residual
tetap ditolak. ReceiptBalance hanya menerima added value0 bila flag eksplisit
writer retur; default pembelian dan harga pokok input tetap positif.

Slice backend: ReturnAllocationTest RED empat class belum ada lalu GREEN;
PostReturnDraftTest RED permission belum ada lalu GREEN. Focused
ReturnAllocationTest/SaleReturnStockTest/PostReturnDraftTest/ReturnDraftTest
PASS29 test/178 assertion. PurchaseStockReceiptTest tetap PASS, route:list
sales/returns PASS 9 route, scoped Pint PASS. Bukti native/browser/aktivasi
dicatat pada increment berikutnya.

Stock memakai stock_receipts source_type sale_return dan stock_return_allocations
untuk cumulative current read. Port internal receipt mendapat sourceType default
purchase dan receiveLine mengembalikan ID; consumer purchase existing tetap
kompatibel dan diuji. Published SaleReturnStockWriter dikonsumsi PostReturnDraft.

Increment 2 native PASS: `php scripts/qa/sale-return-posting-mysql.php` memakai
DB random pos_return_post_qa_ dan dua proses PHP independen. Dua draft berebut
sisa penjualan: tepat satu posted dan satu ditolak, tanpa over-return. BOX/base
dan HPP asal exact, replay no-op, audit veto rollback, source/Finance immutable,
full residual conserved, guard rollback posted dan finally DROP DB QA PASS.
Micro-cost nyata: stok awal positif -> sale berbiaya0 -> retur -> jual ulang
seluruhnya, HPP0,000003 tetap terjaga pada MySQL native.

Test tambahan PASS18 test/79 assertion untuk ReturnAllocation/Stock/Returns:
barang sama dengan dua satuan asal ditelusuri per line, satu sale line dari dua
cost consumption berbeda, gudang tujuan berbeda ditolak, filter status dan
privacy HPP. Demo posted sengaja SKIPPED di seeder operasional: ReturnsDemoSeeder
tetap hanya draft agar tidak otomatis memposting/mengubah stok draft user.
Fixture QA disposable menyediakan skenario posted CCTV yang relevan.

Increment 3 Passed: `npx tsc --noEmit`, `npx eslint resources/js`, `npm run build`
PASS2735 module. Chrome DevTools MCP namespace tidak diekspos sesi; server MCP
existing diakses stdio isolated melalui script dengan roots hanya workspace,
tanpa dependency/config/profil user berubah. `sales-returns-devtools.mjs --posting`
PASS tambah/post/readonly, angka1,25, snapshot, framing1440/1024/768, console/
network internal dan source fingerprint serta delta stok. Artefak:
storage/framework/testing/sales-returns-browser-bf03c3fb5651bed6/.

`sales-returns-browser.mjs --posting` PASS: CRUD draft, qty berlebih ditolak,
source500 terkontrol/retry, posting keyboard, filter status bertahan setelah
reload, posted tanpa edit/hapus/terima lagi, viewer readonly dan outsider403,
shortcut/palette, Light/Dark desktop1440/tablet1024/768 expanded/collapsed,
konfirmasi dan detail framing. Console unexpected kosong; dua error disengaja
terpisah. Source invoice/consumption/Finance unchanged, delta stok dan satu
trace benar, event created/updated/deleted/restored/posted. Screenshot direview.
Artefak: storage/framework/testing/sales-returns-browser-8145b63ab67603a7/.

Gate akhir sebelum aktivasi: `php artisan test --compact` PASS525 test/4057
assertion; scoped Pint dan diff --check PASS. QA mobile di luar scope.

Increment 4: `php scripts/qa/activate-sale-return-posting-local.php` dua kali
PASS. `php artisan migrate:status` seluruhnya Ran, dua migration posting pada
batch 21. Fingerprint semua kolom/data existing serta permission/migration lama
identik. Hanya schema baru/metadata nullable dan catalog posting ditambahkan,
tanpa grant/demo/reset/posting transaksi user. Script PHP dan server QA milik
pekerjaan sudah selesai; aplikasi memakai virtual host Laragon existing.

Review boundary/privacy/transaction dan scope staging dilakukan sebelum commit.
Tidak ada dependency/stack/theme/Old-docs atau file user yang diubah. Generator
tidak diperlukan karena tidak membuat module atau mengubah struktur canonical.
