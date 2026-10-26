# Role Dan Pembagian Akses

## 1. Bedakan Profil Tugas Dengan Role Yang Sudah Terpasang

Dokumen ini mengusulkan tujuh profil tugas agar toko mudah membagi pekerjaan.
**Tidak ada perubahan role, akun atau permission database dari penyusunan
panduan ini.** Administrator harus memeriksa role yang benar-benar tersedia
di **Identitas & Akses**, menyepakati pembagian dengan pemilik dan baru
mengaturnya melalui aplikasi.

`super-system` adalah role sistem yang sudah menjadi keputusan aplikasi.
Nama enam profil lain di bawah adalah usulan, bukan bukti sudah dibuat.
Role demo `cashier` tidak boleh diasumsikan sudah memiliki izin posting hanya
karena bisa membuka POS.

| Profil | Nama Role Usulan | Tanggung Jawab Utama |
| --- | --- | --- |
| Administrator sistem | `super-system` (role sistem) | Akun, akses, identitas, cetak dan backup |
| Pemilik / supervisor | `owner-supervisor` | Pengawasan dan tindakan kritis yang ditugaskan |
| Admin master | `master-admin` | Barang/satuan/harga, gudang, pelanggan, supplier |
| Kasir | `cashier-operational` | Penjualan lunas dan bukti transaksi sendiri |
| Staf gudang | `warehouse-staff` | Saldo, kartu stok, draft/posting transfer |
| Staf pembelian | `purchasing-staff` | Supplier, draft pembelian dan draft retur supplier |
| Staf keuangan | `finance-staff` | Metode/akun, pembayaran dan penyelesaian uang retur |

Satu orang boleh merangkap pada toko kecil setelah disepakati, tetapi gabungan
role juga menggabungkan akses. Jangan memberi super-system hanya karena ada
satu tombol yang belum boleh dipakai.

## 2. Pembagian Pekerjaan Yang Disarankan

| Kegiatan | Penanggung Jawab | Pemeriksa / Serah Terima |
| --- | --- | --- |
| Akun dan role | Administrator | Pemilik menyetujui ruang akses |
| Harga dan master barang | Admin master | Pemilik memeriksa level/harga |
| Draft pembelian | Pembelian | Gudang cek fisik, keuangan cek dana |
| Terima dan bayar pembelian | Petugas dengan kedua izin | Tanggung jawab fisik dan uang harus jelas |
| Penjualan lunas | Kasir | Keuangan mencocokkan pembayaran |
| Penerimaan retur pelanggan | Petugas berizin / supervisor | Cek invoice dan barang layak jual |
| Refund pelanggan | Keuangan | Cek retur sudah diterima dan nominal |
| Penyerahan retur supplier | Petugas berizin / supervisor | Cek pembelian dan saldo layer sumber |
| Uang kembali supplier | Keuangan | Cek pembelian lunas dan uang benar diterima |
| Transfer | Gudang berizin | Kedua lokasi cocokkan fisik |
| Koreksi stok | Supervisor berizin khusus | Selisih dan alasan diperiksa dahulu |
| Ringkasan akhir hari | Pemilik / keuangan | Cocokkan sumber, bukan saldo kas otomatis |
| Backup dan pemulihan | Administrator | Pemilik cek status dan salinan terpisah |

Ini pembagian kerja manusia, **bukan fitur approval bertingkat**. Aplikasi
belum menyediakan persetujuan supervisor dalam status dokumen.

## 3. Permission Untuk Administrator

Bagian teknis ini membantu memilih izin yang benar; operator pemula cukup
membaca lembar role masing-masing. Permission backend tetap penentu keamanan.
Role super-system melewati pemeriksaan permission, tetapi tidak melewati login,
validasi, aturan stok, perlindungan transaksi dan invariant bisnis.

### Kelompok Identitas Dan Pengaturan

