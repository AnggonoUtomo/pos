import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Riwayat Penjualan',
    description: 'Cari invoice yang sudah berhasil dan cetak ulang bukti tanpa membuat penjualan baru.',
    sections: [
        {
            title: 'Mencari invoice',
            steps: [
                'Cari nomor invoice atau gunakan filter tanggal, gudang dan pelanggan.',
                'Buka bukti dan cocokkan pelanggan, barang, satuan, total serta pembayaran.',
                'Gunakan pagination untuk menelusuri daftar jika hasilnya banyak.',
            ],
        },
        {
            title: 'Cetak ulang dan koreksi',
            steps: [
                'Cetak bukti transaksi yang sama melalui dialog browser dan printer yang benar.',
                'Jika barang kembali, gunakan Retur Penjualan berdasarkan invoice asal sesuai kewenangan.',
            ],
        },
    ],
    notes: [
        'Kasir melihat transaksi miliknya; melihat semua memerlukan izin tambahan. Halaman ini tidak menyediakan edit/hapus bebas.',
        'Cetak batal/gagal bukan alasan membuat penjualan ulang. Jika posting sebelumnya belum pasti, telusuri hasil terlebih dahulu.',
    ],
} satisfies PageGuideContent;
