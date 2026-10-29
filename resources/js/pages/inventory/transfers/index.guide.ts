import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Transfer Antar-Gudang',
    description: 'Pindahkan barang antar lokasi tanpa mengubah jumlah total persediaan toko.',
    sections: [
        {
            title: 'Membuat draft',
            steps: [
                'Tambah draft, pilih gudang asal dan tujuan yang berbeda.',
                'Pilih barang, satuan dan jumlah yang akan dipindahkan, lalu simpan draft.',
            ],
        },
        {
            title: 'Memindahkan barang',
            steps: [
                'Cocokkan fisik dan stok asal dengan petugas kedua lokasi.',
                'Pilih Posting transfer sesuai izin ketika perpindahan benar-benar dilakukan.',
                'Periksa kartu stok: asal berkurang dan tujuan bertambah sekaligus dengan biaya asal tetap terjaga.',
            ],
        },
    ],
    notes: [
        'Draft belum memindahkan stok. Tahap ini memindahkan seluruh isi sekaligus, bukan stok transit atau penerimaan parsial.',
        'Posted tidak boleh diedit bebas. Jangan membuat transfer balik hanya untuk mencoba tombol.',
    ],
} satisfies PageGuideContent;
