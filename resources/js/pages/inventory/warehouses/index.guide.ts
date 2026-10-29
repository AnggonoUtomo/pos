import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Gudang',
    description: 'Daftarkan lokasi penyimpanan fisik yang dipakai untuk stok dan transaksi.',
    sections: [
        {
            title: 'Menyiapkan lokasi',
            steps: [
                'Cari nama atau kode lokasi yang sudah terdaftar.',
                'Tambah gudang dengan kode dan nama yang mudah dikenali, lalu simpan sebagai aktif.',
            ],
        },
        {
            title: 'Menggunakan gudang',
            steps: [
                'Pilih gudang yang benar pada stok awal, pembelian dan POS.',
                'Gunakan Transfer antar-gudang untuk memindahkan barang, lalu periksa kartu stok kedua lokasi.',
                'Edit, hapus atau pulihkan hanya melalui operasi yang diizinkan pada halaman ini.',
            ],
        },
    ],
    notes: [
        'Gudang bukan cabang baru. Stok dan biaya FIFO dihitung terpisah untuk setiap barang dan gudang.',
        'Stok di gudang cadangan tidak otomatis tersedia untuk penjualan dari gudang utama.',
    ],
} satisfies PageGuideContent;
