# Stock Opname

Panduan petugas gudang dan supervisor untuk mencocokkan fisik dengan saldo,
satu gudang dalam satu sesi.

## 1. Siapkan Penghitungan

Selesaikan transaksi berjalan, rapikan barang dan tentukan petugas.
Buka **Stock opname > Mulai opname**, pilih gudang dan isi catatan.
Konfirmasi saat siap: mutasi stok gudang ditahan sampai sesi selesai atau
dibatalkan. Gudang lain tetap beroperasi, satu sesi aktif per gudang.

Penjualan, penerimaan pembelian, retur barang, transfer dari/ke gudang,
stok awal dan koreksi biasa tertahan. Draft serta pembayaran dokumen lama
yang tidak mengubah stok tetap tersedia. Super-system juga tidak dapat bypass.

## 2. Hitung Fisik

Daftar awal memuat semua barang bersaldo, termasuk nol. Aktifkan kembali
barang bersaldo nonaktif sebelum mulai. Barang tanpa saldo yang ditemukan
dapat ditambahkan saat hitung. Isi **Fisik** dalam satuan dasar: dua dus kamera
isi 12 ditulis **24 unit**; kabel boleh **8,5 meter**. Kosong berarti belum
dihitung; **0** berarti sudah dihitung dan tidak ada.

Scan barcode pabrikan/internal atau ketik SKU untuk menemukan barang.
Barcode dus mengarah ke barang yang sama; scan tidak menambah jumlah otomatis.
Hitungan tetap satuan dasar. **Simpan hitung** sebelum berpindah/menambah
barang. Boleh menyimpan sebagian dan melanjutkan sisanya. Setelah seluruh
baris diisi, simpan lalu **Ajukan review**.

## 3. Pemeriksaan Supervisor

Bandingkan Sistem, Fisik dan Selisih tersimpan. **Hitung ulang** dengan alasan
bila belum benar; gudang tetap ditahan. Selisih tambah wajib harga pokok
positif per satuan dasar, bukan harga jual/per dus. Selisih kurang memakai
biaya FIFO existing, bukan pilihan supervisor.

## 4. Posting atau Batalkan

**Posting hasil** dengan alasan dan konfirmasi. Saldo, FIFO, koreksi, audit,
status dan pelepasan gudang disimpan bersama. Jika gagal tidak ada posting
sebagian dan gudang tetap ditahan. Selisih nol tanpa mutasi. Sesudah berhasil,
gudang dibuka kembali. **Batalkan sesi** wajib alasan: tidak mengubah stok,
melepas gudang dan mempertahankan jejak sesi.

Posted tidak dapat diedit/dihapus. Koreksi berikutnya melalui proses audited
baru. Jika respons terputus, muat ulang; retry yang sama tidak membuat koreksi
ganda. Jika tab lain mengubah sesi, baca hasil terbaru sebelum melanjutkan.

## Izin Tugas

- `inventory.stock.counts.view`: daftar/detail.
- `inventory.stock.counts.manage`: mulai, tambah, simpan, ajukan dan batal.
- `inventory.stock.counts.post`: hitung ulang dan posting persetujuan.

Petugas gudang disarankan view/manage; supervisor view/manage/post. Admin
mengatur role melalui Identitas & Akses; grant existing tidak diubah otomatis.
F1 membuka bantuan pada daftar dan detail.
