import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Riwayat Pembayaran',
    description: 'Telusuri uang masuk/keluar dari penjualan, pembelian dan kedua jenis retur.',
    sections: [
        {
            title: 'Mencari pembayaran',
            steps: [
                'Gunakan nomor transaksi atau filter tanggal, arah, akun dan metode.',
                'Buka detail untuk mencocokkan snapshot dengan bukti kas, bank atau dokumen asal.',
                'Periksa arah: penjualan dan uang kembali supplier masuk; pembayaran pembelian serta refund pelanggan keluar.',
            ],
        },
        {
            title: 'Jika ada perbedaan',
            steps: [
                'Catat nomor, tanggal, metode dan sumber transaksi yang perlu ditelusuri.',
                'Periksa modul asal atau hubungi penanggung jawab; gunakan Buku Kas untuk melihat saldo dan mutasi akun.',
            ],
        },
    ],
    notes: [
        'Halaman ini readonly, bukan tempat membuat pembayaran manual atau menghapus pembayaran.',
        'Total masuk dikurangi keluar adalah arus pembayaran, bukan saldo rekening atau laba.',
    ],
} satisfies PageGuideContent;
