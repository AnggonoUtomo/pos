# Format Indonesia Untuk Isi Satuan Dasar

Status: Done. Target Inventory/Items, editor multi-satuan pada halaman
/inventory/items. User meminta angka tidak tampil mentah seperti 1.000000.

Rasio menyatakan isi per satu satuan pilihan dalam satuan dasar: Buah 1,
Pak 6 berarti satu pak berisi enam buah. Tampilan/editor memakai format
Indonesia: 1.000000 menjadi 1, 6.000000 menjadi 6, 1.500000 menjadi 1,5.
Titik dipakai sebagai pemisah ribuan (1.000 = seribu); koma untuk desimal.

API dan database tetap decimal string bertitik, presisi enam digit. Tidak
mengubah schema, stok, FIFO, barcode, seeder, transaksi POS, theme atau data
existing. Tidak menormalisasi angka lewat Number/float.
