import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Pelanggan',
    description: 'Daftarkan pembeli dan level harga yang akan digunakan pada POS.',
    sections: [
        {
            title: 'Membuat pelanggan',
            steps: [
                'Pastikan level pelanggan yang sesuai sudah aktif dan harganya tersedia.',
                'Cari pelanggan terlebih dahulu, lalu tambah kode/nama dan pilih satu level aktif.',
                'Isi kontak atau alamat bila diperlukan, simpan dan periksa kembali.',
            ],
        },
        {
            title: 'Saat transaksi',
            steps: [
                'Cari pelanggan melalui pencarian autocomplete pada POS sebelum memeriksa keranjang.',
                'Pastikan level dan harga yang tampil sesuai pembeli sebenarnya.',
                'Gunakan edit, status, hapus atau pulihkan dari master sesuai izin.',
            ],
        },
    ],
    notes: [
        'Kontak/alamat opsional, tetapi level wajib. Jangan memilih level Installer untuk pembeli Eceran hanya agar harga lebih murah.',
        'Perubahan master pelanggan tidak mengubah snapshot transaksi yang sudah diposting.',
    ],
} satisfies PageGuideContent;
