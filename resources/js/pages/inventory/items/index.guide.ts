import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Barang',
    description: 'Atur identitas barang CCTV/kelistrikan, gambar, satuan, barcode dan harga jual.',
    sections: [
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
