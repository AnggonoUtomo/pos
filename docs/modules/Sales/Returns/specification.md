# Spesifikasi Retur Penjualan

## Scope Disetujui

- Draft berdasarkan satu invoice posted, sebagian baris dan kuantitas.
- Satuan, rasio konversi, barang, gudang, pelanggan, dan harga berasal dari
  snapshot invoice, bukan master yang dapat berubah.
- Kuantitas positif, maksimum kuantitas terjual pada baris asal, presisi enam
  desimal. Konversi base unit harus tepat tanpa pembulatan tersembunyi.
- Satu baris invoice hanya sekali per draft; maksimum 100 baris.
- Invoice asal tidak dapat diganti ketika mengedit draft.
- Tambah/edit/soft delete/restore dengan optimistic version, row lock,
  transaction, permission backend, dan activity log atomik.
- `sales.returns.view` membaca semua draft. `sales.returns.manage` melakukan
  mutation dan mencari snapshot invoice sumber. Tidak otomatis memberikan
  permission kepada role/user existing. Super-system hanya bypass authorization.
- Catatan opsional maksimum 500 karakter; tidak disalin ke activity log.

## Batas Tegas

Draft sendiri tidak mengubah stok/FIFO, refund atau invoice/pembayaran asal.
Beberapa draft boleh menunjuk invoice sama; batas kumulatif diperiksa saat
posting melalui writer Stock. Harga asal bukan HPP atau uang yang otomatis
dikembalikan; backend refund tersedia terpisah di bawah, belum aktif lokal/UI.

Tidak ada endpoint edit invoice, hapus permanen, atau perubahan ledger bebas.
Snapshot publik tidak memuat HPP, pembayaran, request hash, maupun credential.
Nomor draft dibuat server dan immutable. Draft terhapus hanya dapat dipulihkan,
bukan diedit; operasi stale ditolak, termasuk dari super-system.

## Posting Barang Layak Jual

Scope dan ADR-015 disetujui 18 Oktober2026. Backend/UI dan QA telah selesai pada
[pekerjaan lintas module](../../../work-items/sale-return-posting/README.md).
Migration posting lokal Ran batch21, aktivasi dua kali tanpa demo/grant/posting.

POST /sales/returns/{id}/post membutuhkan sales.returns.post dan version draft.
Semua baris diterima bersama ke gudang invoice asal, source cost berasal dari
fifo_consumptions penjualan, dengan layer baru bertanggal penerimaan. Stock
menyimpan trace kumulatif per consumption; partial round down scale6, full drain
mengambil residual. Biaya turunan nol diperbolehkan dan bisa dijual kembali;
input harga pokok stok awal/pembelian tetap positif.

Posted readonly dengan receipt ID/posted_at dan replay revisi asal tanpa stok
atau audit ganda. First posting memerlukan master aktif; replay tidak bergantung
master aktif. Invoice/penerimaan/consumption asal immutable; posting fisik tidak
melakukan refund. Karantina, tukar, gudang tujuan bebas dan reversal belum tersedia. HPP tidak menjadi
props atau response Returns. Audit Returns dan Stock berada dalam satu transaction.

## Backend Refund Lunas (Belum Aktif Lokal/UI)

Scope disetujui melalui interview pada [work item refund](../../../work-items/sale-return-refund/README.md).
Hanya retur posted, satu penyelesaian penuh dengan multi-payment tepat tanpa
kembalian. Nominal otomatis dari nilai bersih snapshot baris invoice asal,
proporsional kuantitas, scale2/round down untuk sebagian dan residual pada
penghabisan jumlah terjual. Tidak memakai harga master/HPP atau input total bebas.

Metode aktif boleh berbeda dari pembayaran asal. Nilai Rp0,00 diselesaikan
sebagai Tanpa pengembalian uang dengan permission/audit, tanpa metode atau
ledger nol; alokasi kuantitas tetap tercatat. Refund tidak mengubah Stock/FIFO
atau penerimaan invoice asal. Posted lama tidak otomatis menjadi sudah dibayar.

Permission, quote/stale handling, persistence dan consumer writer Finance sudah
tersedia pada Increment 4. UI/history menyusul; migration lokal belum dijalankan.
Pembayaran parsial, kredit pelanggan, tukar barang dan reversal di luar scope.

Increment 2 menyediakan `RefundInvoiceBasis` untuk rekonsiliasi semua baris
terhadap header/paid_total bersih dan `RefundAllocation` untuk alokasi
remaining amount/quantity exact. Keduanya Domain murni tanpa persistence/HTTP.
Source summary khusus refund dan consumer quote/settlement dipasang Increment 4;
lookup draft existing tetap tidak mendapat field pembayaran baru.
Desain ledger khusus Finance, scope invoice, stale quote dan outcome nol
dikunci di [ADR-016](../../../decisions/ADR-016-SALE-RETURN-REFUND-SETTLEMENT.md).

