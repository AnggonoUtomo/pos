# PRD: Penerimaan Pembelian Penuh

Admin gudang/pembelian menerima seluruh barang dalam draft ke satu gudang.
Harga beli positif per satuan pilihan tetap mengikuti draft, konversi tepat
ke satuan dasar. Memerlukan konfirmasi sebelum posting karena ledger immutable.

User menyetujui satu kali terima penuh; bukan parsial. Sukses: saldo/movement/
FIFO dan status dokumen commit bersama, retry tidak menggandakan stok, barang
tanpa stok awal dapat diterima/dijual FIFO, saldo/kartu stok terlihat, posted
readonly dengan permission terpisah dan audit. Tidak memberi kesan sudah lunas.

UI admin existing, status Draft/Diterima, filter all/draft/posted, detail waktu
penerimaan, konfirmasi ringkas gudang/baris/total. Dropdown shortcut diperbarui
untuk konfirmasi dialog, bukan shortcut posting tanpa memilih dokumen.
Desktop/tablet Light/Dark; mobile ditunda. QA utama mencakup receipt pertama,
tambahan setelah opening/sale, FIFO urutan lama-baru, rollback/duplicate/stale,
status/permission, format angka dan preservation data lokal.
