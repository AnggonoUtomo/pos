# Backlog Pengembangan POS Skala Menengah

## Status Dan Prioritas Aktif

Dicatat pada 26 Oktober 2026 atas arahan user. Dokumen ini menampung usulan
pengembangan untuk dikerjakan nanti, bukan instruksi implementasi sekarang.
Persetujuan pencatatan bukan persetujuan seluruh requirement bisnis atau
pembuatan module baru.

Prioritas sekarang adalah koreksi, perbaikan, atau tambahan kecil per module
maupun lintas module yang disampaikan user. Jangan mengambil pekerjaan dari
backlog ini secara otomatis. Aktivasi fitur besar menunggu instruksi dan
penetapan scope bersama user.

Baseline berjalan mengacu pada [daftar module](MODULES.md) dan
[empat paket MVP](work-items/mvp-ready/tasks.md). Pending UAT operator, printer,
tablet fisik, pemicu scheduler dan salinan backup terpisah tetap mengikuti
[panduan operasional](work-items/mvp-ready/OPERASIONAL.md); dokumen ini tidak
menandainya selesai atau mengaktifkan pekerjaan tersebut kembali.

## Arah Produk

POS retail/grosir CCTV dan kelistrikan skala menengah dengan operasional yang
mudah, kontrol stok/uang yang jelas, dan layanan purnajual yang tertelusur.
Tetap Finance Lite terlebih dahulu, bukan langsung full accounting.

Prioritaskan pengembangan capability existing. Ownership pada tabel berikut
adalah kandidat untuk pembahasan, bukan keputusan struktur atau nama module
baru. Boundary baru hanya ditetapkan bila ada kebutuhan bisnis nyata.

## Daftar Usulan

Seluruh entri berstatus **Ditunda / Belum Diimplementasikan** pada backlog ini.
Capability dasar yang sudah ada tidak dianggap hilang; yang diusulkan adalah
pengembangan di atasnya.

| ID | Area / Ownership Kandidat | Pengembangan | Manfaat Dan Batas Awal |
| --- | --- | --- | --- |
| BG-001 | Sales/POS | Tahan dan panggil kembali keranjang per kasir | Melayani pelanggan bergantian tanpa input ulang; bukan posting penjualan dan bukan reservasi stok otomatis. |
| BG-002 | Sales/POS, Finance/Payments | Buka/tutup shift, modal kas, hitung fisik uang, selisih dan review | Serah terima kasir tertib; definisikan penanggung jawab kas, setoran, refund dan hubungan dengan buku kas. |
| BG-003 | Finance/Payments | Buku kas, saldo awal, pengeluaran operasional, transfer kas/bank dan saldo berjalan | Mengetahui uang tersedia; history pembayaran existing belum sama dengan saldo rekening atau laba. |
| BG-004 | Inventory/Items | Kategori, merek, model/spesifikasi, cetak label barcode, impor barang/harga dengan preview validasi | Memudahkan pencarian dan pemeliharaan banyak SKU; harga tetap berdasarkan level pelanggan dan satuan. |
| BG-005 | Inventory/Stock | Stock opname per gudang: sesi hitung, scan barcode, review selisih, persetujuan dan posting | Mengembangkan koreksi stok existing menjadi workflow hitung fisik; kendalikan transaksi yang terjadi selama sesi hitung. |
| BG-006 | Inventory/Stock, Purchasing/PurchaseOrders | Batas stok minimum per gudang, peringatan hampir habis dan usulan pembelian | Mengurangi barang kosong/berlebih; jangan membuat order atau posting otomatis tanpa keputusan user. |
| BG-007 | Inventory/Items, Stock, Sales/POS, kedua Returns | Serial perangkat, riwayat masuk/keluar, garansi, klaim/RMA dan status servis/karantina | Khusus barang yang memerlukan pelacakan seperti kamera/NVR/HDD; tidak wajib untuk seluruh konektor atau kabel. |
| BG-008 | Purchasing/PurchaseOrders, Inventory/Stock | Penerimaan sebagian, sisa pesanan dan perkiraan kedatangan | Mendukung kiriman supplier bertahap; tentukan pemisahan order/penerimaan/tagihan/pembayaran sebelum coding. |
| BG-009 | Reporting/OperationalReports | Laba kotor/HPP FIFO, margin per barang, barang cepat/lambat terjual dan stok tidak bergerak | Bedakan omzet, HPP, laba kotor, arus kas dan saldo; jangan menyebut laba bersih sebelum komponen biayanya lengkap. |
| BG-010 | Sales, Purchasing, Finance | Piutang/utang, transaksi tempo, jatuh tempo, pembayaran bertahap, kartu pelanggan/supplier, limit kredit dan umur tagihan | Kondisional bila toko melayani tempo; perlu alur dokumen/alokasi pembayaran, bukan sekadar status Belum lunas. |
| BG-011 | Sales, Inventory/Stock, Finance | Penawaran, pesanan pelanggan, pengiriman, tagihan, uang muka dan reservasi stok | Kondisional untuk grosir/pesanan/proyek; aturan kapan stok dan uang berubah harus eksplisit. |
| BG-012 | Inventory/Items, Sales, Inventory/Stock | Paket CCTV berbasis komponen nyata dan jasa non-stok | Contoh kamera + NVR + HDD + kabel + jasa; stok berkurang pada komponen, bukan paket fiktif yang menggandakan stok. |
| BG-013 | Sales dan capability layanan bila disetujui | Pekerjaan pemasangan: teknisi, jadwal, material, biaya jasa dan berita acara | Kondisional jika toko melayani instalasi/proyek; tidak langsung membuat sistem manajemen proyek penuh. |
| BG-014 | Inventory/Items, Inventory/Stock | Sisa panjang kabel per rol/potongan fisik | Opsional bila panjang bentang perlu dilacak; saldo total30 meter belum tentu satu bentang30 meter. Multi satuan meter/rol existing tetap dipertahankan. |

