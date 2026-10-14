# ADR-012: Checkout Lunas Dan Preservasi Residual FIFO

## Status

Accepted. Implementasi bertahap, bukan klaim checkout sudah tersedia.

## Tanggal

2026-10-15

## Konteks

User menyetujui checkout pertama wajib lunas, multi-metode, maksimal satu
baris tunai dengan kembalian, serta non-tunai tidak boleh berlebih. Stock sudah
menyimpan quantity/nilai layer exact scale6; biaya dasar hasil pembagian bukan
sumber kebenaran dan dapat memiliki pecahan berulang. Ketentuan persistence
settlement dan HPP sulit diubah setelah transaksi posted ada.

## Keputusan

- Sales/POS menjadi transaction boundary untuk dokumen, pemanggilan published
  Stock/Finance, idempotency dan audit pada database default yang sama.
  Tidak menggunakan event async untuk invariant stok/pelunasan sinkron.
- Finance memiliki perhitungan settlement dan payment record. Non-cash exact,
  satu cash boleh kelebihan; change dibebankan hanya pada baris cash. Simpan
  tendered/applied/change terpisah; penerimaan net dijumlah menjadi tagihan.
  Baris cash tanpa kontribusi setelah non-cash lunas ditolak.
- Uang scale2 mengikuti quote existing, tanpa float/pembulatan tersembunyi.
  Quantity20,6 dan nilai HPP30,6 mengikuti Stock existing. Overflow ditolak.
- Partial FIFO: nilai = remaining_value x taken_quantity / remaining_quantity,
  dibulatkan turun scale6. Full drain mengambil persis seluruh remaining_value.
  Residual tetap di layer, bukan dibuang atau direkonstruksi dari unit_cost.
  Contoh nilai1/qty3: 0,333333 + 0,333333 + 0,333334 = 1.
- FIFO mengikuti received_at lalu ULID per item/gudang. Domain menerima layer
  berurutan dari caller; adapter kelak wajib memfilter/mengunci pasangan itu.
  Unit test bukan bukti concurrency atau perizinan mutation database.
- Posted/snapshot immutable. Input diskon/pajak, kredit, void dan retur belum
  scope; diskon/pajak disimpan eksplisit nol pada slice checkout awal.
- Domain murni dan tests dibuat pada Increment 1; port/adapter/schema/route
  baru menunggu consumer dan increment masing-masing, tanpa dummy integration.

## Alternatif

- Semua metode dapat memberi kembalian: asal kas keluar menjadi ambigu.
- Menganggap seluruh tendered sebagai kas masuk: melebihkan penerimaan tunai.
- Average unit cost dibulatkan lalu dikali: dapat menghilangkan residual stok.
- Rounding setiap partial ke atas: dapat menguras nilai sebelum quantity habis.
- Menulis payment/stock melalui async event atau transaksi terpisah: penjualan
  dapat sukses sementara stok atau uang gagal.

## Konsekuensi

Positif: kas bersih jelas, HPP/stock value terjaga, retry dan atomicity dapat
diuji. Negatif: beberapa alokasi partial HPP dapat nol pada presisi mikro,
sementara residual diterima alokasi terakhir; bukan item gratis pada pricing.
Fase ini bukan pengganti rekonsiliasi bank atau general ledger.

## Verifikasi

Unit settlement dan FIFO menguji batas decimal, invalid input dan conservation.
Increment integrasi wajib menambah snapshot/rollback/idempotency/MySQL race dan
browser, sebelum checkout aktif. Status/bukti ada di work item.

## Referensi

- [Work item](../work-items/pos-paid-checkout/README.md)
- [Stock](../modules/Inventory/Stock/specification.md)
- [POS](../modules/Sales/POS/specification.md)
- [Finance](../modules/Finance/Payments/specification.md)
