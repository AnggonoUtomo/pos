import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Order Pembelian',
    description: 'Catat pembelian dari draft sampai penerimaan stok dan pembayaran lunas.',
    sections: [
        {
            title: 'Menyiapkan draft',
            steps: [
                'Pilih supplier dan gudang tujuan, lalu tambah atau scan barcode barang.',
                'Isi satuan beli, jumlah dan harga beli positif per satuan pilihan. Contoh 2 Dus x Rp120.000/Dus = Rp240.000.',
                'Simpan draft dan cocokkan baris serta total dengan nota supplier.',
            ],
        },
        {
            title: 'Terima dan bayar',
            steps: [
                'Periksa seluruh barang fisik; penerimaan tahap ini penuh satu kali.',
                'Petugas berizin memilih Terima dan bayar, isi metode serta nominal gabungan yang totalnya tepat tanpa kembalian.',
                'Konfirmasi Catat pembayaran sekali, tunggu Diterima/Lunas lalu periksa kartu stok dan riwayat pembayaran.',
            ],
        },
        {
            title: 'Dokumen lama diterima',
            steps: [
                'Jika sudah Diterima tetapi pembayaran Belum dicatat, gunakan Bayar pembelian sesuai izin. Pembayaran ini tidak menambah stok lagi.',
            ],
        },
    ],
    notes: [
        'Draft belum menambah stok. Jangan mencatat stok awal untuk barang pembelian yang sama.',
        'Jika respons tidak pasti, gunakan pemulihan hasil yang tersedia. Belum mendukung pembelian tempo atau penerimaan parsial.',
    ],
} satisfies PageGuideContent;
