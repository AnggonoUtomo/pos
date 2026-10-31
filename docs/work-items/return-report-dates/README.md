# Batas Tanggal Ringkasan Retur

User mengizinkan patch terpisah setelah regresi opname menemukan ringkasan
retur dini hari WIB tidak dihitung. Header retur penjualan/pembelian menyimpan
instant posted dari Stock sebagai UTC; settlement refund memakai waktu
aplikasi. Reader semula memakai batas waktu aplikasi untuk kedua jenis kolom.

Patch hanya mengubah batas query count posted ke UTC, tanpa menulis ulang data,
mengubah timezone .env, writer, nilai refund, filter gudang atau transaksi lama.
Regresi memakai posting nyata, pergantian hari/bulan, UTC dan Asia/Jakarta.
