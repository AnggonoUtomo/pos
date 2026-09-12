# Daftar Work-Item

Status awal: Draf. Work-item ini menjadi backlog implementasi POS modular.

## Legenda Status

- `Draf`: belum siap dikerjakan.
- `Siap`: siap dikerjakan.
- `Sedang Dikerjakan`: sedang dikerjakan.
- `Blocked`: menunggu keputusan atau dependency.
- `Selesai`: selesai dan sudah diverifikasi.

## Daftar Work-Item

| ID | Judul | Status | Bergantung Pada | File |
| --- | --- | --- | --- | --- |
| WI-0001 | Audit Baseline Source Laravel dan Tooling | Draf | Tidak ada | `docs/work-items/WI-0001-baseline-laravel-source-and-tooling-audit.md` |
| WI-0002 | Review Baseline Dokumentasi dan Arsitektur | Draf | WI-0001 | `docs/work-items/WI-0002-documentation-and-architecture-baseline-review.md` |
| WI-0003 | Scaffold Folder dan Loader Modul | Sedang Dikerjakan | WI-0002 | `docs/work-items/WI-0003-module-loader-and-folder-scaffold.md` |
| WI-0004 | Baseline Identitas dan Akses | Draf | WI-0003 | `docs/work-items/WI-0004-identity-access-baseline.md` |
| WI-0005 | Baseline Pengaturan Perusahaan dan Penomoran | Draf | WI-0004 | `docs/work-items/WI-0005-company-settings-and-numbering-baseline.md` |
| WI-0006 | Katalog Multi Satuan dan Harga Level Pelanggan | Draf | WI-0005 | `docs/work-items/WI-0006-catalog-multi-unit-and-customer-level-pricing.md` |
| WI-0007 | Master Supplier Pelanggan dan Sales | Draf | WI-0006 | `docs/work-items/WI-0007-parties-supplier-customer-sales.md` |
| WI-0008 | Fondasi Persediaan FIFO | Draf | WI-0006 | `docs/work-items/WI-0008-inventory-fifo-foundation.md` |
| WI-0009 | Posting Pembelian dan Hutang | Draf | WI-0008 | `docs/work-items/WI-0009-purchasing-posting-and-payables.md` |
| WI-0010 | Posting Penjualan POS dan Piutang | Draf | WI-0008, WI-0009 | `docs/work-items/WI-0010-sales-pos-posting-and-receivables.md` |
| WI-0011 | Retur Penjualan dan Pembelian | Draf | WI-0009, WI-0010 | `docs/work-items/WI-0011-returns-sales-and-purchase.md` |
| WI-0012 | Transfer Gudang dan Stok Opname | Draf | WI-0008 | `docs/work-items/WI-0012-warehouse-transfer-and-stock-opname.md` |
| WI-0013 | Laporan Operasional Fase 1 | Draf | WI-0009, WI-0010, WI-0011, WI-0012 | `docs/work-items/WI-0013-operational-reports-phase-1.md` |
| WI-0014 | Baseline shadcn/ui dan Layout Aplikasi | Draf | WI-0003, WI-0004 | `docs/work-items/WI-0014-shadcn-ui-and-application-layout-baseline.md` |

## Gerbang

Sebelum mengubah kode untuk work-item mana pun:

- Work-item harus minimal berstatus `Siap`.
- Checklist sebelum coding harus terisi.
- Command verifikasi yang relevan harus disebut.
- Modul target harus disebut sebelum coding.
- Rencana increment harus tertulis dengan checklist sebelum/sesudah dan QA automated.
- Scope harus jelas dan tidak bercampur dengan work-item lain.
