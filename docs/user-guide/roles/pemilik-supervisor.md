# Panduan Role: Pemilik / Supervisor

**Tujuan:** mengawasi toko dan menangani tindakan kritis tanpa memakai akun
administrator untuk semua kegiatan. Usulan role `owner-supervisor`, belum
otomatis dibuat. Hak tindakan dipilih secara eksplisit oleh administrator.

## Tahap 1: Setujui Persiapan Toko

1. Sepakati penanggung jawab master, stok, kasir, pembelian dan keuangan.
2. Periksa gudang, satuan dasar serta konversi barang CCTV/kelistrikan.
3. Sahkan level dan harga jual. Contoh Installer tidak otomatis berhak dipakai
   untuk semua pelanggan; harga mengikuti level pelanggan terpilih.
4. Cocokkan akun/metode bayar dengan rekening/kas nyata.
5. Tentukan penanggung jawab backup dan tindak lanjut masalah.

**Lanjut jika:** stok siap di gudang jual, harga lengkap dan petugas memahami
batas aksesnya. Login berhasil saja belum cukup.

## Tahap 2: Awasi Penerimaan Dan Penjualan

1. Untuk pembelian, minta nomor draft dan nota supplier.
2. Cocokkan jumlah/satuan/harga serta fisik seluruh barang.
3. Petugas yang ditugaskan dan memiliki kedua izin menjalankan **Terima dan
   bayar**, dengan pembayaran tepat. Jangan posting sebelum fisik/dana siap.
4. Periksa status Diterima/Lunas dan stok masuk; jangan menambah stok awal lagi.
5. Saat memeriksa penjualan, gunakan **Riwayat Penjualan** dan cocokkan nomor
   invoice, gudang, pelanggan/level, diskon/pajak serta metode pembayaran.

Hak melihat seluruh invoice berbeda dari hak kasir yang hanya melihat miliknya.
Ringkasan pembayaran bukan bukti uang fisik/bank sudah cocok; tetap periksa bukti.

## Tahap 3: Tangani Pengecualian

### Pelanggan Mengembalikan Barang

1. Cari invoice asal dan periksa sisa jumlah yang boleh diretur.
2. Periksa kelayakan jual. Barang rusak tidak dimasukkan lewat alur retur layak jual.
3. Petugas berizin menerima barang pada **Retur Penjualan**.
4. Serahkan nomor retur posted ke keuangan untuk refund, bukan mengeluarkan
   uang hanya berdasarkan draft.

### Barang Dikembalikan Ke Supplier

1. Cocokkan dengan pembelian asal dan jumlah yang masih dapat dikembalikan.
2. Periksa saldo sumber penerimaan, bukan hanya total stok barang.
3. Petugas berizin mem-posting serah barang; keuangan mencatat uang kembali
   setelah benar diterima. Penyelesaian uang mensyaratkan pembelian asal lunas.

### Fisik Berbeda Dari Sistem

1. Hitung ulang, cek gudang dan satuan serta telusuri **Kartu stok**.
2. Jika benar perlu koreksi, sahkan selisih dan alasan terlebih dahulu.
3. Petugas berizin khusus mem-posting **Koreksi stok**. Saldo 10, fisik 8
   berarti Kurangi 2, bukan memasukkan angka 8.

Belum ada tombol approval supervisor terpisah; pemeriksaan di atas merupakan
prosedur kerja. Hak penuh tidak membolehkan edit/hapus bebas transaksi posted.

## Tahap 4: Pemeriksaan Akhir Hari

1. Buka **Laporan Operasional**, pilih tanggal/gudang yang benar.
2. Cocokkan penjualan, pembelian, retur, stok dan riwayat uang dengan dokumen asal.
3. Minta penjelasan setiap selisih, sertakan nomor dokumen dan petugasnya.
4. Pastikan persoalan terbuka diserahkan, bukan ditutup dengan koreksi tanpa alasan.
5. Periksa status backup bersama administrator. Backup lokal belum salinan terpisah.

Belum ada tutup shift, saldo kas berjalan, laporan laba bersih atau akuntansi
penuh. Jangan menafsirkan selisih uang masuk/keluar sebagai laba.

## Checklist Pengawasan

- [ ] Harga, konversi dan saldo gudang jual sesuai kebijakan/fisik.
- [ ] Penerimaan pembelian dibuktikan fisik dan pembayaran.
- [ ] Retur barang dan penyelesaian uang diperiksa sebagai dua tahap.
- [ ] Koreksi stok mempunyai selisih dan alasan yang dapat ditelusuri.
- [ ] Periode laporan sama dengan periode bukti yang diperiksa.
- [ ] Pending operasional dicatat, bukan dinyatakan lulus tanpa uji nyata.

Rincian izin: [role dan akses](../03-role-dan-akses.md).
Urutan lengkap: [alur global](../01-alur-global.md).
