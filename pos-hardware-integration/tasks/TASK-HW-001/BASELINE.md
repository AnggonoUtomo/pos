# Baseline POS: Discovery Read-Only

Tanggal pemeriksaan: 20 November 2026. Pemeriksaan berupa pembacaan source,
migration, route declaration, test dan dokumentasi; bukan pengujian produksi.
Koreksi scope pemilik tercatat di [README](README.md#keputusan-pemilik-yang-mengoreksi-bundle).

## Repository Dan Batas Pemeriksaan

- Root: `C:\laragon\www\pos`; branch: `main`.
- HEAD: `1d6ca5bdcf22b569b92125b56cb69eed5a010fc0`.
- Commit terakhir: `docs: close category and brand delivery checklist`.
- Worktree kotor sebelum pekerjaan ini: empat file `storage/framework/lsp-*.php`
  sudah staged, lima file LSP lain untracked; companion hardware juga untracked.
  Semua tetap milik user, tidak di-stage, dihapus atau di-commit.
- Root AGENTS, docs/README, ARCHITECTURE, FOLDER-STRUCTURE, QUALITY dan
  spesifikasi Sales/POS serta Inventory/Items dibaca. `Old-docs/` tidak dibaca.
- Tidak membaca credential, database transaksi toko atau sesi browser user.
- Tidak menjalankan Artisan: bootstrap provider dapat punya side effect,
  misalnya pemeriksaan/penghapusan Vite hot file pada environment local.
- Route dan schema di bawah adalah deklarasi source, bukan hasil inspeksi
  route cache atau schema live. Runtime, konfigurasi produksi, scheduler,
  backup serta koneksi perangkat tidak divalidasi ulang.

## Implementasi Nyata

Semua path berikut relatif terhadap root repository POS.

| Surface | Bukti source | Temuan dan konsekuensi |
| --- | --- | --- |
| Stack | `composer.json`, `package.json`, `.github/workflows/tests.yml` | Laravel ^12, Inertia ^2, React ^19, Vite ^6, Tailwind ^4; CI PHP 8.4. Versi runtime tidak diperiksa. PHP constraint Composer masih ^8.2; tidak diubah |
| Package | `composer.json`, `package.json` | Permission, Activitylog, Media Library, Ziggy dan JsBarcode sudah dideklarasikan; tidak perlu instalasi untuk mockup |
| Model organisasi | `AGENTS.md` dan konfirmasi user | Single company, multi-gudang; tidak menggunakan tenancy/cabang |
| Module loading | `app/Providers/AppServiceProvider.php` | Auto-register `app/Modules/*/*/ServiceProvider.php`; tidak perlu menambah registry global untuk modul baru kelak |
| UI utama | `resources/js/layouts/app/app-sidebar-layout.tsx`, `resources/css/app.css` | Sidebar admin, fullscreen POS, token theme existing; tidak diganti oleh mockup |
| Navigasi | `resources/js/components/app-sidebar.tsx`, `resources/js/components/command-palette.tsx` | Menu permission-aware dan command palette sudah ada; penambahan hardware memerlukan izin exact-file tersendiri |
| Panduan/tabel | `resources/js/components/shared/page-guide/page-guide.tsx`, `resources/js/components/shared/table-pagination.tsx` | Reuse F1/Sheet dan default tabel global 10; pilihan 10/25/50/100 kelak, tidak membuat design system lain |
| RBAC | `app/Modules/Platform/Identity/ServiceProvider.php` | Gate::before super-system guard web; bypass hanya authorization, bukan invariant |
| POS HTTP | `app/Modules/Sales/POS/Routes/web.php`, `Presentation/Controllers/POSController.php` dalam module tersebut | Inertia + JSON session-auth web, bukan SPA REST API token baru. Scan memakai can:sales.pos.invoices.create |
| Checkout | `app/Modules/Sales/POS/Application/Actions/PostInvoice.php` | Quote ulang, shift aktif, FIFO dan pembayaran di transaction bersama; key per actor, request hash dan replay snapshot. Tidak ditemukan dispatch hardware di action ini |
| Pembayaran/stok | Contract `SalePaymentWriter.php` pada Finance/Payments dan `SaleStockWriter.php` pada Inventory/Stock | Authority tetap pada pemilik module; hardware tidak boleh memanggil writer bisnis untuk mencetak |
| Detail penjualan | `app/Modules/Sales/POS/Presentation/Controllers/InvoiceController.php`, `Application/Queries/GetInvoice.php` | Snapshot melalui GET; kasir pemilik atau pemegang hak view-all. Bukan akses global invoice bagi setiap user hardware |
| Barcode master | `app/Modules/Inventory/Items/Database/Migrations/2026_10_09_000003_create_items_tables.php` | barcode nullable unik per item-unit, max64; termasuk reservasi data soft-deleted. Tidak menambah barcode kedua milik hardware |
| Scan POS | `resources/js/pages/sales/pos/hooks/use-pos-scanner.ts`, `lib/barcode.ts` | Antrean scan maksimal20, mempertahankan scan berulang, membatalkan konteks lama, tidak scan saat dialog; stok/harga tetap divalidasi |
| Input scanner | `resources/js/components/shared/barcode-input.tsx` | Enter pada input fokus, bukan listener keyboard global; leading zero tidak dikonversi angka |
| Scan pembelian | `resources/js/pages/purchasing/purchase-orders/lib/barcode.ts` | Cocokkan barcode satuan, tambah qty, pertahankan biaya beli; satuan berbeda pada barang yang sama ditolak. Tidak posting stok |
| Cetak label existing | `resources/js/pages/inventory/items/components/barcode-label-dialog.tsx`, `barcode-label.css`, `../lib/barcode.ts` | JsBarcode Code128 + browser print A4/label tunggal; copies1..100, lebar40..190, tinggi35..80 mm. Bukan transport TSPL/CPCL |
| Cetak struk existing | `resources/js/pages/sales/pos/components/receipt-dialog.tsx`, `resources/js/components/shared/receipt-print.tsx` | Snapshot transaksi posted melalui window.print(); bukan bukti cetak fisik atau silent print |
| Preferensi cetak | `app/Modules/Platform/OperationalSettings/Application/Contracts/PrintingPreferences.php` | Public printing() mengembalikan paper; ukuran struk A4/58/80 tetap bukan profil media CT420D |

## Contract Yang Dipertahankan

| Route source | Contract existing |
| --- | --- |
| GET `/pos/scan` | warehouse_id + customer_id + code string ASCII printable max64; trim existing dipertahankan; hasil item dan unit_id; bukan mutation |
| GET `/pos/catalog` | Konteks gudang/pelanggan, search/page, category_id/brand_id; harga dari level pelanggan dan satuan |
| POST `/pos/quote` | Validasi keranjang server; bukan reservasi |
| POST `/pos/invoices` | Settlement lunas idempotent, shift wajib, FIFO/stock/payment/invoice atomic |
| GET `/pos/invoices/{id}` | Snapshot berizin tanpa HPP/credential untuk receipt |
| GET `/inventory/items` | Master barang dengan inventory.view; bukan API khusus hardware |

Tidak menambahkan tenant_id/branch_id, terminal_id, raw commands atau flag
hardware pada payload checkout existing. Format error dan CSRF tetap Laravel.
Label harga kelak wajib memilih satuan dan level pelanggan existing; tidak
ada konsep harga cabang. Label kode saja dapat dibuat tanpa harga.

## Perbedaan Dengan Bundle Generik

1. **Tenant/cabang:** tidak berlaku atas keputusan eksplisit user. Bukan scope
   yang akan dikerjakan. Assignment perangkat dan RBAC tetap diperlukan.
2. **SaaS/REST:** deployment POS existing tidak dipindah. Gateway outbound,
   jika kelak disetujui, berkomunikasi dengan server POS, tidak wajib cloud.
3. **Cetak/scan sudah ada:** jangan duplikasi katalog/barcode atau mengganti
   BarcodeInput, usePosScanner, label dialog dan receipt existing.
4. **Perangkat:** menurut profil bundle, CT420D printer label TSPL/CPCL;
   HC-4208DB scanner. Spesifikasi berasal dari bundle, belum diverifikasi
   terhadap unit/manual/firmware aktual pada sesi ini. Jangan klaim LAN,
   ESC/POS, cutter, drawer, silent Bluetooth atau printer struk untuk CT420D.
5. **Dimensi:** batas label browser existing berbeda dari spesifikasi media
   CT420D pada bundle. Jangan mengubah batas existing untuk memaksakan driver;
   media/printable width/gap/margin kelak divalidasi pada adapter tersendiri.
6. **Cash drawer:** CashDrawerPosition di Finance adalah posisi uang laci,
   bukan kemampuan fisik membuka drawer. Tidak boleh menyimpulkan dukungan
   pulse hardware dari nama contract tersebut.
7. **TASK-HW-001:** README bawaan secara eksplisit discovery/mockup only.
   Tidak memperluasnya menjadi implementasi workspace shell/harness.

## Peta Verifikasi Existing, Belum Dijalankan Ulang

| Bukti yang tersedia di source | Kegunaan setelah implementasi kelak disetujui |
| --- | --- |
| `tests/Feature/Modules/Sales/POS/PosCartTest.php` | Permission/konteks scan, leading zero, harga level/unit, stok agregat |
| `tests/Feature/Modules/Sales/POS/PosCheckoutTest.php` | Atomicity, replay, CSRF, HPP, audit rollback dan snapshot |
| `tests/Feature/Modules/Sales/POS/CashierShiftTest.php` | Shift/laci eksklusif dan guard checkout |
| `tests/Feature/Modules/Sales/POS/InvoiceHistoryTest.php` | Ownership dan pembacaan historis |
| `tests/Feature/Modules/Inventory/Items/ItemsHttpTest.php` | Hak akses master dan barcode unik termasuk data terhapus |
| `tests/Unit/Modules/Inventory/Stock/FifoAllocationTest.php` | Kalkulasi FIFO existing |
| `scripts/qa/barcode-unit.mjs` | Encoding, leading zero, scan dua kali, stok/satuan/biaya pembelian |
| `scripts/qa/barcode-browser.mjs`, `scripts/qa/pos-checkout-browser.mjs` | Calon regresi browser; menjalankan server/fixture sehingga tidak dijalankan pada fase ini |
| `phpunit.xml` | Testing SQLite memory dan cache path testing terpisah; jangan memuat cache produksi |

Tidak ada test runtime atau gate produksi yang diklaim PASS dari pembacaan
source. Belum ada bukti feature-OFF executable karena fitur belum dibuat.

## Risiko Yang Tetap Terbuka

- Manual/protokol CT420D yang sesuai firmware belum diperiksa; command builder
  tidak boleh dibuat berdasarkan tebakan sintaks.
- Android tablet, pairing USB/Bluetooth dan media label tetap PHYSICAL_PENDING.
- Mekanisme persistent hardware job/token/registry belum ada dalam surface yang
  diperiksa; desain serta migrasinya perlu PLAN dan izin terpisah.
- Namespace backend hardware dan exact-file integration seam belum ditetapkan
  sebagai keputusan final. Kandidat Platform/Hardware mengikuti struktur
  existing, bukan izin generator atau pembuatan folder.
- Status staged LSP user tidak boleh ikut commit. Companion sendiri untracked;
  tidak ada operasi commit/push pada pekerjaan ini.
