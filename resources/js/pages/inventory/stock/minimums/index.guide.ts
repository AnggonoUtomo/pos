import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Stok Minimum',
    description: 'Tentukan batas barang per gudang dan periksa stok yang perlu ditambah.',
    sections: [
        {
            title: 'Menentukan batas',
            steps: [
                'Pilih Tambah batas, tentukan gudang dan cari barang.',
                'Isi minimum dalam satuan dasar yang ditampilkan. Gunakan koma untuk pecahan, misalnya 2,5 meter.',
                'Centang Peringatan aktif lalu Simpan. Minimum 0 boleh dipakai untuk memantau barang habis.',
            ],
        },
        {
            title: 'Memeriksa persediaan',
            steps: [
                'Pilih status Perlu diperiksa atau buka Lihat peringatan dari dasbor.',
                'Saldo sama dengan atau di bawah minimum masuk peringatan. Selisih ke batas bukan jumlah pembelian yang wajib.',
                'Cari SKU/nama/gudang; gunakan filter gudang dan pilihan jumlah baris bila diperlukan.',
            ],
        },
        {
            title: 'Mengubah pengaturan',
            steps: [
                'Pilih ikon pensil untuk mengubah batas atau menonaktifkan peringatan.',
                'Hapus pengaturan bila tidak dipakai. Untuk mengembalikannya, pilih filter Terhapus lalu Pulihkan.',
                'Aktifkan kembali barang, gudang dan satuan yang tidak aktif sebelum mengedit atau memulihkan pengaturannya.',
            ],
        },
    ],
    notes: [
        'Peringatan tidak menahan penjualan dan tidak membuat pembelian otomatis.',
        'Barang/gudang yang belum diatur tidak menghasilkan peringatan.',
        'Saldo yang belum ada dihitung nol. Setiap gudang memiliki batas sendiri.',
        'Mengubah batas tidak mengubah stok, FIFO atau uang.',
    ],
} satisfies PageGuideContent;
