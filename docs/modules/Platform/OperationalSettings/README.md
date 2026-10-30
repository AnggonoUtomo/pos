# Platform/OperationalSettings

Pengaturan operasional singleton untuk satu toko. Scope disetujui user:
format struk 58 mm/80 mm/A4 melalui dialog browser, backup otomatis opsional
dengan jam Asia/Jakarta, dan status backup terakhir. Bukan pengaturan company,
secret, database, jaringan, firewall, sertifikat, atau APP_ENV dari UI.

Tambahan disetujui user25Oktober2026: tombol **Backup sekarang** memakai
permission manage, konfirmasi, progress, Sonner dan audit requested/succeeded/
failed. File disimpan ke `base_path('backups')`, ignored Git dan bukan unduhan
web. UI hanya menampilkan basename aman/status/waktu manual terakhir. Manual
tidak mengisi sukses jadwal harian dan boleh dijalankan ulang. Command memiliki
lock bersama manual/scheduler/CLI selama dump/verify. Tidak ada restore dari UI.
Rencana/bukti: [backup manual](work-items/manual-backup/tasks.md).

Default: format A4 untuk mempertahankan cetak existing; backup otomatis mati,
jam awal 21:00 WIB. Semua opsi dapat disiapkan/disimpan saat backup nonaktif.
Scheduler Laravel netral OS; admin deployment memasang cron di Linux atau
pemicu noninteraktif yang sesuai di Windows. Aplikasi tidak memasang task,
service atau runner PowerShell otomatis pada komputer pengguna.
Printer fisik dipilih di dialog OS; USB/LAN/raw ESC/POS tidak diimplementasikan.

Update memakai version, permission view/manage terpisah, validasi backend dan
audit atomik. Singleton tidak dapat dihapus/soft-delete. Catatan eksekusi backup
immutable secara identitas, tanpa soft delete; status boleh berubah sesuai
siklus eksekusi. Secret dan output error mentah tidak disimpan/ditampilkan.

Public contract PrintingPreferences dipakai middleware Inertia untuk preferensi
cetak pengguna terautentikasi. Company/invoice snapshot tidak diubah; ukuran
kertas adalah preferensi perangkat operasional saat cetak, bukan isi invoice.

Backup memakai adapter command pos:backup existing, bukan engine dump baru.
Runner hanya mencoba jadwal hari WIB berjalan setelah jam yang dipilih;
terlewat beberapa hari tidak membuat dump historis palsu. Maksimal tiga
percobaan per hari, jeda sepuluh menit, satu eksekusi aktif dan timeout/lease.
Status terakhir adalah hasil runner, bukan klaim restore/off-device sudah aman.

Demo seeder dipertimbangkan: default aman saja, tidak membuat dump/jadwal aktif,
tidak mengubah grant dan tidak menimpa setting existing. Routes, migration dan
seeder tetap di module. Rencana: [work item](work-items/initial-settings/tasks.md).

## Preferensi Tabel Global

Tab vertikal Umum/Cetak/Backup menjaga opsi existing. Umum menambahkan
`table_per_page` default 10 dengan pilihan 10/25/50/100. Manage/view, audit dan
optimistic version tetap. Override query `per_page` hanya berlaku pada daftar,
tidak disimpan. `TablePreferences` membagikan default aman ke Inertia;
`TablePagination` memvalidasi ukuran request melalui adapter Infrastructure.
Repository daftar Inventory, Sales, Purchasing, Finance dan Identity memakai
contract publik tersebut. Operasional/laporan menjaga summary seluruh data,
bukan nilai halaman. Tidak berlaku pada editor/struk/katalog atau dashboard.
Scope/bukti: [pagination global](../../../work-items/global-table-pagination/README.md).
