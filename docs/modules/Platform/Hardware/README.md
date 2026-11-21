# Platform/Hardware: Uji Scanner Virtual

Module additive untuk memeriksa identitas barang/satuan jual melalui input
barcode. Bukan scanner baru pengganti POS atau pembelian. Tidak membuat
transaksi, menambah keranjang, mengubah harga atau mengurangi stok.

## Boundary

Controller/request/middleware pada Presentation; lookup pada Application.
Dependency lintas module hanya public Inventory/Items SaleItemCatalog dan
DTOs terkait. Tidak membuat Domain/adapter/repository tanpa kebutuhan.
Riwayat uji hanya memori tab; tidak ada tabel atau audit business mutation.

## Akses Dan Aktivasi

Flag default OFF dan production selalu ditolak404. Hanya local/testing dengan
POS_HARDWARE_ENABLED=true dan inventory.view yang dapat memakai halaman
`/platform/hardware/scanner`. Auth serta middleware verified mengikuti starter
kit existing. Tidak ada menu/palette baru, grant atau aktivasi database toko.
Jika config/route cache deployment digunakan, admin mengelolanya terpisah;
task ini tidak mengubah cache produksi.

## Acuan

- [Spesifikasi](specification.md).
- [Panduan operator](../../../user-guide/UJI-SCANNER.md).
- [PLAN dan approval task](../../../../pos-hardware-integration/tasks/TASK-HW-002/README.md).

Pengujian sintetik bukan bukti HC-4208DB/CT420D bekerja secara fisik.
