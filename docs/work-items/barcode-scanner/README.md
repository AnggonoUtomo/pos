# Barcode Scanner Dan Label Barang

Status: implementasi. User menyetujui barcode pabrikan + buat/cetak label internal,
scanner USB/Bluetooth mode keyboard, serta instalasi JsBarcode untuk Code128.

## Scope

- Inventory/Items: generate kode internal pada field kosong atas tindakan user;
  simpan melalui form existing (unique constraint, permission dan audit tetap).
  Cetak label dari barang tersimpan, pilih satuan/jumlah/ukuran dan A4 atau label
  tunggal lewat dialog browser. Tidak menimpa barcode existing/bulk reseed.
- Sales/POS: exact barcode lookup backend, prioritas barcode atas SKU; fallback
  SKU exact untuk kompatibilitas. Scan langsung + Enter tidak bergantung debounce
  atau halaman katalog. Antrean terbatas, scan berulang menambah satu satuan,
  kuantitas agregat per barang tidak melebihi stok. Quote/post tetap authority.
- Purchasing/PurchaseOrders: barcode dari published ItemStockLookup, scan draft
  memasukkan barang/satuan/qty1; harga beli tetap diisi operator. Scan ulang satuan
  sama menambah1 tanpa mengubah harga. Satuan berbeda ditolak sesuai invariant
  satu barang satu baris existing, tanpa konversi harga otomatis.
- Barcode tetap string (leading zero aman). Barcode tidak ditemukan/nonaktif/
  bukan satuan beli/jual ditolak, tidak membuat barang atau transaksi otomatis.
- Tidak ada kamera/QR/serial, raw printer/driver, reservasi atau mutation ledger
  pada scan. Cetak fisik/scanner hardware perlu UAT user; tidak dianggap lulus
  hanya karena browser/simulasi keyboard lulus.

## Boundary Dan Keamanan

Items mempublikasikan DTO scan lewat SaleItemCatalog; POS tidak mengimpor model
privat Items. PurchaseWorkspace hanya menambah metadata barcode dari DTO publik
existing. Tidak ada schema baru, role/grant, scheduler, reset atau data seed toko.
Kode internal acak20 digit bukan nomor GS1/EAN/UPC; dicetak sebagai Code128.
Unique constraint backend tetap final; collision ditangani sebagai validation,
bukan overwrite. Label ASCII printable yang tak muat tidak dipaksa mengecil.

Sumber library: https://github.com/lindell/JsBarcode. Library yang teruji dipakai
untuk encoding, bukan membuat algoritma barcode sendiri.
