# Platform/OperationalSettings

Pengaturan operasional singleton untuk satu toko. Scope disetujui user:
format struk 58 mm/80 mm/A4 melalui dialog browser, backup otomatis opsional
dengan jam Asia/Jakarta, dan status backup terakhir. Bukan pengaturan company,
secret, database, jaringan, firewall, sertifikat, atau APP_ENV dari UI.

Default: format A4 untuk mempertahankan cetak existing; backup otomatis mati,
jam awal 21:00 WIB. Mengaktifkan jadwal memerlukan runner scheduler Windows.
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
