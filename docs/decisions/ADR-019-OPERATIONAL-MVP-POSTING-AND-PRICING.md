# ADR-019: Posting Dan Harga MVP Operasional

Status: Accepted. Tanggal: 24 Oktober 2026.

## Konteks

User mengunci empat paket untuk segera dipakai toko, dengan penjualan dan
pembelian langsung lunas. Transfer masih draft, sedangkan snapshot diskon dan
pajak existing belum memiliki input/perhitungan operasional.

## Keputusan

- Transfer penuh sekali posting dalam satu transaction: issue FIFO asal,
  receipt tujuan, status dan audit. Gudang dikunci menurut ID terurut.
- Setiap segmen biaya asal menjadi layer tujuan baru bertanggal posting;
  origin_fifo_layer_id menjaga provenance. Layer lama tidak dipindah/ditulis
  ulang; nilai nol hasil alokasi tetap boleh berpindah.
- Koreksi fisik memakai dokumen langsung posted, alasan dan permission khusus.
  Kuantitas dalam satuan dasar; masuk memakai harga pokok positif exact6,
  keluar memakai FIFO. Ledger bukan objek CRUD mutable.
- Diskon nota persen/nominal dialokasikan proporsional kumulatif DOWN exact2;
  pajak tambahan atas nilai setelah diskon, HALF_UP exact2 tiap baris. Header
  merupakan jumlah baris. Tarif default nol, maksimum100%; total harus positif.
- Snapshot input diskon/tarif disimpan. Retur memakai snapshot nilai bersih
  existing, sehingga tidak bergantung harga/konfigurasi terkini.
- Pembelian memiliki jalur utama terima dan bayar dalam satu transaction,
  memakai use case receipt/payment existing. Jalur lama dipertahankan untuk
  kompatibilitas dokumen operasional sebelumnya, bukan fitur utang baru.
- Identitas toko disnapshot pada invoice baru; cetak ulang invoice lama tidak
  mengarang identitas historis. CompanySettings tidak memiliki delete.

## Konsekuensi

Tidak ada in-transit/transfer parsial, pajak inklusif/multi-tarif, barang gratis,
piutang/utang, atau full accounting. Alternatif layer transfer gabungan ditolak
karena menghilangkan segmen biaya; transfer dua tahap ditunda agar alur utuh
lebih cepat siap. QA terfokus selama coding dan gate gabungan dipakai kembali
bila source tidak berubah.
