import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Pengaturan Toko',
    description: 'Simpan identitas dan informasi toko yang akan dicetak pada transaksi baru.',
    sections: [
        {
            title: 'Mengisi identitas',
            steps: [
                'Siapkan nama, kontak, alamat dan informasi toko yang telah disahkan pemilik.',
                'Isi kolom yang tersedia dan periksa penulisan serta catatan struk.',
                'Simpan pengaturan sesuai izin akun Anda.',
            ],
        },
        {
            title: 'Memeriksa hasil',
            steps: [
                'Periksa identitas pada bukti transaksi baru yang sah atau lingkungan latihan.',
                'Jika hasil berbeda, periksa waktu transaksi dan apakah pengaturan sudah berhasil disimpan.',
            ],
        },
    ],
    notes: [
        'Bukti lama tetap memakai identitas saat transaksi dibuat; perubahan pengaturan tidak menulis ulang struk lama.',
        'Jangan membuat penjualan coba-coba pada data toko hanya untuk menguji tampilan.',
    ],
} satisfies PageGuideContent;
