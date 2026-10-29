import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Profil Akun',
    description: 'Perbarui identitas akun pribadi tanpa mengubah role atau hak akses toko.',
    sections: [
        {
            title: 'Memperbarui profil',
            steps: [
                'Periksa nama dan email akun yang sedang masuk.',
                'Ubah data yang diperlukan lalu simpan. Baca validasi jika email sudah digunakan atau tidak valid.',
                'Ikuti verifikasi email jika diminta oleh aplikasi.',
            ],
        },
        {
            title: 'Pengaturan akses dan keamanan',
            steps: [
                'Untuk mengubah password pribadi, buka menu Password pada pengaturan akun.',
                'Hubungi administrator untuk perubahan role atau penonaktifan akun. Halaman profil tidak menyediakan penghapusan akun pribadi.',
            ],
        },
    ],
    notes: [
        'Mengubah nama atau email tidak mengubah role, hak akses atau riwayat transaksi.',
        'Jangan membagikan password atau memakai akun kasir lain untuk bekerja.',
    ],
} satisfies PageGuideContent;
