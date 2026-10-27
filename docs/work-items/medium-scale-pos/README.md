# Pengembangan POS Skala Menengah

## Status

Diaktifkan user pada 27 Oktober 2026. Persetujuan mencakup arah tiga tahap,
bukan persetujuan otomatis atas seluruh aturan bisnis yang masih terbuka.
Pengerjaan dimulai dari master barang: identifikasi barcode dan gambar melalui
Spatie Laravel Media Library. Instalasi package diizinkan secara eksplisit.

## Tujuan

Mempermudah pekerjaan toko CCTV/kelistrikan tanpa mengubah baseline transaksi
lunas yang sudah berjalan. Setiap capability diselesaikan sebagai alur utuh,
dengan permission, audit, panduan, dan verifikasi yang proporsional.

## Peta Capability

ID berikut adalah identifier pekerjaan, bukan instruksi membuat module baru.
Ownership baru hanya ditetapkan ketika requirement-nya sudah cukup jelas.

| ID | Ownership | Tanggung jawab | Prasyarat |
| --- | --- | --- | --- |
| item-identification | Inventory/Items; consumer Sales/POS | Barcode per satuan, gambar barang dan tampilan katalog | Master satuan/barang dan katalog existing |
| held-carts | Sales/POS | Menahan, membuka kembali, dan membatalkan keranjang milik kasir | Katalog, quote dan checkout existing |
| cashbook | Finance/Payments | Saldo awal, mutasi operasional dan saldo berjalan kas/bank | Ledger pembayaran existing; keputusan tanggal mulai saldo |
| cashier-shifts | Sales/POS, Finance/Payments | Buka/tutup shift, modal, hitung fisik dan selisih | Buku kas; keputusan laci dan penanggung jawab |
| stock-counts | Inventory/Stock | Sesi opname, hitung, review dan posting selisih | Adjustment existing; keputusan stok selama penghitungan |
| minimum-stock | Inventory/Stock; consumer Purchasing | Minimum per barang/gudang, peringatan dan usulan beli | Saldo stok existing |
| serial-warranty | Inventory/Items, Inventory/Stock, Sales/POS, kedua Returns | Serial, garansi dan penelusuran purnajual | Keputusan barang wajib serial dan alur klaim |
| credit-terms | Sales, Purchasing, Finance | Tempo, jatuh tempo dan alokasi pembayaran | Buku kas; keputusan kredit dan pelunasan |
| quotations | Sales; consumer Inventory dan Finance | Penawaran dan konversi ke transaksi | Keputusan reservasi, masa berlaku dan uang muka |
| component-bundles | Inventory/Items; consumer Sales/POS dan Stock | Paket komponen dan jasa | Keputusan harga paket dan retur komponen |
| partial-receipts | Purchasing/PurchaseOrders; consumer Stock dan Finance | Penerimaan bertahap dan sisa pesanan | Keputusan pemisahan order, penerimaan, tagihan dan bayar |

Urutan tahap serta checklist berada di [plan](plan.md) dan [tasks](tasks.md).
Fitur di luar tiga tahap tidak otomatis aktif. Pending UAT/printer/tablet,
scheduler dan salinan backup terpisah tetap mengikuti work item MVP.

## Pengaman

- Tidak menjalankan ulang penggantian dataset atau penghapusan data lama.
- Tidak mengubah role akun, saldo, invoice atau snapshot posted secara diam-diam.
- Stok tidak minus; FIFO per barang/gudang; multi-satuan dan harga level tetap.
- Gambar bukan identitas stok, bukan snapshot HPP dan bukan lampiran transaksi.
- Barcode existing tetap unik per satuan barang, termasuk data soft-deleted.
- Pertanyaan difokuskan pada keputusan bisnis yang menghambat alur berikutnya.
- Test terfokus selama coding; satu gate akhir paket, bukan seluruh QA diulang
  setiap perubahan kecil.

## Progres Awal

Spatie Laravel Media Library 11.23.11 terpasang tanpa major upgrade package
existing. Belum ada migration media, upload, perubahan form atau aktivasi
database dari tahap persiapan tersebut. User kemudian memilih satu gambar utama;
implementasi mengikuti [work item gambar](../../modules/Inventory/Items/work-items/item-image/README.md).
