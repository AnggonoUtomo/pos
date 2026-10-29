import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Stok',
    description: 'Tetapkan stok awal, baca saldo/kartu stok dan catat koreksi fisik yang sudah disahkan.',
    sections: [
        {
            title: 'Stok awal',
            steps: [
                'Pilih gudang dan buat draft stok awal untuk barang yang benar-benar sudah tersedia sebelum operasional.',
                'Isi barang, satuan, jumlah dan harga pokok positif per satuan pilihan; cocokkan dengan hitungan fisik.',
                'Periksa draft lalu posting. Pastikan saldo dan kartu stok bertambah di gudang yang benar.',
            ],
        },
        {
            title: 'Menelusuri selisih',
            steps: [
                'Pada Saldo stok atau Kartu stok, gunakan filter barang dan gudang.',
                'Cek dokumen masuk, keluar, retur dan transfer sebelum menyimpulkan stok salah.',
            ],
        },
        {
            title: 'Koreksi yang disahkan',
            steps: [
                'Gunakan Koreksi stok sesuai izin; pilih tambah atau kurangi dan isi kuantitas SELISIH dalam satuan dasar.',
                'Untuk tambahan, isi harga pokok yang diminta. Sertakan alasan, periksa lalu Posting koreksi.',
            ],
        },
    ],
    notes: [
        'Stok awal hanya sekali per barang dan gudang. Pembelian berikutnya tidak dicatat ulang sebagai stok awal.',
        'Contoh saldo 10 Meter, fisik 8,5 Meter: kurangi 1,5, bukan 8,5. Stok tidak boleh minus; koreksi bukan sesi opname formal.',
    ],
} satisfies PageGuideContent;
