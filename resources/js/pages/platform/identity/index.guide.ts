import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Identitas Dan Akses',
    description: 'Kelola pengguna, role dan izin sesuai pembagian tugas toko.',
    sections: [
        {
            title: 'Menyiapkan akses',
            steps: [
                'Periksa tab Permission untuk melihat izin yang tersedia dari modul.',
                'Pada Role, buat atau edit role sesuai tugas dan pilih izin yang memang diperlukan.',
                'Pada Akses Pengguna, tambah nama, email, password awal beserta konfirmasi dan role yang disepakati.',
            ],
        },
        {
            title: 'Memeriksa pengguna',
            steps: [
                'Sampaikan password awal secara pribadi dan minta pengguna masuk dengan akun sendiri.',
                'Edit nama/email atau role sesuai kebutuhan. Tutup shift kasir sebelum menonaktifkan akunnya.',
                'Uji menu dengan akun petugas, bukan dengan akun administrator yang memiliki akses lebih luas.',
            ],
        },
    ],
    notes: [
        'Super-system hanya untuk administrator tepercaya. Akun super-system aktif terakhir dilindungi sistem.',
        'Role baru tidak otomatis mendapat semua izin. Fitur ini bukan reset password admin atau hapus akun pengguna oleh admin.',
    ],
} satisfies PageGuideContent;
