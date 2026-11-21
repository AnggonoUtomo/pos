# Platform/Hardware: Uji Scanner

Module additive untuk memeriksa identitas barang/satuan jual melalui input
barcode. Bukan scanner baru pengganti POS atau pembelian. Tidak membuat
transaksi, menambah keranjang, mengubah harga atau mengurangi stok.

## Boundary

Controller/request/middleware pada Presentation; lookup pada Application.
Dependency lintas module hanya public Inventory/Items SaleItemCatalog dan
DTOs terkait. Tidak membuat Domain/adapter/repository tanpa kebutuhan.
Riwayat uji hanya memori tab; tidak ada tabel atau audit business mutation.

## Akses Dan Aktivasi

Flag default OFF. POS_HARDWARE_ENABLED=true membuka akses termasuk production,
tetap dengan login dan inventory.view untuk memakai halaman
`/platform/hardware/scanner`. Auth serta middleware verified mengikuti starter
kit existing. Tidak ada menu/palette baru, grant atau perubahan database toko.
Setelah perubahan flag/deployment, admin membangun ulang config dan route cache.
Flag OFF menolak404 termasuk super-system dan route yang sudah dicache.
Aktivasi instalasi toko dan buktinya dicatat pada TASK-HW-011, bukan disimpulkan
dari default source. Scanner mode keyboard/HID memakai input fokus + Enter;
belum ada verifikasi perangkat fisik atau integrasi printer/gateway.

## Acuan

- [Spesifikasi](specification.md).
- [Panduan operator](../../../user-guide/UJI-SCANNER.md).
- [PLAN dan approval task](../../../../pos-hardware-integration/tasks/TASK-HW-002/README.md).
- [Akses produksi diagnostik](../../../../pos-hardware-integration/tasks/TASK-HW-011/README.md).

Pengujian sintetik bukan bukti HC-4208DB/CT420D bekerja secara fisik.
