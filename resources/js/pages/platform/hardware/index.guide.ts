import type { PageGuideContent } from '@/components/shared/page-guide/types';

const guide: PageGuideContent = {
    title: 'Panduan Perangkat POS',
    description: 'Menyiapkan printer label, memeriksa kode dan meninjau pengiriman gateway.',
    sections: [
        { title: 'Siapkan printer', steps: [
            'Siapkan QZ Tray sekali pada PC tujuan melalui installer resmi atau script setup admin Windows. Driver printer dipasang terpisah; tablet memerlukan gateway LAN WSS dan trust yang disiapkan admin.',
            'Saat membuka Perangkat POS, gateway diperiksa sekali bagi pemegang izin manage/print. Gunakan Deteksi ulang setelah driver/perangkat baru dipasang. Pilih nama printer OS yang benar ketika menambah profil, bukan printer pertama otomatis.',
            'Simpan nama profil, ukuran label dan orientasi. Profil tersimpan dan gateway terhubung belum membuktikan kertas atau hasil cetak siap.',
        ] },
        { title: 'Label dan riwayat', steps: [
            'Buka Label, cari barang/satuan berbarcode, pilih profil dan jumlah label. Periksa pratinjau sebelum mengirim.',
            'Saat pencetakan perangkat diaktifkan oleh admin setelah persetujuan UAT, Kirim label membuat job dan menyerahkannya ke gateway satu kali. Konfirmasi izin QZ tetap dapat muncul.',
            'Diterima gateway bukan bukti fisik tercetak. Jika hasil belum pasti, periksa printer terlebih dahulu; cetak ulang memerlukan alasan dan membuat job baru yang ditautkan.',
            'Job yang belum dikirim dapat dibatalkan oleh pembuatnya pada Riwayat. Hasil cetak tidak mengubah transaksi, stok atau pembayaran.',
        ] },
        { title: 'Tes scanner', steps: [
            'Pada tab Scanner, input otomatis fokus. Scanner mode keyboard langsung dapat mengirim kode diikuti Enter tanpa memilih perangkat atau menghubungkan QZ. Nol awal tetap dipertahankan.',
            'Status Belum diuji menjadi Input diterima setelah kode masuk. Ini status sesi input, bukan deteksi merek atau bukti hardware tersambung.',
            'Kode yang diterima dibandingkan dengan barcode barang yang dipilih pada Label. Ketikan manual juga dapat masuk; merek atau koneksi scanner tidak disimpulkan otomatis.',
        ] },
    ],
    notes: ['CT420D adalah printer label, bukan struk ESC/POS. Jalur ini memakai gambar melalui driver OS; driver/media dan scan-back fisik tetap perlu diuji.',
        'Web POS tidak memasang driver, service atau pairing Bluetooth. Installer QZ menyiapkan trust TLS lokal/startup bawaannya satu kali; prompt izin QZ tetap dapat muncul. Jangan melewati warning TLS.',
        'Alt+1 sampai Alt+4 memilih tab, Alt+F fokus pencarian riwayat, dan F1 membuka panduan. Tidak ada auto-print sesudah checkout.'],
};
export default guide;
