import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Kas Dan Bank',
    description: 'Daftarkan rekening pencatatan uang yang dipakai metode bayar dan Buku Kas.',
    sections: [
        {
            title: 'Menyiapkan akun',
            steps: [
                'Cari akun yang sudah tersedia, lalu tambah kode dan nama yang jelas.',
                'Pilih jenis Kas untuk laci uang fisik atau Bank untuk rekening.',
                'Isi metadata bank bila diperlukan dan cocokkan dengan pemilik sebelum menyimpan sebagai aktif.',
            ],
        },
        {
            title: 'Menghubungkan operasional',
            steps: [
                'Di Metode pembayaran, hubungkan Tunai ke Kas serta Debit/Transfer ke Bank.',
                'Tetapkan saldo awal dan tanggal mulai di Buku Kas. Menambah master akun tidak membuat saldo uang.',
                'Untuk beberapa kasir bersamaan, siapkan akun laci dan metode Tunai terpisah.',
            ],
        },
    ],
    notes: [
        'Akun yang sudah dipakai tidak boleh dihapus/dinonaktifkan atau diganti jenisnya sembarangan.',
        'Ini rekening pencatatan, bukan koneksi langsung ke saldo bank atau instruksi transfer bank.',
    ],
} satisfies PageGuideContent;
