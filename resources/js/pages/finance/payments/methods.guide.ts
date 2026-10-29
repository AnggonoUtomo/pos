import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Metode Pembayaran',
    description: 'Tentukan cara bayar dan akun uang yang benar untuk setiap metode.',
    sections: [
        {
            title: 'Membuat metode',
            steps: [
                'Pastikan akun tujuan sudah aktif pada Kas dan bank.',
                'Tambah kode/nama metode, pilih jenis Tunai, Debit atau Transfer.',
                'Hubungkan Tunai ke akun Kas, Debit/Transfer ke akun Bank; periksa lalu simpan aktif.',
            ],
        },
        {
            title: 'Memeriksa penggunaan',
            steps: [
                'Pada transaksi, pilih metode sesuai uang yang benar-benar diterima atau dibayar.',
                'Di POS, metode Tunai harus mengarah ke laci shift kasir; non-tunai tetap memakai akun bank.',
                'Cocokkan riwayat pembayaran dan Buku Kas jika pencatatan uang terlihat berbeda.',
            ],
        },
    ],
    notes: [
        'Mengganti metode pada master tidak mengubah snapshot pembayaran transaksi lama.',
        'Satu penjualan boleh multi-payment. Kembalian hanya berasal dari maksimal satu baris Tunai.',
    ],
} satisfies PageGuideContent;
