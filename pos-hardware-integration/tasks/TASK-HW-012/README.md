# TASK-HW-012: Gateway QZ Dan Konfigurasi Perangkat Label

**Status:** Implementasi dan aktivasi aplikasi selesai; penutupan Git berjalan.
**Tanggal:** 21 November 2026.
**Module utama:** Platform/Hardware.
**Integrasi terbatas:** Inventory/Items, shell navigasi dan shared feature props.

## Arahan Yang Sudah Dikonfirmasi

- Label CASHCOW CT420D dan scanner HC-4208DB, tanpa printer struk.
- QZ Tray sebagai gateway Windows/Linux, dipasang manual oleh admin.
- Cari/pilih printer terpasang di OS; scanner mode keyboard dikonfirmasi lewat
  tes scan, bukan mengklaim merek scanner terdeteksi otomatis.
- Sertifikat cetak senyap merupakan konfigurasi terpisah; tidak dipasang otomatis.

Exact PLAN version1 disetujui beserta source/SDK/QA/migration/seeder/cache dan
commit/push; lihat APPROVAL. PLAN tidak ditulis ulang setelah persetujuan.
Baseline efbf811; file LSP user tetap dipertahankan dan tidak ikut task.

## Paket Yang Diselesaikan

Satu alur operasional utuh: konfigurasi printer, penemuan printer QZ,
pilih barang/satuan, pratinjau label, kirim berizin dengan idempotency,
riwayat hasil gateway dan tes scanner/scan-back. Driver gambar OS dipakai;
tidak menebak TSPL/CPCL tanpa manual CT420D. Hasil fisik tetap pending UAT.

- [Kebutuhan dan rancangan layar](PRD.md).
- [PLAN dan exact allowlist](PLAN.md).
- [Checklist incremental](tasks.md).
- [Persetujuan implementasi](APPROVAL.md).
- [Laporan hasil dan batas aktivasi](REPORT.md).

Workspace https://pos.test/platform/hardware tersedia setelah login/izin.
Tiga tabel Hardware dan4 permission sudah diaktifkan setelah backup/restore
disposable PASS; tidak ada grant atau perubahan data bisnis existing.
Software VIRTUAL_PASSED, physical PHYSICAL_PENDING. Printing tetap OFF;
QZ desktop/driver/cert tidak dipasang otomatis.

Setelah approval, increment dikerjakan sampai paket selesai tanpa meminta izin
setiap langkah kecil. Pertanyaan baru hanya jika scope berubah atau keputusan
bisnis/keamanan benar-benar menghambat. QA fokus selama coding, satu gate akhir.
