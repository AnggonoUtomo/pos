import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Password Pribadi',
    description: 'Ganti password akun sendiri dengan tetap menjaga kerahasiaan akses toko.',
    sections: [
        {
            title: 'Mengganti password',
            steps: [
                'Isi password saat ini untuk mengonfirmasi kepemilikan akun.',
                'Masukkan password baru yang kuat dan konfirmasinya sama persis.',
                'Simpan lalu baca pesan hasil. Jika ada validasi, perbaiki kolom yang ditandai.',
            ],
        },
        {
            title: 'Sesudah berhasil',
            steps: [
                'Gunakan password baru saat masuk berikutnya dan simpan secara pribadi dengan aman.',
                'Jika lupa password atau tidak mengenali perubahan akun, hubungi administrator atau gunakan alur pemulihan yang tersedia.',
            ],
        },
    ],
    notes: [
        'Jangan menyampaikan password melalui catatan transaksi atau meminjam akun pengguna lain. Halaman ini bukan reset password pengguna lain oleh admin.',
    ],
} satisfies PageGuideContent;
