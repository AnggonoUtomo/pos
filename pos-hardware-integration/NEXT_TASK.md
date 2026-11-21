# Langkah Berikutnya

**Diperbarui:** 21 November 2026.

TASK-HW-002 scanner virtual selesai. Lihat
[REPORT](tasks/TASK-HW-002/REPORT.md) dan [checklist](tasks/TASK-HW-002/tasks.md).
Tidak ada coding task berikutnya yang diizinkan oleh penyelesaian ini.

1. TASK-HW-002 sudah dipush pada96bddfe, tanpa LSP user. TASK-HW-011 disetujui
   dan selesai: [REPORT](tasks/TASK-HW-011/REPORT.md). Halaman diagnostik ON di
   https://pos.test/platform/hardware/scanner; login/inventory.view wajib.
   Menunggu perangkat untuk UAT fisik, bukan mengklaim hardware sudah verified.
2. Jika pemilik meminta kelanjutan label/gateway, susun PLAN exact-file
   vertical slice tersendiri. Reuse barcode/katalog/cetak browser existing;
   jangan mengubah checkout atau membuat OS runner diam-diam.
3. Pastikan manual TSPL/CPCL sesuai CT420D tersedia sebelum command builder.
   Driver/gateway nyata dan physical scan-back memerlukan izin/UAT tersendiri.
4. FakeGateway/FakePrintTransport dan preview/snapshot baru dibuat bila punya
   consumer dalam PLAN yang disetujui. Jangan menyebut scanner virtual sebagai
   hardware USB/Bluetooth verified atau label printer siap produksi.

Tetap single company, multi-gudang, tanpa tenant/cabang. Flag toko ON hanya
untuk diagnostik scanner; printer/gateway belum dibuat. Jangan mengubah .env,
root AGENTS, module existing, dependency, database toko atau pending MVP
di luar arahan user dan PLAN baru yang disetujui.
