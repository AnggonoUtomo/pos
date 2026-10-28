# Barcode Scanner Dan Label

Panduan admin master, kasir dan staf pembelian. Barcode mengenali pasangan
barang + satuan; barcode dus dan unit tidak boleh sama.

## 1. Siapkan Scanner

1. Hubungkan scanner USB/Bluetooth dengan mode keyboard/HID di komputer/tablet.
2. Atur akhiran hasil scan menjadi **Enter**, bukan Tab. Ikuti manual perangkat.
3. Aktifkan pembacaan Code128 untuk label internal. Barcode pabrikan memakai
   jenis asli yang didukung scanner, misalnya EAN/UPC.
4. Pastikan hasil berupa kode persis, termasuk angka nol di depan; jangan menambah
   awalan identitas jenis barcode/AIM atau karakter tambahan pada output scanner.
5. Buka POS atau draft pembelian, fokuskan **Scan barcode** dengan **Alt+B**.

Aplikasi menerima hasil scanner seperti ketikan keyboard. Tidak perlu kamera
atau koneksi internet untuk membuat label. Scanner/printer fisik tetap perlu
dicoba memakai perangkat toko.

## 2. Daftarkan Barcode

1. Buka **Barang**, edit barang, temukan **Satuan & barcode**.
2. Untuk barcode pabrikan, pindai atau ketik pada baris satuan yang benar.
3. Untuk barang tanpa barcode, kosongkan field bila perlu lalu klik ikon
   **Buat barcode internal**. Tombol tidak menimpa barcode yang sudah terisi.
4. Simpan barang. Kode baru belum tersimpan hanya karena generate diklik.
5. Pastikan satuan diizinkan untuk Jual/Beli sesuai penggunaannya.

Kode internal berupa 20 digit acak, dicetak dalam Code128, bukan nomor registrasi
GS1/EAN/UPC. Kode disimpan sebagai teks agar nol di depan tidak hilang. Duplikat
ditolak, termasuk kode pada data soft-deleted. Kode lama tidak diganti massal.
Foto dan nomor serial perangkat berbeda dari barcode SKU.

## 3. Cetak Label

1. Pada daftar Barang, klik ikon **Label barcode** pada barang aktif.
2. Pilih satuan, jumlah 1..100, kertas **A4** atau **Label tunggal**, serta ukuran
   media. Default 90x40 mm; lebar 40..190 mm dan tinggi 35..80 mm.
3. Periksa preview: nama barang, satuan, batang hitam/putih dan kode teks.
4. Klik **Cetak label** atau **Ctrl/Cmd+Enter** dari dialog label.
5. Pada dialog browser, pilih printer dan ukuran media yang sama, skala
   **100%/Actual size**, nonaktifkan header/footer; hindari Fit/Shrink.
6. Cetak satu sampel dahulu dan scan hasilnya sebelum mencetak banyak.

A4 memakai grid berdasarkan lebar label, bukan template semua merek stiker
precut. Cocokkan margin/grid dengan media atau gunakan label tunggal pada driver
printer yang sesuai. Label terlalu sempit/kode non-ASCII ditolak untuk dicetak,
bukan dipaksa mengecil. Barcode master tidak diubah hanya karena gagal cetak.
Nama sangat panjang dapat disingkat tampilan label; identitas kode tetap utuh.

## 4. Penjualan

1. Pilih **Gudang** dan **Pelanggan** terlebih dahulu.
2. Fokus **Scan barcode** (Alt+B), lalu scan. Enter memasukkan satu satuan sesuai
   barcode. Scan kode sama lagi menambah jumlah, bukan baris duplikat.
3. Scan cepat diproses berurutan. Tunggu indikator selesai sebelum menahan atau
   membayar. Jangan beralih aplikasi saat scan.
4. Barcode dus menambah satu dus, bukan satu unit. Batas stok dihitung gabungan
   semua satuan barang pada gudang terpilih; harga mengikuti level pelanggan.
5. Unknown/tidak tersedia/harga belum ada/kelebihan stok menampilkan pesan.
   Scan gagal tidak menghapus keranjang; periksa jumlah sebelum mengulang scan.
6. **Periksa keranjang**, lalu Pembayaran. Scan sendiri tidak mengurangi stok;
   harga/stok/FIFO/pembayaran tetap divalidasi ulang saat posting.

Cari barang tetap mencari nama/SKU; Enter dapat menjalankan exact barcode/SKU.
Untuk scan berturut-turut, gunakan Scan barcode agar input tetap siap selama
antrean. Barcode diprioritaskan jika sama dengan SKU barang lain.

## 5. Barang Masuk Melalui Pembelian

1. Buka **Order pembelian**, tambah/edit draft, pilih supplier dan gudang.
2. Fokus **Scan barcode** (Alt+B). Scan memilih barang/satuan beli dan qty 1.
3. Isi harga beli per satuan pilihan, misalnya per dus, bukan per unit dasar.
4. Scan ulang kode sama menambah qty 1, mempertahankan harga beli yang diisi.
5. Jika barang sudah ada dengan satuan berbeda, scan ditolak. Periksa baris lalu
   ubah satuan/kuantitas/harga secara sadar, tanpa konversi harga diam-diam.
6. Enter scanner tidak menyimpan draft. Klik **Simpan draft** setelah lengkap.
7. Penerimaan/posting dan pembayaran mengikuti alur pembelian biasa; draft/scan
   tidak menambah stok, movement atau layer FIFO.

Scan tidak tersedia pada dokumen posted. Tidak ada opname, serial/garansi,
penerimaan parsial atau posting stok otomatis dari fitur ini.

## Pemeriksaan Operator

- Coba barcode unit, dus dan kode yang diawali nol.
- Scan dua kali; periksa satuan, jumlah dan harga yang muncul.
- Cocokkan cetakan ukuran asli dengan hasil scan pada printer/scanner toko.
- Latihan transaksi hanya di database pelatihan. UAT fisik belum dianggap lulus
  oleh QA browser/keyboard otomatis saja.
