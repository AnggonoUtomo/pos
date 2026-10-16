# PRD: Draft Pembelian

Admin pembelian memerlukan dokumen rencana barang dari supplier sebelum tahap
penerimaan dibuat. Memilih satu supplier/gudang, barang dan satuan pembelian,
kuantitas dan harga per satuan; melihat total; menyimpan dan mengoreksi draft.
User menetapkan biaya positif sejak draft disimpan (17 Oktober 2026).

Sukses: CRUD/detail/filter dapat dipakai; 2 dus*Rp120.000=Rp240.000 dengan
konversi authoritative; format Indonesia; perubahan stale tidak menimpa data;
permission/CSRF/audit wajib; saldo stok/FIFO/finance tidak berubah.
Tidak ada kesan draft sudah menerima barang atau membayar supplier.

Acceptance dan verifikasi rinci di [tasks](tasks.md); contract/data pada
[spesifikasi module](../../specification.md). Tidak ada dependency atau stack baru.
