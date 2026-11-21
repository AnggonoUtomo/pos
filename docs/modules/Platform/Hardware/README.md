# Platform/Hardware: Perangkat POS

Module additive untuk diagnostik scanner serta konfigurasi printer label QZ,
preview, pengiriman eksplisit dan riwayat. Tidak menggantikan scanner POS,
pembelian atau cetak label browser existing. Tidak mengubah harga, stok/FIFO,
pembayaran, invoice atau checkout.

## Boundary

Presentation menangani HTTP/auth; Application mengorkestrasi transaksi, audit,
snapshot dan claim; Domain menjaga ukuran/lifecycle murni; Infrastructure
mengimplementasikan persistence dan runtime availability. ServiceProvider
merupakan composition root. Dependency Inventory/Items hanya melalui public
SaleItemCatalog dan ItemLabelCatalog beserta DTOs.

Profil printer tersimpan dengan version dan soft delete. Job/attempt/audit
dipertahankan tanpa soft delete. Riwayat uji scanner tetap sementara di tab.
Demo seeder sengaja no-op; perangkat nyata dipilih admin, bukan data demo.

## Akses Dan Aktivasi

Flag default OFF. POS_HARDWARE_ENABLED=true membuka akses termasuk production,
tetap dengan login dan inventory.view untuk memakai halaman
`/platform/hardware/scanner`. Auth serta middleware verified mengikuti starter
kit existing. Workspace baru /platform/hardware memakai permission
platform.hardware.view/manage/print/reprint per action, serta menu/palette
bersyarat flag/permission. Seeder key tidak mengubah role/grant akun.
Setelah perubahan flag/deployment, admin membangun ulang config dan route cache.
Flag OFF menolak404 termasuk super-system dan route yang sudah dicache.
Aktivasi instalasi toko dan buktinya dicatat pada TASK-HW-011, bukan disimpulkan
dari default source. Scanner mode keyboard/HID memakai input fokus + Enter;
belum ada verifikasi perangkat fisik.

POS_HARDWARE_PRINTING_ENABLED default OFF, terpisah dari konfigurasi/preview.
TASK-HW-012 tidak mengaktifkan cetak fisik atau memasang aplikasi OS.
QZ disiapkan sekali melalui installer resmi/helper admin Windows; driver tetap
terpisah. Discovery berjalan sekali saat workspace dibuka oleh pemegang izin
manage/print, dengan Deteksi ulang manual dan tanpa polling di module lain.
Printer yang ditemukan adalah daftar OS,
bukan bukti CT420D siap cetak. Scanner keyboard/HID tidak dideteksi mereknya.
Tablet membutuhkan print-server WSS dan trust/allowlist yang diatur admin,
bukan localhost tablet. Hasil submitted berarti diterima gateway, bukan
terbukti tercetak. Software acceptance dan physical acceptance dipisahkan.

## Acuan

- [Spesifikasi](specification.md).
- [Panduan operator](../../../user-guide/UJI-SCANNER.md).
- [Printer label](../../../user-guide/PRINTER-LABEL.md).
- [Setup awal](../../../user-guide/SETUP-PERANGKAT.md).
- [PLAN dan approval task](../../../../pos-hardware-integration/tasks/TASK-HW-002/README.md).
- [Akses produksi diagnostik](../../../../pos-hardware-integration/tasks/TASK-HW-011/README.md).
- [Gateway label](../../../../pos-hardware-integration/tasks/TASK-HW-012/README.md).

Pengujian sintetik bukan bukti HC-4208DB/CT420D bekerja secara fisik.
