# Demo CCTV Dan Kelistrikan

Status: Done. Target: Inventory/Units, Inventory/Items, Inventory/Stock.

Demo beralih ke toko CCTV dan kelistrikan. Produk, isi kemasan, barcode dan
harga pokok adalah contoh fiktif, bukan spesifikasi vendor atau harga pasar.
Tidak ada perubahan schema, theme, transaksi POS, akun atau grant permission.
Data lama di database tidak dihapus; seed ulang tidak menimpa perubahan user.

## Dataset

Satuan: PCS (Buah), UNIT (Unit), SET (Set), PAIR (Pasang), PACK (Pak),
BOX (Dus), M (Meter), ROLL (Rol). Konversi selalu milik barang.

| SKU | Barang | Base | Satuan tambahan |
| --- | --- | --- | --- |
| DEMO-CCTV-IP-2MP | Kamera IP 2MP | UNIT | BOX = 4 UNIT |
| DEMO-CCTV-NVR-8CH | NVR 8 Kanal | UNIT | BOX = 2 UNIT |
| DEMO-CCTV-UTP-CAT6 | Kabel UTP Cat6 | M | ROLL = 305 M |
| DEMO-ELEC-NYM-3X15 | Kabel NYM 3x1.5 mm2 | M | ROLL = 100 M |
| DEMO-ELEC-MCB-6A | MCB 1P 6A | UNIT | BOX = 12 UNIT |
| DEMO-CCTV-RJ45 | Konektor RJ45 | PCS | PACK = 100 PCS; BOX = 1000 PCS |
| DEMO-CCTV-ADAPTER-12V | Adaptor CCTV 12V | UNIT | BOX = 10 UNIT |
| DEMO-CCTV-BALUN | Balun Video | PCS | PAIR = 2 PCS |
| DEMO-CCTV-KIT | Paket CCTV | SET | Tidak ada |

Stok awal WH-MAIN: kamera 2 BOX @ Rp2.000.000; UTP 1 ROLL @ Rp1.100.000;
NYM 1.5 ROLL @ Rp1.500.000; MCB 1 BOX @ Rp360.000;
RJ45 1 PACK @ Rp150.000. Harga pokok per satuan pilihan, bukan harga jual.
NYM menjadi 150 M; 0.125 ROLL NYM menjadi 12.5 M.

Marker baru `DEMO: cctv-electrical-opening-v1` tidak mengganti dokumen lama.
Jika marker sudah ada atau salah satu pasangan barang/gudang sudah bersaldo,
demo stok dilewati seluruhnya. Posted history tidak disunting.

Jalankan manual hanya pada local/testing, urutan WarehousesDemoSeeder,
UnitsDemoSeeder, ItemsDemoSeeder, StockDemoSeeder, dengan
`php artisan db:seed --class="App\Modules\Inventory\{Module}\Database\Seeders\{Module}DemoSeeder"`.
Tidak otomatis menjalankan demo pada database operasional user.

Langkah berikutnya yang disetujui: Sales/Parties, master pelanggan dan level
Eceran/Grosir/Installer. Harga barang/satuan dan transaksi POS terpisah.
