import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan POS',
    description: 'Layani pembeli dari scan barang sampai pembayaran lunas dan bukti penjualan.',
    sections: [
        {
            title: 'Sebelum melayani',
            steps: [
                'Masuk dengan akun kasir sendiri. Klik Buka shift, pilih laci yang sudah memiliki saldo awal Buku Kas, lalu Mulai shift.',
                'Periksa gudang dan pelanggan. Default Gudang Utama/Eceran tetap perlu dicocokkan dengan pembeli dan lokasi sebenarnya.',
            ],
        },
        {
            title: 'Barang dan pembayaran',
            steps: [
                'Scan barcode lalu Enter atau cari barang. Cocokkan satuan dan jumlah; barcode Dus berbeda dari Unit.',
                'Pilih pelanggan melalui autocomplete. Harga mengikuti satuan dan level; isi diskon/pajak bila berlaku.',
                'Klik Periksa keranjang. Jika valid, buka Pembayaran dan isi metode/nominal sesuai uang diterima.',
                'Klik Bayar dan posting sekali, tunggu bukti, periksa kembalian lalu cetak melalui browser.',
            ],
        },
        {
            title: 'Menunda dan mengakhiri',
            steps: [
                'Gunakan Tahan untuk menyimpan keranjang dan Tertahan untuk memanggil milik sendiri. Harga/stok diperiksa ulang dan tidak direservasi.',
                'Saat selesai bekerja, klik Shift aktif, hitung uang fisik dan tutup dengan alasan bila selisih. Supervisor mereview tanpa koreksi uang otomatis.',
            ],
        },
    ],
    notes: [
        'Klasik dan Visual memakai operasi yang sama. Semua checkout baru wajib shift, termasuk seluruh pembayaran non-tunai.',
        'Jika hasil pembayaran belum pasti, gunakan pemulihan yang sama dan periksa riwayat; jangan membuat penjualan atau menerima uang dua kali.',
        'Stok tidak boleh minus. Maksimal satu baris Tunai; hanya Tunai menghasilkan kembalian. Reload bukan penyimpanan keranjang: gunakan Tahan.',
    ],
} satisfies PageGuideContent;
