# Status Integrasi Hardware POS

**Diperbarui:** 21 November 2026.

| Bagian | Status aktual |
| --- | --- |
| Lokasi companion | pos-hardware-integration/ di root POS existing |
| Discovery dan mockup TASK-HW-001 | COMPLETE; approved, commit5e3b4a5 sudah di origin/main |
| Organisasi POS | Single company + multi-gudang; tanpa tenant/multi-cabang sesuai user |
| TASK-HW-002 scanner virtual | COMPLETE; commit96bddfe sudah di origin/main |
| TASK-HW-011 akses produksi scanner | COMPLETE, APPROVED; diagnostik ON, implementasi dipush826056d |
| TASK-HW-012 gateway label QZ | APPROVED; implementasi/QA/aktivasi aplikasi COMPLETE; penutupan Git berjalan |
| Source module POS existing | Hanya seam Items contract/barcode helper dan shell yang diizinkan; alur transaksi tidak diubah |
| Flag hardware | Default source OFF; instalasi toko ON, cached production terverifikasi |
| Permission |4 key Hardware baru; inventory.view existing; role/grant toko tidak berubah |
| Regresi TASK-HW-012 |79 test/774 assertion terfokus PASS; typecheck/lint/build/guides PASS |
| Browser label/scanner TASK-HW-012 |12 cek production-like PASS; fingerprint data bisnis/grants QA identik |
| Software acceptance | VIRTUAL_PASSED scanner dan label gateway driver-image |
| Physical acceptance | PHYSICAL_PENDING |
| CT420D | Profil printer LABEL TSPL/CPCL; driver/media/cetak/scan-back belum diuji |
| HC-4208DB | Input keyboard sintetik diuji; USB/dongle/Bluetooth nyata belum diuji |
| Gateway/print transport/label commands | SDK QZ driver-image terimplementasi; konfigurasi/preview tersedia, printing OFF |
| Database Hardware |3 tabel baru kosong;4 permission; backup restore-disposable dan fingerprint69 tabel existing PASS |
| Deployment/pilot | Akses diagnostik production APPROVED/ON; pilot perangkat belum disahkan |
| Server/browser QA | Sudah ditutup; artefak ignored tersimpan lokal |

Bukti dan batas: [REPORT TASK-HW-002](tasks/TASK-HW-002/REPORT.md).
Aktivasi produksi: [REPORT TASK-HW-011](tasks/TASK-HW-011/REPORT.md).
Hasil label/scanner: [REPORT TASK-HW-012](tasks/TASK-HW-012/REPORT.md).
SDK qz-tray2.3.0 terpasang; QZ desktop/driver/printer fisik tidak dipasang.
Pengiriman tetap OFF sampai persetujuan/UAT fisik. Npm audit existing belum
bersih; lihat residual dependency pada REPORT, bukan auto-upgrade scope lain.
Blueprint generik masih usulan; istilah tenant/cabang/SaaS tidak mengganti
model POS existing. Tidak ada task lain yang otomatis diizinkan.