## Urutan Pembahasan Yang Diusulkan

Ini pengelompokan calon pekerjaan, bukan increment coding atau jadwal yang
telah disahkan.

1. **Operasional harian:** BG-004, BG-001, BG-003 dan BG-002. Permudah master
   barang/layanan kasir, lalu lengkapi pencatatan uang dan kontrol shift.
2. **Stok dan purnajual:** BG-005, BG-006 dan BG-007; BG-009 ditambahkan sesuai
   kesiapan data dan kebutuhan owner. BG-008 mengikuti kebutuhan supplier.
3. **Grosir/proyek:** BG-010 sampai BG-013 bila model usaha memerlukannya;
   BG-014 hanya jika pelacakan potongan kabel memberi manfaat nyata.

Prioritas dapat berubah berdasarkan koreksi user dan hasil penggunaan toko.
Kebutuhan tempo atau serial dapat didahulukan bila menjadi hambatan operasional.

## Standar Kemudahan Operasi

- Kasir melihat POS, keranjang tertahan, riwayat miliknya dan shift sesuai
  permission; fitur admin tidak memenuhi layar kasir.
- Fitur lanjutan muncul sesuai role dan opsi toko, bukan seluruh form wajib
  untuk semua pengguna.
- Data yang sudah diketahui sistem otomatis diisi; konfirmasi difokuskan pada
  keputusan yang memang memerlukan pengguna.
- Persetujuan supervisor hanya pada tindakan berisiko yang disepakati, seperti
  koreksi stok, perubahan matriks harga atau selisih kas.
- Nama tampil Bahasa Indonesia, angka lokal, shortcut halaman, command palette
  dan Sonner toast mengikuti standar UI existing.
- Backend tetap menjadi authority permission, validasi, harga, stok dan uang.

## Keputusan Yang Masih Terbuka

- Dominasi penjualan barang langsung dibanding paket/instalasi/proyek.
- Kebutuhan tempo pelanggan/supplier, limit kredit, uang muka dan cicilan.
- Penanggung jawab laci kas, pergantian shift dan prosedur selisih/setoran.
- Jenis barang wajib serial, awal masa garansi, alur klaim supplier dan barang
  rusak/karantina. Serial tidak otomatis mengganti metode penilaian FIFO.
- Aturan transaksi berjalan saat opname, penerimaan parsial dan reservasi stok.
- Kebutuhan tracking potongan kabel dibanding saldo agregat per satuan dasar.

Keputusan tersebut dibahas saat fitur terkait benar-benar dipilih. Jangan
menganggap usulan sebagai invariant atau mengubah keputusan domain existing.

## Batas Pengembangan

Multi cabang, full accounting, marketplace, aplikasi native dan offline-first
tetap ditunda. Single company, multi gudang, FIFO per barang/gudang, stok tidak
minus, harga level/satuan dan koreksi posted yang audited tetap menjadi baseline.

## Cara Mengaktifkan Pekerjaan

1. User memilih koreksi atau entri backlog dan scope yang akan dikerjakan.
2. Agent menyebutkan module target dan memastikan requirement yang menghambat.
3. Bug kecil mengikuti aturan dokumentasi existing; tidak perlu folder baru
   untuk setiap typo atau perubahan mandiri.
4. Pekerjaan signifikan dibuatkan work item per module atau lintas module,
   dengan rencana increment, acceptance, checklist sebelum/sesudah dan QA
   terfokus sebelum coding. PRD/ADR dibuat bila memang diperlukan.
5. Setelah verifikasi, catat hasil dan tautkan work item aktual dari entri
   backlog. Status baru diperbarui berdasarkan bukti, bukan perkiraan.

Tidak membuat module, schema, dependency atau perubahan runtime dari dokumen
ini sebelum user memerintahkan implementasi.

## Referensi Pembahasan

- [Serial POS untuk pelacakan stok dan layanan garansi, Microsoft Learn](https://learn.microsoft.com/en-us/dynamics365/commerce/pos-serialized-items).
- [Stock reconciliation dan penghitungan barcode, ERPNext](https://docs.frappe.io/erpnext/stock-reconciliation).

Referensi adalah contoh workflow, bukan pengganti keputusan domain dan
arsitektur project.
