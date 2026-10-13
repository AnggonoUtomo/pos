# PRD: Harga Jual Barang

Admin memasukkan barang, satuan dan harga dalam satu form. Setiap satuan dengan
flag Jual memunculkan kolom harga untuk setiap level pelanggan aktif. Semua
harga wajib positif; pasangan di luar matrix/duplikat/level nonaktif ditolak.
Harga masing-masing level/satuan diisi eksplisit, bukan dihitung otomatis dari
level lain. Input Rp memakai titik ribuan dan koma pecahan tanpa float.

Save baru/edit menyimpan barang, relasi satuan, harga dan audit secara atomic.
Gagal satu bagian membatalkan semuanya. Barang purchase-only tanpa satuan jual
tidak membutuhkan harga. Level terpakai harga, termasuk harga terhapus, tidak
dapat dihapus/nonaktif. Menambah level baru mewajibkan matrix lengkap pada edit
berikutnya; tidak membuat default harga tanpa keputusan admin.

Master harga mutable memakai soft delete/restore untuk perubahan konfigurasi,
bukan pengganti reversal transaksi. Transaksi posted belum dibuat dan kelak
wajib memiliki snapshot harga. Restore master barang existing mempertahankan
harga sebelumnya; bukan cara melengkapi harga yang belum dikonfigurasi.
