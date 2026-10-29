import type { PageGuideContent } from './types';

export const fallbackGuide: PageGuideContent = {
    title: 'Panduan halaman',
    description: 'Langkah umum untuk bekerja dengan aman pada aplikasi POS.',
    sections: [
        {
            title: 'Mulai bekerja',
            steps: [
                'Pastikan halaman, akun dan gudang yang dipilih sesuai pekerjaan Anda.',
                'Gunakan pencarian atau filter untuk menemukan data sebelum membuat data baru.',
            ],
        },
        {
            title: 'Periksa hasil',
            steps: [
                'Baca pesan validasi, periksa isian, lalu tunggu konfirmasi hasil operasi.',
                'Jika hasil belum pasti, periksa riwayat atau hubungi supervisor sebelum mengulangi transaksi.',
            ],
        },
    ],
    notes: ['Tombol yang tersedia mengikuti izin akun dan status dokumen. Jangan meminjam akun pengguna lain.'],
};

export function guideKey(component: string): string {
    return `/resources/js/pages/${component}.guide.ts`;
}

export async function resolveGuide(component: string, loaders: Record<string, () => Promise<PageGuideContent>>): Promise<PageGuideContent> {
    const load = loaders[guideKey(component)];
    return load ? load() : fallbackGuide;
}
