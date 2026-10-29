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

## Progres Aktif

Spatie Laravel Media Library 11.23.11 dan satu gambar utama barang telah
diimplementasikan, termasuk preview/ganti/hapus, thumbnail master, gambar POS,
permission, audit, rollback file dan migration additive lokal. Barcode tetap
per satuan, dengan scan dus/harga level terverifikasi. Bukti pada
[work item gambar](../../modules/Inventory/Items/work-items/item-image/README.md).
Keranjang tertahan juga selesai: tahan/panggil/update/batal milik kasir sendiri,
tanpa reservasi, reprice/recheck current dan checkout sumber atomik anti-double-pay.
Migration lokal additive batch30 dan QA desktop/tablet serta MySQL concurrency
terverifikasi; [bukti](../../modules/Sales/POS/work-items/held-carts/tasks.md).
Tambahan barcode scanner/label ditangani pada [work item](../barcode-scanner/README.md):
barcode pabrikan/internal, label Code128 dan scan pembelian/POS, tanpa mengubah
aturan posting stok. UAT printer/scanner fisik tetap pending.
Buku Kas diselesaikan pada [work item Payments](../../modules/Finance/Payments/work-items/cashbook/README.md):
saldo awal/cutover per akun, mutasi/transfer/pembalik dan saldo berjalan dari
ledger existing. Saldo minus hanya peringatan, tidak memblokir pembayaran.
Shift Kasir selesai melalui [work item](../cashier-shifts/README.md): laci
eksklusif, modal dari Buku Kas, checkout wajib shift, tutup/hitung serta review
selisih tanpa mutasi uang otomatis. Tahap2/3 belum
diimplementasikan; seluruh roadmap belum dianggap selesai.
