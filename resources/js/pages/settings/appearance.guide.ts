import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Tampilan Akun',
    description: 'Pilih mode tampilan yang nyaman untuk perangkat kerja Anda.',
    sections: [
        {
            title: 'Memilih mode',
            steps: [
                'Pilih Light untuk terang, Dark untuk gelap atau System untuk mengikuti perangkat.',
                'Periksa keterbacaan teks dan kontras pada ruang kerja Anda.',
            ],
        },
        {
            title: 'Warna aksen',
            steps: [
                'Gunakan menu Tema pada toolbar untuk memilih aksen yang tersedia.',
                'Buka kembali halaman kerja dan gunakan pilihan yang paling nyaman tanpa mengubah data transaksi.',
            ],
        },
    ],
    notes: ['Pilihan tampilan tidak mengubah stok, harga, pembayaran atau permission akun. Preferensi browser/perangkat dapat berbeda.'],
} satisfies PageGuideContent;