GET `/{id}/refund-quote` dan POST `/{id}/refund` memakai sales.returns.refund
terpisah dari view/manage/post. Quote readonly, tidak membuat scope atau audit.
Settlement mengunci scope invoice (insert-if-absent dan current read), return/
lines, lalu alokasi terdahulu. Quote fingerprint mencakup revision/basis/lines;
perubahan revision menolak quote stale422 walaupun total tetap sama.

Settlement, allocation, kenaikan revision, payout Finance positif dan audit
refund_settled commit bersama. Nol menyimpan alokasi tanpa payout. Snapshot hasil
menjadi sumber replay; body berbeda ditolak, master nonaktif tidak menghalangi
replay. Status/version posted asal tidak berubah, CRUD posted tetap dilarang.
Schema belum tersedia ditolak422 refund; tidak fallback atau membuat pembayaran.

Migration `2026_10_19_000002_create_sales_return_settlements` memiliki scope,
settlement dan allocation immutable; FK/unique dan down guard melindungi data.
Tidak ada FK ke ledger Finance; ID berasal dari public writer pada transaction
yang sama. Permission catalog tidak memberi grant atau reset role existing.

## Demo Dan UI

Demo CCTV/kelistrikan hanya berjalan eksplisit pada local/testing, memakai
invoice demo posted yang sudah ada. Bila belum ada sumber relevan, skip tanpa
membuat penjualan/stock/payment otomatis. Demo idempotent dan tidak menimpa
draft edited/trashed. Aktivasi lokal tidak menjalankan demo.

Halaman admin memakai shadcn/ui, Sonner, format angka Indonesia, shortcut
dropdown, navigasi sidebar/palette, desktop/tablet Light/Dark.

## Schema Dan Contract

`sales_returns`: ULID, nomor SR-ULID unique immutable, FK invoice_id restrict,
invoice_number/customer_name untuk pencarian, header snapshot JSON safe,
status draft/posted, version integer, notes nullable500, demo_key unique nullable,
timestamps dan deleted_at. Metadata posted: stock_receipt_id unique FK restrict,
posted_from_version, posted_at/by. `sales_return_lines`: ULID, FK sales_return_id dan
invoice_line_id restrict, unique pasangan draft/baris asal, snapshot JSON flat
unit/barang/rasio/qty/harga asal, quantity/base_quantity decimal20,6 exact.
SQLite test memakai string decimal; MySQL target memakai decimal native.

Harga/nominal asal dalam JSON adalah decimal string scale2; quantity/ratio
scale6. Snapshot tidak memakai float. Perbandingan no-op menormalisasi urutan
key MySQL JSON tetapi nilai string tetap strict untuk mencegah hilangnya
perbedaan satu mikro pada angka besar.

Permission catalog `ReturnsAccessSeeder` additive; demo memakai
`ReturnsDemoSeeder` explicit. Key legacy sales.returns.create tidak dihapus atau
dianggap memberikan view/manage. Tidak reset akun/role dari identity seeder.

## Operasi Lokal Dan QA

URL `/sales/returns`. Aktivasi aman: `php scripts/qa/activate-sales-returns-local.php`
menjalankan migration module dan katalog permission saja, menjaga fingerprint
data existing. Migrasi target Ran batch20; aktivasi dua kali lulus.

Posting: `php scripts/qa/activate-sale-return-posting-local.php` hanya dua
migration baru dan catalog permission; fingerprint memakai kolom existing agar
penambahan metadata null tidak dianggap perubahan isi draft lama. Tidak ada
grant/reset/posting. Semua migration Ran sampai batch21. Perlu permission
sales.returns.post eksplisit untuk user biasa; super-system hanya bypass izin.

`php artisan test --filter='ReturnQuantityTest|ReturnDraftTest|ReturnsScaffoldTest'`
menguji domain/lifecycle/permission/snapshot/audit/demo. Native:
`php scripts/qa/sales-returns-mysql.php` memakai DB random disposable dan cleanup.
Browser: `node scripts/qa/sales-returns-devtools.mjs` dengan QA_DEVTOOLS_ENTRY
menunjuk executable MCP existing; `node scripts/qa/sales-returns-browser.mjs`
dengan QA_PLAYWRIGHT_MODULE menunjuk Playwright existing. Keduanya memakai
fixture disposable, credential random stdin, bukan DB atau session user.

QA posting native: `php scripts/qa/sale-return-posting-mysql.php`, termasuk dua
proses berebut sisa kuantitas dan biaya nol/resale. Browser kedua script memakai
flag `--posting` untuk grant terbatas fixture QA, konfirmasi, status readonly,
source fingerprint unchanged serta delta stok. Full525 test/4057 assertion PASS.

Rincian bukti: [Draft](work-items/draft-foundation/tasks.md) dan
[Posting](../../../work-items/sale-return-posting/tasks.md).
