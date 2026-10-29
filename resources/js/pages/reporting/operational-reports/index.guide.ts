import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Laporan Operasional',
    description: 'Baca transaksi, arus pembayaran dan persediaan dengan periode/gudang yang sesuai.',
    sections: [
        {
            title: 'Mengatur laporan',
            steps: [
                'Pilih Dari tanggal dan Sampai tanggal; periode maksimal 366 hari.',
                'Pilih gudang transaksi/stok atau Semua gudang lalu Terapkan.',
                'Baca ringkasan dan chart penjualan lunas, pembelian diterima serta nilai refund yang selesai.',
            ],
        },
        {
            title: 'Menafsirkan angka',
            steps: [
                'Arus pembayaran selalu seluruh gudang; perhatikan label scope walaupun filter gudang transaksi dipilih.',
                'Stok terkini menunjukkan posisi sekarang, bukan saldo historis pada akhir periode.',
                'Gunakan riwayat transaksi/pembayaran dan kartu stok untuk menelusuri selisih.',
            ],
        },
    ],
    notes: [
        'Selisih pembayaran bukan saldo rekening atau laba bersih. Penjualan belum dikurangi retur; refund selesai disajikan terpisah.',
        'Chart memakai data nyata periode terpilih, bukan tren contoh. Tidak ada transaksi pada periode bukan berarti persediaan saat ini kosong.',
    ],
} satisfies PageGuideContent;
