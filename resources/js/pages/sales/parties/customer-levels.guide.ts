import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Level Pelanggan',
    description: 'Kelompokkan harga jual Eceran, Grosir atau Installer sesuai kebijakan toko.',
    sections: [
        {
            title: 'Membuat level',
            steps: [
                'Cari kode/nama agar tidak menggandakan level yang sudah ada.',
                'Tambah level pelanggan, isi kode dan nama, lalu simpan sebagai aktif.',
            ],
        },
        {
            title: 'Menghubungkan harga',
            steps: [
                'Koordinasikan dengan admin barang untuk melengkapi matriks harga setiap satuan jual.',
                'Pilih level yang benar pada pelanggan sebelum melakukan transaksi.',
                'Nonaktifkan, hapus atau pulihkan hanya sesuai izin dan aturan referensi.',
            ],
        },
    ],
    notes: [
        'Level bukan diskon otomatis berdasarkan jumlah belanja. Harga berasal dari matriks barang, satuan dan level.',
        'Level yang dipakai pelanggan tidak boleh dihapus/dinonaktifkan, termasuk referensi pelanggan terhapus.',
    ],
} satisfies PageGuideContent;
