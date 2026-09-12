# Spesifikasi: POS Modular ERP-Lite

Status: Draf

## Tujuan

Membangun aplikasi Point of Sales dan operasional toko berbasis web untuk bisnis retail dan grosir. Sistem harus mendukung multi gudang, multi satuan, harga berdasarkan level pelanggan, HPP FIFO, retur, pajak, diskon, multi metode pembayaran, dan finance lite.

Arah produk terinspirasi dari workflow iPos-like: master data, pembelian, penjualan kasir, penjualan backoffice, kontrol persediaan, hutang, piutang, laporan, dan pengaturan sistem.

## Tech Stack

- Backend: Laravel 12.
- Frontend: Inertia React.
- UI default: shadcn/ui.
- Database: MySQL.
- Authentication: auth bawaan Laravel starter kit.
- Authorization: Spatie Laravel Permission.
- Audit trail: Spatie Laravel Activitylog.
- Primary key: ULID.
- Arsitektur: modular monolith, DDD-lite, hexagonal architecture.

## Command

Command final akan diverifikasi setelah source Laravel 12 Inertia React fresh dicopy ke workspace ini.

Command awal yang diharapkan:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run dev
npm run build
php artisan test
```

## Arsitektur

Gunakan folder modul bergaya Laravel dengan namespace kategori:

```text
Modules/
  Platform/
    Identity/
    Company/

  Commerce/
    Catalog/
    Parties/
    Sales/
    Purchasing/
    Payments/

  Inventory/
    Inventory/

  Reporting/
    Reports/
```

Konvensi penamaan:

```text
PHP namespace: Modules\Commerce\Sales
Route name: commerce.sales.invoices.index
Permission: commerce.sales.invoices.create
Event: commerce.sales.invoice_posted
```

Setiap modul boleh memakai struktur DDD-lite hexagonal berikut:

```text
Application/
  Commands/
  Queries/
  DTOs/
  Services/

Domain/
  Models/
  ValueObjects/
  Events/
  Exceptions/
  Contracts/

Infrastructure/
  Persistence/
    Eloquent/
    Migrations/
  Providers/

Presentation/
  Http/
    Controllers/
    Requests/
  Routes/
    web.php
  Pages/
```

Jangan memaksakan folder kosong. Modul CRUD tipis boleh tetap sederhana sampai ada domain logic nyata.

Komunikasi lintas modul memakai public boundary berikut jika modul perlu dipakai modul lain:

```text
Application/Contracts
Application/DTOs
Application/Events
```

Domain event atau integration event hanya dibuat jika memang memiliki consumer. Aturan detail komunikasi lintas modul didokumentasikan di `docs/MODULE-COMMUNICATION.md`.

## Aturan Inti

- Fase 1 memakai single company.
- Fase 1 mendukung multi gudang, belum multi cabang.
- User dapat memilih gudang saat transaksi.
- MVP memakai satu gudang per transaksi.
- Stok tidak boleh minus.
- FIFO berjalan per item dan gudang.
- Semua kuantitas stok internal disimpan dalam base unit terkecil.
- Multi satuan wajib sejak fase 1.
- Harga jual dipilih berdasarkan item, satuan terpilih, dan level pelanggan.
- Harga bertingkat berdasarkan jumlah beli tidak masuk scope fase 1.
- Public registration dimatikan.
- User dibuat oleh admin.
- Transaksi posted bersifat immutable.
- Koreksi transaksi posted dilakukan lewat retur, void/reversal, atau adjustment yang diaudit.
- Dokumentasi dan UI label memakai Bahasa Indonesia, tetapi nama tabel, kolom, class, route, permission, enum, dan namespace memakai English technical naming.
- Migration milik modul tetap berada di modul, bukan dipindah ke `database/migrations` global.
- Jangan membuat port, event, service, repository, adapter, atau integration tanpa consumer nyata.

## Siklus Hidup Transaksi

```text
DRAFT -> POSTED -> VOID
```

- Transaksi backoffice boleh disimpan sebagai draft.
- Transaksi POS kasir langsung posted.
- Dampak stok, hutang, piutang, pembayaran, dan laporan hanya terjadi saat transaksi posted.
- Transaksi posted tidak boleh diedit bebas.
- Nomor transaksi yang sudah void tidak boleh dipakai ulang.
- Aksi void membutuhkan permission eksplisit.
- Transaksi yang sudah memiliki retur atau pembayaran turunan membutuhkan aturan void/reversal yang lebih ketat.

## Penomoran

Format nomor dokumen default:

```text
POS-{YYYYMMDD}-{0001}
INV-{YYYYMMDD}-{0001}
PO-{YYYYMMDD}-{0001}
PUR-{YYYYMMDD}-{0001}
SRT-{YYYYMMDD}-{0001}
PRT-{YYYYMMDD}-{0001}
TRF-{YYYYMMDD}-{0001}
STO-{YYYYMMDD}-{0001}
ADJ-{YYYYMMDD}-{0001}
PAY-{YYYYMMDD}-{0001}
```

Aturan:

- Nomor dokumen bisnis terpisah dari primary key ULID.
- Nomor unik per tipe dokumen.
- Sequence reset harian berdasarkan tanggal dokumen.
- Format nomor dapat dikonfigurasi nanti di company/settings.
- Generate nomor harus dilindungi database transaction atau lock.
- Nomor yang sudah dipakai tidak boleh dipakai ulang, termasuk setelah void.

## Persediaan dan FIFO

- Pembelian posted ke gudang membuat FIFO layer.
- Penjualan posted dari gudang consume FIFO layer dari gudang tersebut.
- Transfer consume FIFO layer dari gudang asal dan membuat layer cost yang sesuai di gudang tujuan.
- Retur penjualan mengembalikan stok memakai cost dari alokasi FIFO penjualan asal.
- Retur pembelian mengurangi stok dan menyesuaikan hutang/deposit/refund sesuai status pembayaran.
- Semua aksi yang memengaruhi stok wajib membuat stock movement.
- Kartu stok diturunkan dari stock movement, bukan dari activity log.
- Consume FIFO layer wajib memakai database transaction dan row lock.

Contoh:

```text
Gudang A - Item X:
Layer 1: 100 pcs @ 10.000
Layer 2: 80 pcs @ 11.000

