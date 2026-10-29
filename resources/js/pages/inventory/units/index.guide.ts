import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Satuan',
    description: 'Siapkan nama satuan seperti Unit, Dus, Meter dan Rol sebelum membuat barang.',
    sections: [
        {
            title: 'Menambah satuan',
            steps: [
                'Cari kode atau nama terlebih dahulu agar tidak membuat satuan ganda.',
                'Klik Tambah satuan, isi kode, nama dan simbol sesuai penggunaan toko.',
                'Simpan dan pastikan status aktif sebelum dipilih pada barang.',
            ],
        },
        {
            title: 'Mengubah atau memulihkan',
            steps: [
                'Gunakan ikon edit untuk memperbaiki data yang diizinkan.',
                'Gunakan tab Terhapus untuk mencari dan memulihkan satuan yang dihapus sementara.',
            ],
        },
    ],
    notes: [
        'Isi Dus atau Rol ditetapkan per barang, bukan di halaman ini. Contoh 1 Rol dapat berisi 100 Meter pada kabel tertentu.',
        'Satuan yang sudah digunakan barang tidak boleh dihapus atau dinonaktifkan. Mengganti nama satuan bukan cara mengubah isi kemasan.',
    ],
} satisfies PageGuideContent;
