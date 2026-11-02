import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Barang',
    description: 'Atur identitas barang, gambar, satuan, barcode dan harga jual sesuai usaha toko.',
    sections: [
        {
            title: 'Kategori dan merek',
            steps: [
                'Siapkan master melalui menu Kategori atau Merek. Saat mengisi barang, cari dan pilih kategori/merek aktif bila diperlukan; keduanya opsional.',
                'Gunakan filter kategori/merek pada daftar untuk mempersempit hasil bersama pencarian. Mengganti filter kembali ke halaman pertama.',
                'Mengganti kategori/merek tidak mengganti harga, satuan, stok, FIFO atau snapshot transaksi lama.',
            ],
        },
        {
            title: 'Mencari pilihan',
            steps: ['Cari satuan dasar atau satuan tambahan dengan nama/simbol, lalu pilih hasilnya. Satuan dasar barang existing tetap terkunci.'],
        },
        {
            title: 'Persiapan',
            steps: [
                'Pastikan satuan dan level pelanggan yang diperlukan sudah aktif.',
                'Cari SKU atau nama untuk menghindari barang ganda, lalu klik Tambah barang.',
            ],
        },
        {
            title: 'Mengisi barang dan harga',
            steps: [
                'Isi SKU unik, nama dan satuan dasar terkecil. Contohnya kabel memakai Meter, kamera memakai Unit.',
                'Tambahkan kemasan dan Isi satuan dasar. Contoh 1 Dus isi 4 berarti setiap Dus sama dengan 4 Unit, bukan saldo stok.',
                'Atur satuan boleh Jual/Beli dan barcode berbeda per satuan. Gunakan barcode pabrikan atau buat/cetak label internal yang tersedia.',
                'Lengkapi harga jual positif untuk seluruh satuan jual dan level aktif. Tambahkan satu gambar utama jika diperlukan, lalu simpan.',
            ],
        },
        {
            title: 'Menghitung usulan harga',
            steps: [
                'Pada Edit barang, isi markup persen untuk level yang ingin dihitung. Persen kosong tidak mengubah level itu, sedangkan 0 berarti tanpa markup.',
                'Pilih Hitung usulan atau Alt+G. Acuan berasal dari pembelian terakhir yang sudah diterima di seluruh gudang, dikonversi ke satuan jual pada form.',
                'Periksa sumber biaya dan harga usulan. Pilih Terapkan ke form atau Alt+A, sesuaikan lagi bila perlu, lalu Simpan barang.',
                'Jika belum ada pembelian diterima, isi harga manual. Markup bukan margin laba bersih; diskon dan biaya operasional belum diperhitungkan.',
            ],
        },
        {
            title: 'Sesudah disimpan',
            steps: [
                'Periksa harga, barcode dan gambar pada master serta katalog POS.',
                'Catat stok melalui stok awal atau penerimaan pembelian. Menyimpan barang tidak menambah persediaan.',
            ],
        },
    ],
    notes: [
        'Satuan dasar tidak dapat diganti setelah barang dibuat. Harga beli/HPP diisi pada stok awal atau pembelian.',
        'Gunakan format Indonesia: 1.250 untuk ribuan dan 0,5 untuk pecahan. Perubahan harga master tidak mengubah transaksi lama.',
    ],
} satisfies PageGuideContent;
