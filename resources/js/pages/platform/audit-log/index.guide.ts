import type { PageGuideContent } from '@/components/shared/page-guide/types';

export default {
    title: 'Panduan Log Aktivitas',
    description: 'Menelusuri aktivitas dan perubahan data yang sudah dicatat aplikasi.',
    sections: [
        {
            title: 'Temukan aktivitas',
            steps: [
                'Isi kode/nomor dokumen, ID objek atau nama pengguna pada Pencarian.',
                'Pilih modul, pengguna, tindakan dan rentang tanggal sesuai kebutuhan. Tanggal mengikuti WIB.',
                'Klik Terapkan, lalu gunakan pagination atau jumlah baris untuk menelusuri hasil. Reset filter mengembalikan semua aktivitas.',
            ],
        },
        {
            title: 'Periksa detail',
            steps: [
                'Klik ikon mata pada aktivitas yang ingin diperiksa.',
                'Bandingkan kolom Sebelum dan Sesudah. Tidak dicatat berarti nilai itu tidak tersedia dalam log, bukan nilai kosong.',
                'Tutup detail setelah selesai. Log tidak dapat diedit atau dihapus melalui halaman ini.',
            ],
        },
    ],
    notes: [
        'Akses memerlukan izin Log Aktivitas. Hubungi administrator bila menu tidak muncul.',
        'Rincian sensitif dan field yang tidak diizinkan tidak ditampilkan. Log bukan bukti saldo stok atau kas; periksa kartu stok atau Buku Kas untuk ledger.',
        'Alt+F memfokuskan pencarian, Ctrl/Cmd+Enter menerapkan filter, Esc menutup detail, dan F1 membuka panduan.',
    ],
} satisfies PageGuideContent;
