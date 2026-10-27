# Role Produksi Dan Matriks Permission

Baseline disetujui 27 Oktober 2026. Seeder khusus:
`App\Modules\Platform\Identity\Database\Seeders\ProductionRolesSeeder`.
Seluruh profil memakai guard `web`. Permission berikut adalah key backend
yang sudah disediakan module, bukan nama permission bebas.

## Ringkasan

| Nama Role | Tugas | Jumlah Permission Baseline |
| --- | --- | --- |
| `super-system` | Administrator sistem; bypass Gate existing | Semua ability melalui Gate, grant existing dipertahankan |
| `owner-supervisor` | Pengawasan, stok awal/koreksi, posting barang retur/transfer, terima dan bayar pembelian | 21 |
| `master-admin` | Master barang/satuan/gudang, pelanggan/level dan supplier | 6 |
| `cashier-operational` | POS lunas dan riwayat penjualan sendiri | 2 |
| `warehouse-staff` | Master readonly, saldo/kartu stok, draft/posting transfer | 5 |
| `purchasing-staff` | Supplier, draft pembelian dan draft retur supplier | 7 |
| `finance-staff` | Akun/metode, pembayaran pembelian diterima, refund dan riwayat uang | 9 |

Role disediakan tanpa membuat akun atau melakukan assignment otomatis. Pengguna
existing tetap memiliki role/direct permission sebelumnya, termasuk role `test`.
Gabungan role menghasilkan gabungan akses; periksa sebelum memberi lebih dari satu.

## Administrator Sistem

`super-system` tetap menggunakan Gate::before yang sudah ada; tidak perlu
menyalin semua permission ke pivot role. Seeder tidak mereset grant existing.
Role yang baru dibuat belum mempunyai pengguna. Bypass hanya permission:
login, status akun, validasi dan invariant bisnis tetap berlaku. Tidak ada
password bawaan atau akun administrator baru dari seeder ini.

## Pemilik / Supervisor

```text
inventory.view
inventory.stock.view
inventory.stock.manage
inventory.stock.post
inventory.stock.adjust
inventory.transfers.view
inventory.transfers.post
sales.parties.view
sales.pos.invoices.view
sales.returns.view
sales.returns.manage
sales.returns.post
purchasing.suppliers.view
purchasing.purchase-orders.view
purchasing.purchase-orders.post
purchasing.purchase-orders.pay
purchasing.returns.view
purchasing.returns.post
finance.payments.view
finance.payments.history.view
reporting.operational.view
```

Supervisor mengelola draft retur penjualan agar alur ini dapat berjalan tanpa
akun sistem. Draft pembelian/retur supplier disiapkan staf pembelian; draft
transfer disiapkan gudang. Supervisor dapat menerima dan membayar pembelian
sekaligus setelah pemeriksaan fisik/dana. Tidak mendapat refund pelanggan/
supplier, CRUD master barang atau pengelolaan akun pengguna.

## Admin Master

```text
inventory.view
inventory.manage
sales.parties.view
sales.parties.manage
purchasing.suppliers.view
purchasing.suppliers.manage
```

`inventory.manage` masih bersama untuk Barang, Satuan dan Gudang; belum terpisah
per master/kolom harga. Tidak memberi hak posting stok atau uang.

## Kasir

```text
sales.pos.invoices.create
sales.pos.invoices.post
```

Dapat membuka POS, memeriksa keranjang, checkout dan membaca transaksi sendiri.
Tidak mendapat invoices.view (semua invoice), refund, master manage atau koreksi
stok. Pemilihan pelanggan di POS tidak membutuhkan akses halaman master pelanggan.

## Staf Gudang

```text
inventory.view
inventory.stock.view
inventory.transfers.view
inventory.transfers.manage
inventory.transfers.post
```

Dapat menyiapkan dan posting transfer. Stok awal dan koreksi menjadi tanggung
jawab supervisor. Tidak memberi hak menerima/membayar pembelian atau refund.
Belum ada pembatasan pengguna per gudang; jangan mengartikannya sebagai gudang
tertentu saja.

## Staf Pembelian

```text
inventory.view
purchasing.suppliers.view
purchasing.suppliers.manage
purchasing.purchase-orders.view
purchasing.purchase-orders.manage
purchasing.returns.view
purchasing.returns.manage
```

Draft tidak memengaruhi stok/uang. Staf pembelian menyerahkan dokumen ke
supervisor untuk penerimaan/serah barang dan keuangan untuk penyelesaian uang
retur. Tidak mendapat post/pay/refund secara otomatis.

## Staf Keuangan

```text
finance.payments.view
finance.payments.manage
finance.payments.history.view
purchasing.purchase-orders.view
purchasing.purchase-orders.pay
sales.returns.view
sales.returns.refund
purchasing.returns.view
purchasing.returns.refund
```

Dapat mengatur akun/metode dan menyelesaikan uang retur posted. Pay tanpa post
tidak memunculkan alur utama Terima dan bayar draft pembelian; pelaksana tahap
itu adalah supervisor. Pay juga melayani dokumen lama yang telah diterima
namun pembayarannya belum dicatat. Tidak mendapat koreksi stok atau laporan
operasional menyeluruh pada baseline minimal ini.

## Menjalankan Provisioning

Pastikan migration dan seeder permission module telah disiapkan melalui proses
deployment masing-masing. Seeder ini **tidak** membuat permission yang hilang,
tidak memanggil seeder demo dan tidak memanggil DatabaseSeeder global.

Untuk workspace toko MySQL, helper menyediakan preview dan aktivasi dengan
pemeriksaan fingerprint seluruh tabel existing (cache dikecualikan):

```powershell
php scripts/qa/activate-production-roles.php
php artisan pos:backup --verify
php scripts/qa/activate-production-roles.php --apply
```

Jalankan tanpa transaksi/operator bersamaan. Backup baru wajib tersedia.
Aktivasi menerapkan seeder dua kali dalam satu transaksi; existing data tetap
identik dan pengulangan kedua harus no-op. Laporan tanpa credential berada di
`storage/framework/testing/production-roles/activation.json` yang ignored.

Pada deployment lain yang sudah memenuhi prasyarat, command standar:

```powershell
php artisan db:seed --class='App\Modules\Platform\Identity\Database\Seeders\ProductionRolesSeeder' --force
```

Jika role produksi bernama sama mempunyai matriks berbeda, provisioning berhenti
atomic. Tidak menambah/mencabut permission diam-diam. Tinjau perubahan manual
melalui Identitas & Akses; jangan hapus role berpengguna agar seeder dapat dipaksa.
Pembuatan role baru dicatat sebagai `identity.production_role_created`; rerun
yang identik tidak membuat audit tambahan. Cache permission dihapus setelahnya.

## Menetapkan Role Ke Petugas

1. Masuk dengan super-system yang sudah ada.
2. Buka **Identitas & Akses** > **Akses Pengguna**.
3. Pilih pengguna dan atur role sesuai tugas dari daftar di atas.
4. Periksa role lama/direct permission sebelum menyimpan. Jangan menghapus
   super-system aktif terakhir atau memberi super-system untuk kasir harian.
5. Petugas login memakai akun sendiri, periksa menu sesuai tugas.
6. Uji transaksi hanya pada lingkungan pelatihan, bukan membuat transaksi palsu
   di data toko. Pembagian ini bukan fitur approval supervisor otomatis.

Panduan penggunaan: [indeks pengguna](../../../user-guide/README.md).
Hasil verifikasi: [work item](work-items/production-roles/README.md).
