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
            title: 'Sebelum menghapus akun',
            steps: [
                'Pastikan pekerjaan serta shift kasir sudah ditutup dan koordinasikan dengan administrator.',
                'Gunakan konfirmasi password hanya bila penghapusan benar-benar dimaksudkan.',
            ],
        },
    ],
    notes: [
        'Super-system aktif terakhir dan akun dengan shift aktif tidak boleh dihapus.',
        'Penghapusan akun bukan cara menghapus riwayat transaksi atau meminta penggantian role.',
    ],
} satisfies PageGuideContent;