Jual 120 pcs:
Consume 100 pcs dari Layer 1
Consume 20 pcs dari Layer 2
HPP = 1.220.000
Sisa Layer 2 = 60 pcs
```

## Katalog dan Harga

- Item dapat berupa barang, jasa, non-inventory item, atau assembly item di fase lanjut.
- Fase 1 fokus pada barang dan dukungan jasa/non-inventory seperlunya.
- Setiap stock item punya base unit.
- Satuan tambahan menyimpan konversi ke base unit.
- Harga item dikonfigurasi per level pelanggan dan satuan.
- Pelanggan walk-in memakai level pelanggan default.
- Sales line wajib menyimpan snapshot satuan terpilih, konversi, harga, diskon, pajak, dan HPP FIFO.

Contoh:

```text
Item: Aqua
Base unit: pcs

Konversi satuan:
1 pcs = 1 pcs
1 dus = 24 pcs

Retail:
pcs = 5.000
dus = 115.000

Grosir:
pcs = 4.700
dus = 108.000
```

## Penjualan dan POS

Dua mode UI wajib tersedia:

- Mode admin ERP sidebar untuk workflow backoffice.
- Mode POS fullscreen untuk workflow kasir.

Fitur POS fase 1:

- Barcode atau pencarian item.
- Cart.
- Pilih gudang.
- Pilih pelanggan.
- Harga berdasarkan level pelanggan.
- Penjualan multi satuan.
- Diskon item dan diskon invoice.
- Pilih mode pajak.
- Pending transaction.
- Multi metode pembayaran.
- Data cetak struk.
- Posting langsung.

Fitur penjualan backoffice fase 1:

- Sales invoice draft dan posted.
- Pilih pelanggan.
- Jatuh tempo dan pengelolaan piutang.
- Multi metode pembayaran.
- Retur penjualan berdasarkan invoice asal.

## Pembelian

Fitur pembelian fase 1:

- Purchase order.
- Purchase invoice.
- Pilih supplier.
- Pilih gudang.
- Baris pembelian multi satuan.
- Diskon dan pajak.
- Pengelolaan hutang.
- Pengelolaan pembayaran.
- Retur pembelian berdasarkan purchase invoice asal.
- Purchase invoice posted membuat FIFO layer.

## Pajak dan Diskon

Mode pajak:

```text
NON
INCLUDE
EXCLUDE
```

Aturan:

- Tarif pajak default dikonfigurasi di company/settings.
- Customer dapat memiliki default tax mode.
- User berizin dapat override tax mode saat transaksi.
- Item dapat taxable atau non-taxable.
- Nilai pajak disnapshot pada transaction line.
- Pembelian dan penjualan memakai mode pajak yang sama.
- Cost FIFO default memakai harga beli setelah diskon dan sebelum pajak.

Aturan diskon:

- Diskon item mendukung persen dan nominal.
- Diskon invoice mendukung persen dan nominal.
- Diskon dihitung sebelum pajak.
- Diskon invoice harus dialokasikan proporsional ke line untuk laporan laba.

## Pembayaran dan Finance Lite

Metode pembayaran yang wajib tersedia sejak fase 1:

- Tunai.
- Transfer bank.
- QRIS.
- Kartu debit.
- Kartu kredit.

Aturan:

- Satu invoice dapat dibayar dengan beberapa metode.
- Pembayaran sebagian membuat piutang untuk penjualan atau hutang untuk pembelian.
- Transaksi POS walk-in sebaiknya wajib lunas.
- Penjualan piutang membutuhkan pelanggan terdaftar.
- Pembayaran non-tunai dapat menyimpan nomor referensi.
- Pembayaran diposting ke akun kas atau bank.

Finance Lite mencakup:

- Akun kas dan bank.
- Pembayaran penjualan.
- Pembayaran pembelian.
- Piutang pelanggan.
- Hutang supplier.
- Refund retur.
- Laporan kas sederhana.
- Laporan piutang dan hutang.
- Laporan laba penjualan berbasis FIFO.
- Laporan pajak operasional.

Finance Lite fase 1 tidak mencakup full accounting:

- Jurnal manual.
- Manajemen chart of accounts.
- Buku besar.
- Neraca saldo.
- Neraca.
- Laba rugi akuntansi formal.
- Tutup tahun.

Semua record transaksi tetap harus journal-ready untuk modul akuntansi di masa depan.

## Identitas dan Akses

- Public registration dimatikan.
- User dibuat oleh admin.
- Authentication memakai Laravel starter kit.
- Authorization memakai Spatie Laravel Permission.
- Role dan permission dikelola melalui modul Identity.
- User dapat memiliki aturan akses gudang.
- User dapat memiliki default warehouse, tetapi tetap bisa memilih gudang yang diizinkan saat transaksi.

Permission memakai dot notation:

```text
platform.identity.users.view
platform.identity.users.create
commerce.sales.invoices.create
commerce.sales.invoices.post
commerce.sales.returns.create
commerce.purchasing.invoices.post
inventory.inventory.transfers.create
reports.reports.sales.view
```

## Audit Trail

Gunakan Spatie Laravel Activitylog sejak fase 1.

Tetap simpan kolom actor eksplisit di transaksi:

```text
created_by
posted_by
voided_by
created_at
posted_at
voided_at
```

Activity log wajib mencatat aksi penting:

- Create, update, dan delete master data.
- Perubahan settings.
- Perubahan role dan permission.
- Perubahan draft transaction.
- Posting transaction.
- Voiding transaction.
- Payment.
- Refund.
- Return.
- Stock adjustment.

Activity log bukan ledger. Activity log tidak boleh menjadi sumber saldo stok, kas, hutang, atau piutang.

## Scope UI

Target:

- Utama: desktop dan tablet.
- POS dioptimalkan untuk desktop kasir dan tablet landscape.
- Admin memiliki responsive dasar.
- Workflow mobile phone bukan prioritas fase 1.

Standar UI:

- Gunakan shadcn/ui sebagai default komponen UI aplikasi.
- Gunakan Tailwind CSS sesuai konfigurasi starter kit.
- Gunakan `lucide-react` untuk ikon.
- Komponen reusable mengikuti alias shadcn/ui: `@/components`, `@/components/ui`, `@/lib`, dan `@/hooks`.
- Jangan membuat design system paralel tanpa persetujuan.
- Frontend memakai struktur feature-folder per halaman/domain di `resources/js/pages`.
- Komponen lokal fitur disimpan di `pages/{category}/{feature}/components`.
- Tipe lokal fitur disimpan di `pages/{category}/{feature}/types.ts` jika dibutuhkan.
- Komponen lintas fitur hanya boleh masuk `components/shared` jika benar-benar umum dan stabil.
- Admin ERP memakai komponen shadcn/ui untuk tabel, form, dialog, dropdown, select, checkbox, tab, tooltip, dan navigation.
- POS fullscreen boleh memakai komponen custom untuk kebutuhan kasir cepat, tetapi tetap mengikuti token, warna, spacing, dan interaction pattern shadcn/ui.

Layout:

```text
resources/js/layouts/AppLayout.tsx
resources/js/layouts/PosLayout.tsx
```

Mode admin ERP:

- Sidebar navigation.
- Topbar.
- Tabel padat.
- Search, filter, pagination.
- Form transaksi backoffice.

Mode POS fullscreen:

- Navigasi minimal.
- Input barcode/search cepat.
- Layout mengutamakan cart.
- Total dan aksi pembayaran jelas.
- Keyboard shortcut jika memungkinkan.

Shortcut POS yang diharapkan:

```text
F9  Transaksi baru
F5  Simpan pending
F6  Buka pending
End Pembayaran
Esc Tutup modal/batalkan dialog aktif
```

## Strategi Testing

Strategi testing final akan disesuaikan setelah source Laravel tersedia.

Level yang diharapkan:

- Unit test untuk kalkulasi domain, konversi satuan, pajak, diskon, dan FIFO.
- Feature test untuk posting penjualan, posting pembelian, retur, pembayaran, dan transfer stok.
- Authorization test untuk Boundary role/permission penting.
- Frontend atau browser test untuk flow POS kritis jika tooling tersedia.

Behavior berisiko tinggi yang wajib dites:

- Stok tidak bisa minus.
- FIFO consume layer tertua dengan benar.
- Kuantitas multi satuan dikonversi ke base unit dengan benar.
- Customer-level pricing memilih harga yang benar.
- Perhitungan pajak include/exclude benar.
- Transaksi posted tidak bisa diedit langsung.
- Qty retur tidak bisa melebihi qty transaksi asal.
- Total multi payment dan saldo piutang/hutang benar.

## Batasan

Selalu:

- Gunakan ULID untuk primary key tabel utama.
- Gunakan English technical naming untuk tabel dan kolom database.
- Validasi input user di request Boundary.
- Gunakan database transaction untuk posting stok dan pembayaran.
- Lock FIFO layer saat consume stok.
- Simpan snapshot transaksi.
- Catat stock movement untuk setiap aksi yang memengaruhi stok.
- Catat activity log untuk aksi master, setting, transaksi, pembayaran, dan stok yang penting.
- Jaga auditability transaksi posted.

Tanya dulu:

- Mengubah lifecycle transaksi.
- Menambah full accounting.
- Mengubah aturan FIFO.
- Mengizinkan stok minus.
- Mengubah struktur kategori modul.
- Mengubah naming database atau strategi migration modul.
- Menambah harga bertingkat berdasarkan jumlah beli.
- Mendukung multi cabang.
- Mengaktifkan public registration.

Tidak boleh:

- Mengedit bebas transaksi posted.
- Menggunakan ulang nomor transaksi void.
- Mengurangi stok tanpa stock movement.
- Menggunakan activity log sebagai ledger stok atau finance.
- Menyimpan stok dalam banyak satuan tanpa normalisasi base unit.
- Mengubah snapshot harga, cost, pajak, atau diskon historis secara diam-diam.
- Menggunakan Laravel Boost atau Wayfinder sebagai source of truth arsitektur.

## Kriteria Sukses Fase 1

- Admin dapat mengelola user, role, permission, company settings, dan warehouse.
- Admin dapat mengelola item, satuan, konversi satuan, level pelanggan, harga pelanggan, supplier, customer, dan sales.
- Posting pembelian menambah stok gudang dan membuat FIFO layer.
- POS dapat menjual item multi satuan memakai harga level pelanggan.
- Posting penjualan menolak stok gudang yang tidak cukup.
- Posting penjualan consume FIFO layer dan mencatat HPP.
- Laporan laba penjualan dapat memakai HPP FIFO.
- Multi metode pembayaran berjalan untuk penjualan dan pembelian.
- Piutang dan hutang dibuat dari pembayaran sebagian.
- Retur penjualan dan retur pembelian berjalan terhadap transaksi asal.
- Transfer stok memindahkan cost FIFO antar gudang.
- Laporan kartu stok dan saldo stok tersedia.
- Laporan dasar penjualan, pembelian, kas, pajak, piutang, hutang, dan laba tersedia.

## Pertanyaan Terbuka

- Apakah forgot-password tetap aktif di fase 1.
- Apakah akses gudang ditegakkan ketat sejak fase 1 atau seed awal dibuat permisif dulu.
- Apakah cetak struk memakai browser print dulu atau langsung integrasi thermal printer.
