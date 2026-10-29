import type { PageGuideContent } from '@/components/shared/page-guide/types';
export default {
    title: 'Panduan Pengaturan Operasional',
    description: 'Atur format struk, jalankan backup manual dan pilih jadwal backup opsional.',
    sections: [
        {
            title: 'Cetak',
            steps: [
                'Pilih A4, 58 mm atau 80 mm sesuai kertas, lalu simpan pengaturan.',
                'Gunakan Uji cetak dan pilih printer atau Simpan sebagai PDF melalui dialog browser.',
                'Cocokkan ukuran kertas, skala, margin dan hasil fisik dengan administrator.',
            ],
        },
        {
            title: 'Backup manual dan jadwal',
            steps: [
                'Klik Backup sekarang dan konfirmasi Buat backup; tunggu status berhasil.',
                'Administrator memeriksa file pada folder backups project. File backup bukan unduhan publik.',
                'Aktifkan opsi harian jika dibutuhkan, tentukan jam WIB dan simpan. Periksa hasil terakhir serta status pemicu jadwal.',
            ],
        },
    ],
    notes: [
        'Menyimpan jadwal tidak memasang cron/service. Administrator menyiapkan pemicu Laravel Scheduler sesuai OS server.',
        'Salinan di PC yang sama belum melindungi dari kerusakan PC. Pengujian printer fisik dan pemulihan backup tetap perlu prosedur admin.',
    ],
} satisfies PageGuideContent;
