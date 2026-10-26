# Panduan Role: Staf Pembelian

**Tujuan:** menyiapkan dokumen supplier dengan barang/satuan/harga yang benar.
Usulan role `purchasing-staff`, belum otomatis dipasang. Paket awal membuat
draft; hak menerima stok, membayar dan menerima uang supplier tidak otomatis ada.

## Tahap 1: Pastikan Data Acuan

1. Periksa **Supplier**, gunakan pemasok aktif yang benar atau tambah sesuai izin.
2. Periksa barang, gudang tujuan dan satuan beli bersama admin master.
3. Cocokkan nota/harga penawaran dengan satuan yang dipakai.
4. Jika barang atau satuan belum tersedia, minta admin master menyiapkannya;
   jangan memakai barang lain dengan nama mirip.

Contoh: 2 Box isi 4 Unit dengan harga Rp1.200.000/Box bernilai Rp2.400.000,
bukan 8 x Rp1.200.000. Harga wajib positif sejak draft disimpan.

## Tahap 2: Buat Draft Order Pembelian

1. Buka **Order Pembelian**, tambah draft.
2. Pilih supplier dan satu gudang tujuan.
3. Isi baris barang, satuan, jumlah dan harga beli per satuan pilihan.
4. Periksa subtotal/total, simpan dan catat nomor dokumen.
5. Bila masih draft dan belum dihapus, edit kesalahan; hapus/pulihkan hanya
   sesuai izin dan kebutuhan nyata.

**Hasil:** dokumen rancangan. Draft belum menambah stok, membuat FIFO atau
mencatat pembayaran; jangan memberi tahu kasir bahwa barang sudah siap jual.

## Tahap 3: Serahkan Untuk Terima Dan Bayar

1. Serahkan nomor draft dan nota kepada gudang untuk cek seluruh fisik.
2. Serahkan total/metode yang disepakati kepada keuangan/petugas berwenang.
3. Petugas dengan izin post + pay menjalankan **Terima dan bayar**.
4. Periksa status Diterima/Lunas setelah petugas menyelesaikannya.
5. Bila hasil belum pasti, telusuri dokumen yang sama; jangan membuat duplikat.

Belum ada penerimaan parsial atau pembelian tempo pada alur utama. Jangan
mengubah draft supaya terlihat sesuai kiriman parsial tanpa keputusan supervisor.
Dokumen sudah Diterima tidak boleh diedit/hapus bebas.

## Tahap 4: Draft Retur Ke Supplier

1. Cari pembelian asal yang sudah Diterima.
2. Buka **Retur Pembelian**, buat draft berdasarkan dokumen itu.
3. Pilih baris/jumlah memakai snapshot satuan dan harga asal.
4. Cocokkan fisik/alasan; jumlah tidak boleh melebihi sisa pembelian yang dapat
   dikembalikan. Simpan dan catat nomor retur.
5. Serahkan ke petugas berizin untuk posting serah barang ketika benar diserahkan.
6. Untuk uang kembali, serahkan nomor retur posted ke keuangan; penerimaan uang
   saat ini mensyaratkan pembelian asal lunas.

Stok dari layer penerimaan asal harus cukup. Jangan mengganti dengan pembelian
lain hanya karena total saldo barang masih ada. Uang kembali bukan harga jual
sekarang dan tidak diketik bebas; nilainya dihitung dari pembelian asal.

## Tahap 5: Pantau Dokumen Terbuka

Pisahkan tiga keadaan: draft belum memengaruhi stok, serah barang sudah
memengaruhi stok, dan uang kembali sudah dicatat. Jangan menganggap ketiganya
selesai sekaligus. Belum ada potong utang/kredit supplier atau refund parsial.

## Checklist Pembelian

- [ ] Supplier, gudang, SKU dan satuan sesuai nota.
- [ ] Harga positif per satuan yang dipilih, subtotal/total benar.
- [ ] Nomor draft dan bukti diserahkan ke gudang/keuangan.
- [ ] Status Diterima/Lunas diverifikasi, bukan diasumsikan setelah klik simpan.
- [ ] Retur mengacu pembelian asal dan dipantau barang serta uangnya terpisah.

Rincian: [panduan modul](../02-panduan-modul.md).
Urutan toko: [alur global](../01-alur-global.md).
