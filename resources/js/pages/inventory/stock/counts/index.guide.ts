import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Stock Opname',
    description: 'Mulai sesi hitung fisik per gudang dan pantau pemeriksaan hasilnya.',
    sections: [
        {
            title: 'Sebelum mulai',
            steps: [
                'Selesaikan transaksi stok yang sedang berlangsung dan siapkan barang untuk dihitung.',
                'Pilih Mulai opname, pilih gudang dan isi catatan sesi.',
                'Periksa gudang lalu konfirmasi. Penjualan dan mutasi stok gudang dihentikan sampai sesi selesai atau dibatalkan. Gudang lain tetap berjalan.',
            ],
        },
        {
            title: 'Melanjutkan sesi',
            steps: [
                'Cari nomor sesi atau pilih gudang/status.',
                'Pilih Buka sesi untuk mengisi hasil hitung atau memeriksa selisih.',
                'Gunakan pilihan jumlah baris dan tombol halaman untuk menelusuri daftar sesi.',
            ],
        },
    ],
    notes: [
        'Hanya satu sesi aktif untuk setiap gudang.',
        'Memulai sesi tidak mengubah saldo atau membuat koreksi stok.',
        'Mulai opname hanya ketika petugas sudah siap.',
    ],
} satisfies PageGuideContent;
