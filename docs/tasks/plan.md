# Rencana Implementasi: POS Modular ERP-Lite

Status: Draf aktif.

## Ringkasan

Implementasi dilakukan bertahap berdasarkan work-item. Urutan dimulai dari audit source, baseline dokumentasi, scaffold modul, identity/access, company/settings, catalog/pricing, inventory FIFO, lalu transaksi purchasing dan sales.

## Keputusan Arsitektur

- Modular monolith dengan `app/Modules/{Category}/{Module}`.
- ULID untuk primary key.
- FIFO per item dan gudang.
- Stok tidak boleh minus.
- Finance Lite sebelum full accounting.
- Starter kit auth, Spatie Permission, Spatie Activitylog.
- shadcn/ui sebagai default UI/UX aplikasi.

## Fase

### Fase 0: Baseline

- [x] WI-0001: Audit Baseline Source Laravel dan Tooling
- [x] WI-0002: Review Baseline Dokumentasi dan Arsitektur

Checkpoint:

- [x] Command sudah terverifikasi.
- [x] Dokumen sesuai dengan source.
- [x] Belum ada coding fitur.
- [x] Work-item sudah memakai format modul target, increment, checklist, dan QA automated.

### Fase 1: Fondasi

- [ ] WI-0003: Scaffold Folder dan Loader Modul
- [ ] WI-0004: Baseline Identitas dan Akses
- [ ] WI-0005: Baseline Pengaturan Perusahaan dan Penomoran
- [ ] WI-0014: Baseline shadcn/ui dan Layout Aplikasi

Checkpoint:

- [ ] Route dan migration modul berhasil dimuat.
- [ ] Auth dan permission berjalan.
- [ ] Generate nomor sudah dites.
- [ ] Baseline shadcn/ui dan layout aplikasi terverifikasi.

### Fase 2: Master Data dan Fondasi Persediaan

- [ ] WI-0006: Katalog Multi Satuan dan Harga Level Pelanggan
- [ ] WI-0007: Master Supplier Pelanggan dan Sales
- [ ] WI-0008: Fondasi Persediaan FIFO

Checkpoint:

- [ ] Konversi satuan sudah dites.
- [ ] Lookup harga sudah dites.
- [ ] Consume FIFO sudah dites.
- [ ] Stok minus ditolak.

### Fase 3: Transaksi

- [ ] WI-0009: Posting Pembelian dan Hutang
- [ ] WI-0010: Posting Penjualan POS dan Piutang
- [ ] WI-0011: Retur Penjualan dan Pembelian
- [ ] WI-0012: Transfer Gudang dan Stok Opname

Checkpoint:

- [ ] Pembelian membuat FIFO layer.
- [ ] Penjualan consume FIFO layer.
- [ ] Multi pembayaran berjalan.
- [ ] Retur dibatasi oleh transaksi asal.
- [ ] Transfer membawa cost FIFO.

### Fase 4: Laporan

- [ ] WI-0013: Laporan Operasional Fase 1

Checkpoint:

- [ ] Laporan memakai transaksi posted.
- [ ] Laporan stok sesuai movement.
- [ ] Laporan laba memakai COGS FIFO.

## Risiko dan Mitigasi

| Risk | Impact | Mitigation |
| --- | --- | --- |
| Logika FIFO salah | Tinggi | Unit test dan feature test wajib ada sebelum Sales/Purchasing bergantung padanya |
| Scope melebar ke full accounting | Tinggi | Ikuti ADR-0004 dan jaga fase 1 tetap Finance Lite |
| Transaksi posted diedit langsung | Tinggi | Tegakkan lifecycle dan test authorization |
| Multi-unit conversion drift | Tinggi | Snapshot conversion on transaction lines |
| Route/module loader conflicts | Sedang | Verify route:list and migrate:status after scaffold |

## Pertanyaan Terbuka

- Forgot password enabled or disabled in phase 1.
- Warehouse access strict from phase 1 or permissive seed first.
- Browser print first or direct thermal printer integration.
