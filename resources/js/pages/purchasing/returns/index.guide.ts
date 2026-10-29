import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Retur Pembelian',
    description: 'Kembalikan barang dari pembelian diterima dan catat uang kembali supplier.',
    sections: [
        {
            title: 'Draft dan serah barang',
            steps: [
                'Buat draft dari pembelian yang sudah Diterima, lalu pilih baris dan jumlah sesuai snapshot asal.',
                'Periksa fisik dan stok sumber, isi catatan yang diperlukan lalu simpan.',
                'Petugas berizin memilih Posting serah barang ketika barang benar-benar diserahkan. Periksa kartu stok.',
            ],
        },
        {
            title: 'Menerima uang supplier',
            steps: [
                'Untuk asal pembelian lunas, pilih Terima uang supplier setelah uang benar-benar diterima.',
                'Periksa nominal otomatis dari nilai beli asal, pilih metode/akun dan isi total tepat.',
                'Konfirmasi penerimaan sekali; pastikan Uang diterima penuh dan riwayat pembayaran cocok.',
            ],
        },
    ],
    notes: [
        'Stok dikurangi dari penerimaan asal; stok sumber lain tidak otomatis dapat menggantikannya. Penerimaan uang tidak mengurangi stok lagi.',
        'Nilai nol diselesaikan tanpa pembayaran nol. Belum ada kredit supplier/penerimaan uang parsial. Hasil tidak pasti diperiksa ulang, bukan menerima uang dua kali.',
    ],
} satisfies PageGuideContent;
