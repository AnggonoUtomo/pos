# Role Produksi

User meminta profil panduan menjadi role database siap dipakai dengan permission
masing-masing. Target hanya Platform/Identity; tidak mengubah UI atau aturan bisnis.

Disepakati: supervisor boleh mengelola stok awal/koreksi, posting transfer dan
retur barang, serta Terima dan bayar pembelian; refund hanya keuangan. Supervisor
juga mengelola draft retur penjualan agar alur retur tidak berhenti pada super-system.
Role lain mengikuti paket minimal panduan. Tidak membuat akun/password,
mengganti assignment user, menghapus role lama atau mengubah direct permission.

Seeder module khusus produksi, tanpa demo: tujuh role guard web. Role existing
dengan matriks berbeda harus menyebabkan penolakan atomic, bukan penimpaan.
Role super-system existing dipertahankan bersama seluruh permission/pivotnya;
otorisasi tetap melalui Gate::before existing.

Database awal: production/MySQL, role web super-system dan test. Enam nama role
operasional belum ada. Kredensial dan identitas akun tidak dicatat di dokumen.

## Risiko Dan Pengaman

- Role baru belum aktif untuk pengguna sampai admin melakukan assignment.
- Izin gabungan menerima/membayar pembelian hanya diberikan pada supervisor.
- Kasir tidak melihat seluruh invoice atau melakukan refund/koreksi stok.
- Tidak menambah permission key atau men-seed demo. Katalog module harus tersedia.
- Semua pembuatan role/audit atomic; cache permission dibersihkan setelahnya.
- Ulang seeder tidak menghapus perubahan manual: matriks berbeda dilaporkan konflik.
- Tidak menjanjikan pembatasan user per gudang atau approval workflow baru.
