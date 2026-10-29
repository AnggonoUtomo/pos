import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Supplier',
    description: 'Siapkan data pemasok CCTV dan kelistrikan sebelum membuat pembelian.',
    sections: [
        {
            title: 'Menambah pemasok',
            steps: [
                'Cari kode atau nama agar satu pemasok tidak didaftarkan berkali-kali.',
                'Tambah supplier dengan kode/nama serta kontak/alamat bila diperlukan.',
                'Simpan sebagai aktif dan cocokkan penulisan dengan dokumen pemasok.',
            ],
        },
        {
            title: 'Menggunakan master',
            steps: [
                'Pilih supplier yang benar pada Order Pembelian.',
                'Edit, nonaktifkan, hapus atau pulihkan melalui operasi yang tersedia sesuai kewenangan.',
            ],
        },
    ],
    notes: [
        'Master supplier belum berarti ada utang atau pembayaran. Transaksi dicatat melalui pembelian dan retur.',
        'Jangan menghapus referensi transaksi atau mengubah database untuk melewati validasi master.',
    ],
} satisfies PageGuideContent;
