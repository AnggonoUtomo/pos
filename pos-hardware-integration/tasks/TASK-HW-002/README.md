# TASK-HW-002: Uji Scanner Virtual Read-Only

**Status:** COMPLETE: SCANNER VIRTUAL; FISIK PENDING, PRODUKSI BELUM DIIZINKAN.
**Tanggal:** 20 November 2026.
**Validasi akhir rencana:** 21 November 2026; validator struktur PASS.
**Module:** Platform/Hardware, tanpa perubahan source module POS existing.

TASK-HW-001 sudah disetujui dan dipush pada commit
`5e3b4a51ca3da1689ee224ffc393f6971b564984`. User meminta commit/push terlebih
dahulu, kemudian lanjut. PLAN TASK-HW-002 kemudian disetujui eksplisit pada
21 November2026 dan diimplementasikan sesuai allowlist.

## Hasil Yang Diserahkan

Satu alur utuh pada halaman uji scanner: input teks/Enter yang disimulasikan,
lookup katalog berizin melalui contract Items existing, tampilkan barang dan
satuan yang dikenali, lalu catat hasil uji sementara. Tidak menambah keranjang,
mengubah qty stok, harga, penjualan, pembelian atau pembayaran.

- Feature flag default OFF; halaman hanya dapat aktif pada local/testing.
- Tidak ada menu/palette baru atau perubahan halaman kasir existing.
- Fixture CCTV/kelistrikan hanya database disposable QA, bukan data toko.
- Fake scanner hanya test harness, bukan implementasi scanner USB/Bluetooth.
- Print/gateway/TSPL/CPCL/scan-back fisik adalah slice terpisah, tidak dibuat
  sebagai placeholder atau diselipkan pada task ini.
- Tidak ada tenant, cabang atau perubahan model perusahaan/gudang.

## Dokumen

- [PLAN dan exact file allowlist](PLAN.md).
- [Checklist incremental](tasks.md).
- [APPROVAL implementasi](APPROVAL.md).
- [REPORT dan bukti verifikasi](REPORT.md).
- [Baseline source yang dipakai](../TASK-HW-001/BASELINE.md).
- [Desain workspace yang disetujui](../TASK-HW-001/MOCKUP.md).

Slice ini menerapkan bagian Uji scanner dari desain yang sudah disetujui,
bukan seluruh tab perangkat/printer/antrean sekaligus. Tidak menampilkan tab
yang belum berfungsi sebagai fitur aktif. Halaman ini tidak menggantikan
scanner POS/pembelian yang sudah ada.

## Status Akhir

Software acceptance: VIRTUAL_PASSED (scanner slice saja)
Physical acceptance: PHYSICAL_PENDING
Deployment authorization: NOT_APPROVED
Existing POS regression: PASSED (subset sesuai PLAN)

Implementasi dan QA memakai approval khusus TASK-HW-002, bukan approval desain
TASK-HW-001. Browser final12 cek PASS; fingerprint71 tabel bisnis identik.
Regresi backend69 test/680 assertion PASS; focused checks tambahan, build,
typecheck, lint dan panduan tercatat di REPORT. Server QA sudah dihentikan.

User mengizinkan commit/push TASK-HW-002 pada 21 November 2026. Source existing,
database toko dan flag produksi tidak diubah. PLAN tetap snapshot yang
disetujui; status pelaksanaan aktual ada di APPROVAL/tasks/REPORT.
