# Status Integrasi Hardware POS

**Diperbarui:** 21 November 2026.

| Bagian | Status aktual |
| --- | --- |
| Lokasi companion | pos-hardware-integration/ di root POS existing |
| Discovery dan mockup TASK-HW-001 | COMPLETE; approved, commit5e3b4a5 sudah di origin/main |
| Organisasi POS | Single company + multi-gudang; tanpa tenant/multi-cabang sesuai user |
| TASK-HW-002 scanner virtual | COMPLETE; commit96bddfe sudah di origin/main |
| TASK-HW-011 akses produksi scanner | COMPLETE, APPROVED; diagnostik ON di instalasi toko |
| Source module POS existing | Tidak diubah; penambahan module Platform/Hardware baru saja |
| Flag hardware | Default source OFF; instalasi toko ON, cached production terverifikasi |
| Permission | inventory.view existing; tidak ada grant/role baru pada toko |
| Regresi |69 test/680 assertion PASS; focused checks sesudahnya sesuai REPORT |
| Browser scanner |12 cek production-like PASS; fingerprint71 tabel bisnis QA identik |
| Software acceptance | VIRTUAL_PASSED untuk scanner saja |
| Physical acceptance | PHYSICAL_PENDING |
| CT420D | Profil printer LABEL TSPL/CPCL; driver/media/cetak/scan-back belum diuji |
| HC-4208DB | Input keyboard sintetik diuji; USB/dongle/Bluetooth nyata belum diuji |
| Gateway/print transport/label commands | Belum diimplementasikan atau diaktifkan |
| Deployment/pilot | Akses diagnostik production APPROVED/ON; pilot perangkat belum disahkan |
| Server/browser QA | Sudah ditutup; artefak ignored tersimpan lokal |

Bukti dan batas: [REPORT TASK-HW-002](tasks/TASK-HW-002/REPORT.md).
Aktivasi produksi: [REPORT TASK-HW-011](tasks/TASK-HW-011/REPORT.md).
Blueprint generik masih usulan; istilah tenant/cabang/SaaS tidak mengganti
model POS existing. Tidak ada task lain yang otomatis diizinkan.
