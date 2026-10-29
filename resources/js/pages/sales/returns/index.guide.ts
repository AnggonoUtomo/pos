import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Retur Penjualan',
    description: 'Terima kembali barang layak jual dan proses refund berdasarkan invoice asal.',
    sections: [
        {
            title: 'Draft dan penerimaan barang',
            steps: [
                'Buat draft dari invoice posted, pilih barang dan jumlah yang benar-benar kembali.',
                'Jumlah tidak boleh melebihi sisa yang dapat diretur. Periksa snapshot satuan/harga dan simpan draft.',
                'Petugas berizin memilih Terima barang retur. Stok masuk ke gudang invoice asal dengan biaya dari penjualan asal.',
            ],
        },
        {
            title: 'Refund pelanggan',
            steps: [
                'Setelah retur diterima, keuangan memilih Refund dan memeriksa nominal otomatis.',
                'Pilih metode aktif dan isi pembayaran tepat sebesar refund; metode boleh berbeda dari pembayaran asal.',
                'Konfirmasi refund sekali dan periksa hasil serta riwayat pembayaran. Nilai nol diselesaikan Tanpa pengembalian uang.',
            ],
        },
    ],
    notes: [
        'Terima barang belum berarti uang telah dikembalikan. Barang rusak/karantina dan tukar barang belum masuk alur ini.',
        'Refund mengikuti nilai bersih invoice asal, bukan harga terkini. Jika hasil belum pasti, gunakan Periksa ulang hasil dan jangan menyerahkan uang dua kali.',
    ],
} satisfies PageGuideContent;
