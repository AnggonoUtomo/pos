# Panduan Role: Admin Data Master

**Tujuan:** menyiapkan data agar petugas tidak salah memilih barang, satuan
atau harga. Usulan role `master-admin`; penyusunan panduan tidak memasangnya.
Paket ini tidak otomatis memberi izin posting stok, penjualan atau pembayaran.

## Tahap 1: Siapkan Gudang Dan Satuan

1. Buka **Gudang**, buat kode/nama lokasi penyimpanan nyata.
2. Buka **Satuan**, buat nama yang seragam, misalnya Unit, Box, Meter dan Rol.
3. Pastikan aktif dan tidak ada nama ganda dengan arti yang sama.

Gudang bukan cabang baru. Satuan master hanya nama; isi kemasan ditentukan
per barang, bukan secara global.

## Tahap 2: Siapkan Level Dan Pelanggan

1. Buka **Pelanggan** > **Level pelanggan**.
2. Buat level yang disepakati, misalnya Eceran, Grosir dan Installer.
3. Setelah level siap, tambah pelanggan dan wajib pilih satu level aktif.
4. Isi kontak/alamat bila tersedia. Periksa kode/nama sebelum menyimpan.

Jangan membuat level baru tanpa menyiapkan harga barang untuk level tersebut.
Level yang sudah dirujuk pelanggan tidak boleh dihapus/dinonaktifkan, termasuk
jika pelanggannya sudah dihapus sementara.

## Tahap 3: Buat Barang Dan Harga

1. Buka **Barang** > **Tambah barang**, isi SKU unik dan nama yang mudah dicari.
2. Pilih satuan dasar terkecil dengan benar; tidak bisa diganti sesudah dibuat.
3. Isi **Satuan & barcode** serta **Isi satuan dasar**.
4. Tandai satuan jual/beli. Lengkapi seluruh harga satuan jual x level aktif.
5. Simpan dan cek ulang barcode, isi kemasan dan harga.

Contoh kamera: Unit isi 1, Box isi 4. Harga Installer Unit Rp450.000;
jika harga Box disepakati Rp1.800.000, isi angka itu pada kombinasi Box/Installer.
Lengkapi juga Eceran dan Grosir. Jangan menganggap harga Box otomatis lengkap.

Contoh kabel: Meter sebagai dasar, Rol isi 100. Stok nantinya tersimpan dalam
Meter; kuantitas `0,5` memakai koma desimal. Konversi `100` adalah isi Rol,
bukan stok yang sudah tersedia. Jangan ubah konversi untuk mengakali harga.

## Tahap 4: Supplier Dan Serah Terima

1. Buka **Supplier**, buat kode/nama, kontak dan alamat bila ada.
2. Beri tahu pembelian nomor/kode supplier yang digunakan.
3. Serahkan SKU, satuan, konversi, level dan harga ke kasir/gudang.
4. Minta gudang menyediakan stok melalui stok awal atau pembelian yang sah.
5. Serahkan kebutuhan akun/metode bayar ke keuangan; itu bukan otomatis hak
   admin master.

**Hasil yang diharapkan:** barang dapat dipilih dan harga benar. Menyimpan
master tidak menambah stok; jangan mencoba mengoreksi saldo dari form Barang.

## Tahap 5: Perubahan Dan Data Tidak Dipakai

- Periksa dampak sebelum menambah level atau mengubah harga/konversi.
- Gunakan nonaktif/hapus/pulihkan sesuai fitur dan aturan referensi.
- Jika perubahan ditolak karena data dipakai, laporkan; jangan hapus paksa.
- Transaksi lama memiliki snapshot; perubahan master bukan cara mengoreksinya.
- Izin inventory.manage masih mencakup Barang, Satuan dan Gudang bersama.

## Checklist Siap Diserahkan

- [ ] Gudang/satuan/level aktif dan tidak ganda.
- [ ] SKU/barcode unik serta satuan dasar benar.
- [ ] Isi kemasan per barang sudah diperiksa.
- [ ] Semua kombinasi harga satuan jual dan level aktif terisi positif.
- [ ] Pelanggan mempunyai level yang benar; supplier siap dipakai.
- [ ] Petugas memahami bahwa master belum merupakan saldo stok.

Rincian: [panduan modul](../02-panduan-modul.md).
Pembagian izin: [role dan akses](../03-role-dan-akses.md).
