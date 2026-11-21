# Langkah Berikutnya

**Diperbarui:** 21 November 2026.

Prioritas baru: [TASK-HW-013](tasks/TASK-HW-013/README.md). User meminta QZ
desktop disiapkan pada PC ini dan memilih scanner keyboard langsung scan + tes
kesiapan. PLAN exact33 path APPROVED beserta Git; user mengambil alih instalasi
manual QZ, jadi agent tidak download/install/upgrade kedua. Source/helper/docs
selesai, native read-only PASS (2.3.0+1,6 printer, tanpa print), QA browser
akhir12 cek PASS. [REPORT013](tasks/TASK-HW-013/REPORT.md) tersedia; source
d00cb82/report aa38d54 dipush dan remote diverifikasi. Jangan mengubah
trust/startup OS user atau menyalakan cetak fisik.

Permintaan terbaru: cari/unduh driver CT420D dan simpan dalam project, tanpa
install. Discovery web belum menemukan kecocokan model/download resmi; katalog
[katalog Cashcow](https://www.cashcow.co.id/our-products/thermal-printer/)
menampilkan CT-428/CT-428S. Tunggu foto label/model atau
link pembelian dari user, jangan mengganti driver dengan model mirip. Belum
download/install driver, belum ada perubahan source atau schema untuk permintaan ini.

TASK-HW-002 scanner virtual selesai. Lihat
[REPORT](tasks/TASK-HW-002/REPORT.md) dan [checklist](tasks/TASK-HW-002/tasks.md).
Tidak ada coding task berikutnya yang diizinkan oleh penyelesaian ini.

1. TASK-HW-002 sudah dipush pada96bddfe, tanpa LSP user. TASK-HW-011 disetujui
   dan selesai: [REPORT](tasks/TASK-HW-011/REPORT.md). Halaman diagnostik ON di
   https://pos.test/platform/hardware/scanner; login/inventory.view wajib.
   Menunggu perangkat untuk UAT fisik, bukan mengklaim hardware sudah verified.
2. [TASK-HW-012](tasks/TASK-HW-012/REPORT.md) telah disetujui dan diselesaikan:
   workspace https://pos.test/platform/hardware, SDK QZ, profil/preview/job/history
   dan tes scanner tersedia. Migration/permission/cache diaktifkan setelah backup
   verified, tanpa grant/data bisnis berubah. Physical print tetap OFF.
3. Langkah perangkat berikutnya menunggu arahan pemilik dan hardware: admin
   memasang QZ/driver manual, uji media/ukuran/cetak/scan-back, lalu putuskan pilot.
   Driver-image bukan TSPL/CPCL raw; jangan menebak command firmware.
4. Advisory dependency existing dicatat di REPORT012. Perbaikannya memerlukan
   scope/approval tersendiri; tidak menjalankan audit fix atau fitur lain otomatis.

Tetap single company, multi-gudang, tanpa tenant/cabang. Flag toko ON hanya
untuk akses module/diagnostik, bukan pengiriman cetak. Jangan mengubah .env,
root AGENTS, module existing, dependency, database toko atau pending MVP
di luar arahan user dan PLAN baru yang disetujui.
