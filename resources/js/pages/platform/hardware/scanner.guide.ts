import type { PageGuideContent } from '@/components/shared/page-guide/types';

const guide: PageGuideContent = {
    title: 'Panduan uji scanner',
    description: 'Memeriksa kode dan satuan jual tanpa membuat transaksi atau mengubah stok.',
    sections: [
        {
            title: 'Periksa kode',
            steps: [
                'Pilih input Scan barcode, atau gunakan Alt+S untuk memindahkan fokus ke input tersebut.',
                'Ketik barcode satuan jual atau SKU barang aktif, lalu tekan Enter. Scanner mode keyboard juga memakai input ini; atur akhiran Enter pada perangkat. Nol awal kode tetap dipertahankan.',
                'Periksa nama barang, SKU dan satuan yang dikenali. Dua pemindaian kode yang sama menghasilkan dua hasil uji, bukan tambahan stok.',
            ],
        },
        {
            title: 'Tinjau hasil uji',
            steps: [
                'Jika kode tidak ditemukan, periksa master barang dan status satuan jual. Satuan khusus pembelian tidak tersedia pada uji ini.',
                'Antrean dibatasi 20 uji. Tunggu hasil jika penuh. Uji yang habis waktu tidak dikirim ulang otomatis.',
                'Gunakan Bersihkan hasil atau Alt+R dan konfirmasikan untuk membatalkan antrean serta menghapus hasil sementara. Menutup halaman juga membuang hasil uji.',
            ],
        },
    ],
    notes: [
        'Halaman tersedia jika admin mengaktifkan fitur, termasuk pada produksi. Login dan hak inventory.view tetap wajib.',
        'Uji virtual tidak membuktikan scanner HC-4208DB, Bluetooth, USB atau hasil cetak CT420D bekerja. UAT fisik tetap menunggu perangkat.',
        'Halaman ini tidak mengubah harga, stok, keranjang, penjualan, pembelian atau pembayaran dan tidak mengirim perintah printer.',
    ],
};

export default guide;
