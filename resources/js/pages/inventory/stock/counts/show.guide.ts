import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Hasil Stock Opname',
    description: 'Hitung fisik, simpan hasil, lalu minta supervisor mengesahkan selisih.',
    sections: [
        {
            title: 'Mencari pilihan',
            steps: ['Ketik SKU/nama barang pada pilihan tambah baris, lalu pilih barang yang sesuai.'],
        },
        {
            title: 'Menghitung barang',
            steps: [
                'Isi Fisik dalam satuan dasar. Contoh 2 dus isi 12 berarti 24 unit.',
                'Gunakan angka Indonesia: 1, 12 atau 1,5. Kosong berarti belum dihitung; isi 0 bila fisik kosong.',
                'Scan barcode atau masukkan SKU untuk menemukan barang. Scan tidak otomatis menambah kuantitas. Tambahkan barang yang belum tercantum.',
                'Simpan hitung sebelum berpindah atau menambah barang. Ajukan review setelah seluruh baris terisi.',
            ],
        },
        {
            title: 'Pemeriksaan supervisor',
            steps: [
                'Bandingkan sistem, fisik dan selisih tersimpan.',
                'Untuk setiap selisih tambah, isi harga pokok positif per satuan dasar, bukan harga jual atau harga per dus.',
                'Pilih Hitung ulang dengan alasan jika perlu diperbaiki. Gudang tetap ditahan.',
                'Jika benar, Posting hasil dengan alasan. Selisih kurang memakai FIFO; selisih nol tidak membuat mutasi.',
            ],
        },
        {
            title: 'Selesai atau batal',
            steps: [
                'Setelah posting berhasil, saldo sesuai fisik dan gudang dapat bertransaksi kembali.',
                'Jika tidak dilanjutkan, Batalkan sesi dengan alasan. Tidak ada koreksi stok; jejak sesi tetap tersimpan.',
                'Jika sesi berubah di tab lain, muat ulang sebelum mengulang operasi.',
            ],
        },
    ],
    notes: [
        'Posted tidak dapat diedit atau dihapus; koreksi berikutnya melalui operasi audited.',
        'Posting ulang hasil yang sama tidak menggandakan stok.',
        'Pembayaran dokumen lama yang tidak mengubah stok tetap tersedia.',
    ],
} satisfies PageGuideContent;
