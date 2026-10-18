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
posting melalui writer Stock. Harga asal bukan nominal refund; kebijakan
refund dan pembulatannya belum ditetapkan.

Tidak ada endpoint edit invoice, hapus permanen, atau perubahan ledger bebas.
Snapshot publik tidak memuat HPP, pembayaran, request hash, maupun credential.
Nomor draft dibuat server dan immutable. Draft terhapus hanya dapat dipulihkan,
bukan diedit; operasi stale ditolak, termasuk dari super-system.

## Posting Barang Layak Jual

Scope dan ADR-015 disetujui 18 Oktober2026. Backend sedang diverifikasi pada
[pekerjaan lintas module](../../../work-items/sale-return-posting/README.md);
UI/aktivasi lokal mengikuti checklist, bukan klaim sudah tersedia lokal.

POST /sales/returns/{id}/post membutuhkan sales.returns.post dan version draft.
Semua baris diterima bersama ke gudang invoice asal, source cost berasal dari
fifo_consumptions penjualan, dengan layer baru bertanggal penerimaan. Stock
menyimpan trace kumulatif per consumption; partial round down scale6, full drain
mengambil residual. Biaya turunan nol diperbolehkan dan bisa dijual kembali;
input harga pokok stok awal/pembelian tetap positif.

Posted readonly dengan receipt ID/posted_at dan replay revisi asal tanpa stok
atau audit ganda. First posting memerlukan master aktif; replay tidak bergantung
master aktif. Finance/invoice/consumption asal immutable; refund, karantina,
tukar, gudang tujuan bebas dan reversal tetap belum tersedia. HPP tidak menjadi
props atau response Returns. Audit Returns dan Stock berada dalam satu transaction.

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
status draft, version integer, notes nullable500, demo_key unique nullable,
timestamps dan deleted_at. `sales_return_lines`: ULID, FK sales_return_id dan
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

`php artisan test --filter='ReturnQuantityTest|ReturnDraftTest|ReturnsScaffoldTest'`
menguji domain/lifecycle/permission/snapshot/audit/demo. Native:
`php scripts/qa/sales-returns-mysql.php` memakai DB random disposable dan cleanup.
Browser: `node scripts/qa/sales-returns-devtools.mjs` dengan QA_DEVTOOLS_ENTRY
menunjuk executable MCP existing; `node scripts/qa/sales-returns-browser.mjs`
dengan QA_PLAYWRIGHT_MODULE menunjuk Playwright existing. Keduanya memakai
fixture disposable, credential random stdin, bukan DB atau session user.

Rincian bukti aktual: [Checklist pekerjaan](work-items/draft-foundation/tasks.md).
