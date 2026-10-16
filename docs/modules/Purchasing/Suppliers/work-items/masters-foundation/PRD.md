# PRD: Master Supplier

Admin pembelian toko CCTV/kelistrikan membutuhkan master pemasok sebelum alur
pembelian/penerimaan dibuat. Scope disetujui user 17 Oktober 2026.

Kebutuhan: kode/nama wajib, telepon/email/alamat opsional, status aktif/nonaktif,
daftar/search/filter/pagination, tambah/edit/hapus lunak/restore, permission,
audit dan demo fiktif relevan. Operasi harus dapat diverifikasi desktop/tablet.
Mutasi berizin di backend dan audit tidak menyimpan nilai kontak.

Sukses: master tersimpan/reload dan dapat dipulihkan tanpa duplikasi kode atau
audit parsial; viewer readonly, outsider ditolak. Pembelian, stok masuk/FIFO,
utang, pembayaran supplier dan retur bukan bagian pekerjaan ini.