- `platform.identity.view`: melihat role/permission.
- `platform.identity.manage`: mengelola role dan pemberian izin yang diperbolehkan.
- `platform.identity.users.view`: melihat pengguna.
- `platform.identity.users.create`: membuat pengguna.
- `platform.identity.users.update`: mengubah identitas pengguna.
- `platform.identity.users.manage`: pengelolaan role/status pengguna.
- `platform.company-settings.view` / `platform.company-settings.manage`:
  melihat/mengubah pengaturan toko.
- `platform.operational-settings.view` / `platform.operational-settings.manage`:
  melihat/mengubah Operasional; manage juga dipakai untuk backup manual.

### Kelompok Stok Dan Master

- `inventory.view` / `inventory.manage`: membaca/mengelola master Satuan,
  Gudang dan Barang. Izin manage ini masih bersama, bukan izin terpisah per master.
- `inventory.stock.view`: saldo, kartu dan dokumen stok sesuai endpoint.
- `inventory.stock.manage`: draft stok awal.
- `inventory.stock.post`: posting stok awal.
- `inventory.stock.adjust`: koreksi stok langsung; jangan berikan otomatis
  kepada semua petugas yang hanya boleh membaca saldo.
- `inventory.transfers.view` / `inventory.transfers.manage` /
  `inventory.transfers.post`: melihat, mengelola draft, mem-posting transfer.

### Kelompok Penjualan

- `sales.parties.view` / `sales.parties.manage`: pelanggan dan level.
- `sales.pos.invoices.create`: membuka POS/katalog dan memeriksa keranjang.
- `sales.pos.invoices.post`: checkout lunas dan riwayat penjualan sendiri.
- `sales.pos.invoices.view`: melihat seluruh riwayat penjualan; jangan diberikan
  ke kasir jika hanya diperlukan transaksi miliknya.
- `sales.returns.view` / `sales.returns.manage` / `sales.returns.post` /
  `sales.returns.refund`: membaca, draft, menerima barang, menyelesaikan uang retur.

### Kelompok Pembelian Dan Keuangan

- `purchasing.suppliers.view` / `purchasing.suppliers.manage`: supplier.
- `purchasing.purchase-orders.view` / `purchasing.purchase-orders.manage`:
  membaca dan mengelola draft pembelian.
- `purchasing.purchase-orders.post`: penerimaan stok pembelian.
- `purchasing.purchase-orders.pay`: pembayaran pembelian.
- `purchasing.returns.view` / `purchasing.returns.manage` /
  `purchasing.returns.post` / `purchasing.returns.refund`: membaca, draft,
  serah barang, menerima uang supplier.
- `finance.payments.view` / `finance.payments.manage`: master akun/metode bayar.
- `finance.payments.history.view`: riwayat uang empat sumber.
- `reporting.operational.view`: laporan operasional.

## 4. Paket Izin Awal Yang Direkomendasikan

Paket berikut acuan awal, bukan perintah seeding. Tambahkan izin baca halaman
yang diperlukan bersama izin tindakannya; jangan menganggap manage selalu
berarti semua izin lainnya.

### Administrator Sistem

Gunakan role sistem hanya untuk orang yang dipercaya mengelola aplikasi.
Simpan akun pribadi; jangan dibagikan sebagai akun bersama toko.

### Pemilik / Supervisor

Untuk pengawasan: `inventory.view`, `inventory.stock.view`,
`inventory.transfers.view`, `sales.parties.view`, `sales.pos.invoices.view`,
`sales.returns.view`, `purchasing.suppliers.view`,
`purchasing.purchase-orders.view`, `purchasing.returns.view`,
`finance.payments.view`, `finance.payments.history.view`,
`reporting.operational.view`.

Jika pemilik memang mengeksekusi tindakan kritis, tambahkan secara eksplisit:
`inventory.stock.adjust`, `inventory.stock.post`, `inventory.transfers.post`,
`sales.returns.post`, `purchasing.returns.post`, serta pasangan
`purchasing.purchase-orders.post` + `purchasing.purchase-orders.pay` untuk
Terima dan bayar. Pengisian draft stok awal juga membutuhkan
`inventory.stock.manage`. Jangan memberikan seluruhnya tanpa pembagian tugas.

