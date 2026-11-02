import type { PageGuideContent } from '@/components/shared/page-guide/types';

export default {
    title: 'Panduan Kategori Barang',
    description: 'Kelompokkan barang agar lebih mudah ditemukan.',
    sections: [
        {
            title: 'Menyiapkan kategori',
            steps: [
                'Klik Tambah kategori barang, isi kode unik dan nama, lalu Simpan. Kategori hanya satu tingkat.',
                'Gunakan Cari atau filter status untuk menemukan kategori. Edit untuk memperbaiki nama atau status.',
            ],
        },
        {
            title: 'Memakai pada barang',
            steps: [
                'Buka Barang, edit barang dan cari kategori melalui autocomplete. Pilih hasil lalu Simpan barang.',
                'Kategori opsional; Tanpa kategori mengosongkan pilihan. Pilihan ini tidak mengubah harga atau stok.',
            ],
        },
        {
            title: 'Hapus dan pulihkan',
            steps: [
                'Kategori yang belum dipakai dapat dihapus dan dipulihkan melalui tab Terhapus.',
                'Kategori yang masih dirujuk barang, termasuk barang terhapus, tidak boleh dihapus atau dinonaktifkan. Lepaskan referensinya melalui edit barang terlebih dahulu bila memang diperlukan.',
            ],
        },
    ],
    notes: [
        'Kode tetap direservasi setelah dihapus. Tidak perlu membuat kategori per gudang atau level pelanggan.',
        'Ctrl/Cmd+Shift+N menambah; Ctrl/Cmd+Enter menyimpan form; Alt+F mencari; Esc menutup dialog. Operasi mengikuti izin akun.',
    ],
} satisfies PageGuideContent;
