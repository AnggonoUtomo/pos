# Module Sales/Parties

Status: In Progress. Master pelanggan dan level pelanggan untuk toko CCTV dan
kelistrikan. Scope disetujui user 12 Oktober 2026.

CRUD level/pelanggan, status aktif, soft delete/restore, permission backend,
audit, demo relasional dan UI admin desktop/tablet. Level demo Eceran, Grosir,
Installer. Kontak/alamat opsional, level aktif wajib saat create/edit/restore.

Non-scope: pricing per barang/satuan, transaksi POS, supplier, kredit limit,
finance, nomor seri dan garansi. Tidak membuat contract/event lintas module
sebelum ada consumer. Tidak mengubah theme atau Old-docs.

Composition root: app/Modules/Sales/Parties/ServiceProvider.php.
Routes, migration dan seeder dimiliki module. Controller memakai Application;
repository mengisolasi Eloquent. Data dan audit commit/rollback bersama.

Demo hanya local/testing, menjaga edit/nonaktif/trashed dan tidak membuat akun,
role atau grant. Permission provisioning terpisah dari demo. Informasi kontak
pelanggan tidak disalin ke activity log.
