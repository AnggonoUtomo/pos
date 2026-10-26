# Panduan Role: Staf Gudang

**Tujuan:** menjaga kecocokan barang fisik dengan saldo tiap gudang.
Usulan role `warehouse-staff`, belum otomatis dibuat. Paket awal membaca
master/saldo/kartu dan mengelola transfer. Stok awal/koreksi/penerimaan retur
memerlukan penugasan serta permission tambahan.

## Tahap 1: Pahami Satuan Dan Lokasi

1. Periksa gudang yang sedang dibaca; saldo per lokasi tidak otomatis digabung.
2. Lihat satuan dasar dan isi kemasan barang.
3. Hitung fisik dengan konversi yang sama: 2 Box isi 4 = 8 Unit; 1 Rol isi
   100 = 100 Meter. Angka isi kemasan bukan saldo.
4. Jika label/konversi master salah, laporkan ke admin master sebelum transaksi.

Izin baca inventory dapat memperlihatkan informasi harga barang. Belum ada
pemisahan akses per kolom atau pembatasan user per gudang yang dijanjikan.

## Tahap 2: Stok Pertama Dan Penerimaan Pembelian

Untuk barang yang sudah ada saat mulai sistem, petugas yang ditugaskan membuat
draft **Stok awal**, memeriksa harga pokok per satuan pilihan, lalu posting
setelah fisik disahkan. Hanya satu stok awal posted per barang+gudang.

Untuk barang baru dari supplier:

1. Minta nomor draft **Order Pembelian** dan nota.
2. Cocokkan seluruh barang, kuantitas, satuan dan kondisi fisik.
3. Serahkan hasil pemeriksaan ke petugas berizin **Terima dan bayar**.
4. Setelah berhasil, periksa stok masuk di **Saldo stok** dan **Kartu stok**.

Jangan menambah stok awal untuk penerimaan pembelian yang sama. Alur utama
menerima penuh sekaligus dengan pembayaran; staf gudang tanpa kedua izin
tidak boleh menganggap ada tombol penerimaan terpisah untuk dipaksakan.

## Tahap 3: Transfer Antar-Gudang

1. Buka **Transfer antar-gudang**, buat draft.
2. Pilih asal dan tujuan yang berbeda, isi barang/satuan/jumlah.
3. Periksa stok asal dan cocokkan fisik bersama petugas tujuan.
4. Petugas berizin memilih **Posting transfer** saat perpindahan siap dicatat.
5. Cocokkan kartu stok kedua gudang dan jumlah fisik yang diserahkan.

Posting langsung mengurangi asal dan menambah tujuan bersamaan. Belum ada
status transit/penerimaan parsial. Total jumlah antargudang tetap sama;
transfer tidak membuat pembayaran.

## Tahap 4: Retur Dan Selisih

- Retur pelanggan: harus memakai invoice asal dan barang layak jual. Petugas
  dengan izin tambahan menerima retur ke gudang invoice asal; refund urusan
  keuangan sesudahnya.
- Retur supplier: gunakan pembelian asal dan periksa sisa stok sumber; total
  saldo dari sumber lain tidak menjamin retur dapat diposting.
- Selisih fisik: hitung ulang, periksa satuan/gudang, lalu telusuri kartu stok.
  Jangan melakukan koreksi hanya agar saldo terlihat cocok.
- Bila koreksi disahkan, serahkan selisih dan alasan ke supervisor berizin.
  Saldo 10, fisik 8 berarti kurangi 2 satuan dasar, bukan memasukkan 8.

Belum ada stock opname bersesi, karantina barang rusak atau pelacakan per
potongan rol. Jangan memasukkan barang rusak lewat retur layak jual.

## Tahap 5: Serah Terima

Catat nomor dokumen, barang, satuan pilihan dan satuan dasar, jumlah, gudang
asal/tujuan serta status. Untuk pembelian/retur, pisahkan keterangan barang
dari status uang; barang diterima tidak otomatis berarti refund selesai.

## Checklist Gudang

- [ ] Gudang dan konversi benar saat hitung fisik.
- [ ] Penerimaan tidak dicatat dua kali melalui stok awal dan pembelian.
- [ ] Transfer cocok pada kedua kartu stok dan fisik.
- [ ] Selisih ditelusuri sebelum diajukan koreksi.
- [ ] Barang rusak tidak masuk stok jual lewat alur yang salah.
- [ ] Semua persoalan disertai nomor dokumen saat diserahkan.

Rincian: [panduan modul](../02-panduan-modul.md).
Hak tambahan: [role dan akses](../03-role-dan-akses.md).
