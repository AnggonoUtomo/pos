import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Buku Kas',
    description: 'Baca saldo per akun serta catat biaya, pemasukan operasional dan transfer internal.',
    sections: [
        {
            title: 'Saldo awal satu kali',
            steps: [
                'Bersama penanggung jawab, tentukan akun, tanggal mulai dan posisi uang yang sebenarnya.',
                'Klik Saldo awal dan isi nominal Indonesia. Nilai ini posisi sebelum transaksi pukul 00.00 WIB tanggal mulai.',
                'Periksa lalu Tetapkan saldo awal. Transaksi sebelumnya tetap histori, tidak ikut saldo berjalan.',
            ],
        },
        {
            title: 'Saldo dan mutasi',
            steps: [
                'Pilih akun/periode lalu Tampilkan. Baca saldo awal periode, masuk, keluar dan saldo akhir.',
                'Catat mutasi untuk pemasukan/pengeluaran operasional, atau Transfer antar-akun dengan asal dan tujuan berbeda.',
                'Jika mutasi manual salah, gunakan Balik mutasi dengan alasan; sumber tetap tersimpan.',
            ],
        },
        {
            title: 'Koneksi terputus',
            steps: [
                'Jika hasil belum terkonfirmasi, gunakan Ulangi pengiriman yang sama. Jangan membuat mutasi baru yang sama atau membersihkan penyimpanan browser.',
            ],
        },
    ],
    notes: [
        'Pembayaran penjualan/pembelian/refund masuk otomatis dari modul asal. Jangan dicatat lagi sebagai mutasi manual.',
        'Saldo minus hanya peringatan. Saldo awal tidak bisa diedit/dihapus bebas; modal Shift Kasir mengambil saldo ini tanpa pemasukan baru.',
    ],
} satisfies PageGuideContent;
