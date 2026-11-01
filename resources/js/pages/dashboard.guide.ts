import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Dasbor',
    description: 'Baca ringkasan toko hari ini dan lanjutkan ke pekerjaan yang diperlukan.',
    sections: [
        {
            title: 'Peringatan stok minimum',
            steps: [
                'Bila memiliki izin stok minimum, periksa jumlah pasangan barang/gudang yang perlu diperiksa dan yang habis.',
                'Pilih Lihat peringatan untuk membuka daftar. Hanya pengaturan aktif dengan master aktif yang dihitung.',
                'Gunakan Muat ulang ringkasan setelah transaksi untuk membaca saldo terbaru. Peringatan tidak memblokir penjualan atau membuat pembelian.',
            ],
        },
        {
            title: 'Membaca ringkasan',
            steps: [
                'Periksa waktu pembaruan dan tanggal hari ini dalam WIB.',
                'Baca nilai penjualan, pembelian diterima serta pembayaran masuk dan keluar. Chart memakai angka ringkasan yang sama.',
                'Gunakan Muat ulang ringkasan jika perlu mengambil data terbaru.',
            ],
        },
        {
            title: 'Menelusuri transaksi',
            steps: [
                'Buka nomor pada Penjualan terbaru untuk menuju riwayat dan melihat bukti.',
                'Gunakan Laporan operasional untuk memilih periode atau gudang, atau Buka POS untuk melayani pembeli.',
            ],
        },
    ],
    notes: [
        'Arus pembayaran bukan saldo rekening atau laba. Nilai persediaan adalah posisi terkini.',
        'Ringkasan toko memerlukan izin laporan. Kasir hanya melihat penjualan miliknya kecuali diberi izin melihat semua.',
    ],
} satisfies PageGuideContent;
