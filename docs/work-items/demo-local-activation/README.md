# Aktivasi Demo Lokal Dan Katalog POS

Status: Done. Target: Inventory/Units, Items, Warehouses, Stock,
Sales/Parties dan mock UI POS. User mengonfirmasi master dan layar POS masih
memakai demo lama pada 13 Oktober 2026.

Migration customer masters sudah Ran, tetapi database lokal pos belum berisi
demo CCTV/kelistrikan atau pelanggan. Mock POS memiliki katalog sembako terpisah.
Scope: jalankan demo existing secara additive pada local, selaraskan katalog,
satuan, gudang dan level mock, lalu build dan verifikasi layar.

Tidak reset database, menghapus demo lama, mengubah akun/password/role/grant,
mengganti theme atau mengimplementasikan pricing/penjualan live. Nilai stok dan
harga mock fiktif; mock tidak membaca saldo/harga/gudang per transaksi dari DB.

Demo sudah dijalankan eksplisit pada MySQL lokal pos. Katalog baru dan level
tersedia, stok awal CCTV/kelistrikan posted; repeat tidak menambah data/audit.
Data lama tetap ada. Asset POS sudah dibuild ulang untuk virtual host pos.test.
