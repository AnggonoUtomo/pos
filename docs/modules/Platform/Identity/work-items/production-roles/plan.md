# Rencana Role Produksi

1. Tulis dan jalankan test gagal terlebih dahulu: matriks, least privilege,
   preservasi pengguna/role lama/guard lain, idempotensi, missing permission,
   konflik matriks dan rollback bila audit gagal.
2. Tambahkan ProductionRolesSeeder pada Database/Seeders module Identity.
   Gunakan permission Spatie terdaftar dan transaksi/audit seperti pola existing.
3. Jalankan test terfokus Identity dan izin lintas module, satu gate backend
   lengkap jika terjangkau; tidak mengulang build frontend karena tidak berubah.
4. Terapkan pada MySQL produksi yang dikonfirmasi, verifikasi dua kali,
   bandingkan fingerprint pengguna/assignment/direct grant serta data bisnis.
5. Dokumentasikan matriks produksi aktual terpisah dari usulan panduan, catat
   hasil, commit scoped lalu push saat seluruh work item selesai.
