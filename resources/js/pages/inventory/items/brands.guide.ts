import type { PageGuideContent } from '@/components/shared/page-guide/types';

export default {
    title: 'Panduan Merek Barang',
    description: 'Kelola merek produk tanpa mengubah transaksi yang sudah berjalan.',
    sections: [
        {
            title: 'Menyiapkan merek',
            steps: [
                'Klik Tambah merek barang, isi kode unik dan nama merek lalu Simpan.',
                'Cari berdasarkan kode/nama; gunakan filter Aktif/Nonaktif atau Terhapus sesuai kebutuhan.',
            ],
        },
        {
            title: 'Memakai pada barang',
            steps: [
                'Buka Barang, edit barang dan cari merek melalui autocomplete. Pilih hasil lalu Simpan barang.',
                'Merek opsional; barang tanpa merek tetap dapat dijual. Setiap barang hanya memiliki satu merek.',
            ],
        },
        {
            title: 'Menjaga referensi',
            steps: [
                'Merek yang belum dipakai dapat dinonaktifkan atau dihapus, lalu dipulihkan dari Terhapus.',
                'Merek yang masih dirujuk barang, termasuk barang terhapus, tidak boleh dihapus atau dinonaktifkan. Nama tetap dapat diperbaiki.',
            ],
        },
    ],
    notes: [
        'Kode tidak bisa dipakai ulang sesudah dihapus. Merek bukan supplier, kategori atau level harga.',
        'Ctrl/Cmd+Shift+N menambah; Ctrl/Cmd+Enter menyimpan; Alt+F mencari; Esc menutup. Operasi mengikuti izin akun.',
    ],
} satisfies PageGuideContent;
