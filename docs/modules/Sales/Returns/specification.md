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

Draft belum memposting retur, mereservasi stok/kuantitas retur, mengembalikan
stok/FIFO, mencatat refund, atau mengubah invoice/pembayaran asal. Beberapa
draft boleh menunjuk invoice sama; batas kumulatif retur posted wajib dirancang
sebelum posting tersedia. Draft hanya menyimpan kuantitas permintaan dan harga
asal; nominal refund dan kebijakan pembulatannya belum ditetapkan.

Tidak ada endpoint posting, edit invoice, hapus permanen, atau perubahan ledger.
Snapshot publik tidak memuat HPP, pembayaran, request hash, maupun credential.
Nomor draft dibuat server dan immutable. Draft terhapus hanya dapat dipulihkan,
bukan diedit; operasi stale ditolak, termasuk dari super-system.

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
