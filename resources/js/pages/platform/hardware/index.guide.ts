import type { PageGuideContent } from '@/components/shared/page-guide/types';

const guide: PageGuideContent = {
    title: 'Panduan Perangkat POS',
    description: 'Menyiapkan printer label, memeriksa kode dan meninjau pengiriman gateway.',
    sections: [
        { title: 'Siapkan printer', steps: [
            'Admin memasang QZ Tray dan driver printer secara manual pada PC tujuan. Untuk tablet, admin juga menyiapkan gateway LAN dengan WSS dan sertifikat yang dipercaya.',
            'Buka tab Printer, pilih gateway yang diizinkan lalu Cari printer. Pilih nama printer OS yang benar saat menambah profil; jangan menganggap printer pertama adalah CT420D.',
            'Simpan nama profil, ukuran label dan orientasi. Profil tersimpan dan gateway terhubung belum membuktikan kertas atau hasil cetak siap.',
        ] },
        { title: 'Label dan riwayat', steps: [
            'Buka Label, cari barang/satuan berbarcode, pilih profil dan jumlah label. Periksa pratinjau sebelum mengirim.',
            'Saat pencetakan perangkat diaktifkan oleh admin setelah persetujuan UAT, Kirim label membuat job dan menyerahkannya ke gateway satu kali. Konfirmasi izin QZ tetap dapat muncul.',
            'Diterima gateway bukan bukti fisik tercetak. Jika hasil belum pasti, periksa printer terlebih dahulu; cetak ulang memerlukan alasan dan membuat job baru yang ditautkan.',
            'Job yang belum dikirim dapat dibatalkan oleh pembuatnya pada Riwayat. Hasil cetak tidak mengubah transaksi, stok atau pembayaran.',
        ] },
        { title: 'Tes scanner', steps: [
            'Pada tab Scanner, fokuskan input lalu scan kode dengan akhiran Enter. Nol awal tetap dipertahankan.',
            'Kode yang diterima dibandingkan dengan barcode barang yang dipilih pada Label. Ketikan manual juga dapat masuk; merek atau koneksi scanner tidak disimpulkan otomatis.',
        ] },
    ],
    notes: ['CT420D adalah printer label, bukan struk ESC/POS. Jalur ini memakai gambar melalui driver OS; driver/media dan scan-back fisik tetap perlu diuji.',
        'Aplikasi tidak memasang driver, service, sertifikat atau pairing Bluetooth otomatis. Jangan melewati warning TLS.',
        'Alt+1 sampai Alt+4 memilih tab, Alt+F fokus pencarian riwayat, dan F1 membuka panduan. Tidak ada auto-print sesudah checkout.'],
};
export default guide;
