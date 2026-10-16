# PRD: Riwayat Penjualan

## Persetujuan Dan Tujuan

Scope disetujui 16 Oktober 2026: kasir/admin perlu mencari penjualan posted dan
mencetak ulang dari server, bukan bergantung pada receipt terakhir per tab.
Kasir post hanya transaksi sendiri; permission view membuka seluruh transaksi.

## Kebutuhan

- Daftar invoice, waktu WIB, pelanggan/level, gudang, lunas dan nominal Indonesia.
- Cari nomor, filter rentang tanggal/gudang/pelanggan, pagination dan reset.
- Filter tetap di URL/reload; master nonaktif/terhapus tidak menghilangkan histori.
- Detail/cetak ulang snapshot yang sama dengan checkout, tanpa HPP/kontak/rekening.
- Desktop/tablet Light/Dark, navigasi/palette/shortcut, error/reload dan empty.
- Backend membatasi seluruh rows/count/options/detail; read tidak memutasi ledger.

## Non-Scope

Edit/delete/void/retur, export/margin/agregat penjualan, permission/grant baru,
offline/recovery ambigu otomatis, perubahan retention, theme atau transaksi.

Acceptance dan bukti rinci berada di [README](README.md) dan [tasks](tasks.md).
