import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Shift Kasir',
    description: 'Buka laci, hitung uang saat tutup dan telusuri review selisih kasir.',
    sections: [
        {
            title: 'Membuka shift sendiri',
            steps: [
                'Pastikan akun Kas dan metode Tunai sesuai laci sudah aktif serta saldo awal Buku Kas sudah ditetapkan.',
                'Klik Buka shift, pilih laci, periksa modal dari Buku Kas lalu Mulai shift.',
                'Satu kasir dan satu laci hanya boleh memiliki satu shift aktif. Gunakan laci berbeda untuk kasir yang bekerja bersamaan.',
            ],
        },
        {
            title: 'Menutup shift',
            steps: [
                'Pastikan pembayaran sudah terkonfirmasi. Klik Shift aktif dan periksa saldo seharusnya.',
                'Hitung fisik, isi Uang fisik terhitung dan Alasan selisih bila berbeda.',
                'Tutup shift. Laci dilepas; selisih tetap menunggu review tanpa membuat mutasi Buku Kas otomatis.',
            ],
        },
        {
            title: 'Review supervisor',
            steps: [
                'Buka detail shift Menunggu review, cocokkan hitungan dan mutasi Buku Kas.',
                'Isi Catatan pemeriksaan lalu Sahkan review sesuai izin. Koreksi uang, jika perlu, dicatat terpisah setelah diperiksa.',
            ],
        },
    ],
    notes: [
        'Saldo seharusnya mencakup seluruh mutasi laci, termasuk refund/biaya/transfer, bukan hanya penjualan kasir.',
        'Kasir melihat miliknya, pemegang izin lihat semua dapat melihat seluruh kasir. Tidak ada force-close atau buka ulang; snapshot tutup tetap.',
        'Jika buka/tutup belum terkonfirmasi, gunakan Periksa ulang dengan permintaan yang sama.',
    ],
} satisfies PageGuideContent;