### Admin Master

`inventory.view`, `inventory.manage`, `sales.parties.view`,
`sales.parties.manage`, `purchasing.suppliers.view`,
`purchasing.suppliers.manage`. Tidak otomatis boleh mem-posting stok atau uang.

### Kasir

`sales.pos.invoices.create` + `sales.pos.invoices.post`.
Tambahkan `sales.parties.view` hanya jika perlu membuka halaman master pelanggan.
Tanpa `sales.pos.invoices.view`, riwayat tetap dibatasi transaksi sendiri.
Hak refund atau koreksi stok tidak termasuk paket kasir awal.

### Staf Gudang

`inventory.view`, `inventory.stock.view`, `inventory.transfers.view`,
`inventory.transfers.manage`, `inventory.transfers.post`.
Stok awal `inventory.stock.manage` + `inventory.stock.post` hanya jika
ditugaskan saat persiapan. Koreksi stok bukan akses default.

### Staf Pembelian

`inventory.view`, `purchasing.suppliers.view`, `purchasing.suppliers.manage`,
`purchasing.purchase-orders.view`, `purchasing.purchase-orders.manage`,
`purchasing.returns.view`, `purchasing.returns.manage`.
Posting barang/uang tetap diserahkan ke petugas yang berwenang.

### Staf Keuangan

`finance.payments.view`, `finance.payments.manage`,
`finance.payments.history.view`, `purchasing.purchase-orders.view`,
`purchasing.purchase-orders.pay`, `sales.returns.view`,
`sales.returns.refund`, `purchasing.returns.view`, `purchasing.returns.refund`.
Tambahkan `reporting.operational.view` bila ditugaskan membaca ringkasan.

**Perhatian pembelian:** tombol utama **Terima dan bayar** membutuhkan post
dan pay. Paket keuangan di atas tidak otomatis berhak menerima fisik stok.
Untuk draft baru, serahkan kepada petugas yang memegang kedua izin, atau
berikan post hanya jika keuangan juga ditugaskan memverifikasi penerimaan.
Pay saja berguna untuk pembelian yang sudah Diterima tetapi belum dibayar.
Jangan mencari tombol penerimaan terpisah yang tidak tersedia pada alur utama UI.

## 5. Batas Akses Yang Harus Dipahami

- Belum ada pembatasan role per gudang. Memilih gudang saat transaksi bukan
  bukti user hanya boleh mengakses gudang itu.
- Belum ada pemisahan tampilan harga per kolom. `inventory.view` dapat membuka
  master barang beserta informasi harga; pertimbangkan sebelum memberikannya.
- Belum ada izin backup manual terpisah dari manage Operasional.
- Menu yang tidak muncul bukan bukti validasi backend boleh dilewati.
- Hak penuh tidak membolehkan mengubah transaksi posted atau membuat stok minus.
- Pengelolaan role dapat membatasi pemberian izin di luar kewenangan pengelola.
- Audit tercatat oleh sistem, tetapi jangan menganggap ada halaman audit log
  operator jika menunya tidak tersedia.

## 6. Uji Akses Sebelum Petugas Mulai

1. Administrator dan pemilik menyetujui paket tugas dan nama role.
2. Periksa permission yang tersedia; jangan membuat key bebas yang tidak
   dipakai pemeriksaan backend.
3. Atur role melalui Identitas & Akses dan gunakan akun petugas sendiri.
4. Periksa menu yang boleh dan tidak boleh dibuka, termasuk pembatasan riwayat.
5. Latih transaksi hanya di lingkungan pelatihan; catat kebutuhan tambahan
   sebelum memberi akses lebih luas pada toko produksi.

Gunakan [indeks panduan](README.md) untuk membuka lembar tugas setiap role.
