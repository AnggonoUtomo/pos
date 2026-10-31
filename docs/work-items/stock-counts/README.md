# Stock Opname Per Gudang

## Scope dan Keputusan

Inventory/Stock memiliki sesi opname, snapshot saldo, hasil hitung satuan
dasar, review supervisor dan posting selisih. User menyetujui penghentian
mutasi stok selama sesi aktif dan harga pokok positif wajib untuk selisih
tambah. Selisih kurang memakai FIFO existing; selisih nol tanpa ledger.

Sesi: counting -> review -> posted, atau cancelled dengan alasan. Review bisa
dikembalikan untuk hitung ulang. Mulai sesi langsung menahan gudang; konfirmasi
UI wajib. Satu sesi aktif per gudang; gudang lain tetap berjalan. Draft dan
pembayaran yang tidak mengubah stok tetap tersedia. Replay posted berhasil
tidak diblokir dan tidak membuat stok/payment baru.

Snapshot mencakup semua pasangan saldo gudang, termasuk nol. Barang tambahan
tanpa saldo boleh ditambahkan eksplisit. Baris belum dihitung bukan angka nol;
seluruh baris wajib diisi sebelum review. Input/harga presisi enam desimal,
tampilan angka Indonesia. Barcode mengidentifikasi barang/satuan, hasil hitung
tetap dalam satuan dasar, bukan auto-menambah kuantitas scan.

Master bersaldo yang nonaktif harus diaktifkan sebelum mulai sesi. Snapshot
nama/satuan disimpan; perubahan master tidak boleh mengubah satuan dasar atau
melewati revalidasi posting. Header sesi diaudit, bukan CRUD soft-delete:
pembatalan menggantikan penghapusan agar jejak penahanan gudang terpelihara.

## Pengaman

Warehouse row lock menjadi urutan serialisasi bersama writer existing.
Guard di create receipt/issue/post opening membaca sesi aktif dengan current
read. Hanya posting sesi yang sama dalam status internal posting boleh lewat;
identifier ini tidak diterima dari endpoint koreksi biasa. Semua adjustment,
FIFO, audit, status dan pelepasan gudang berada dalam satu transaction.

Tidak ada perubahan aturan stok minimum, serial/garansi, Finance atau transaksi
lama. Tidak reset/seed data toko atau mengubah grant akun. Demo opname memakai
database disposable; tidak memulai sesi atau membekukan gudang saat deployment.

## Status

Dalam pengerjaan. [Plan](plan.md), [checklist](tasks.md).
