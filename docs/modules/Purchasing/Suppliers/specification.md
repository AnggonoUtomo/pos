# Spesifikasi Master Supplier

## Data Dan Invariant

Tabel suppliers: id ULID, code max32 unik termasuk trashed, name max150,
phone nullable max32, email nullable valid max150, address nullable max500,
is_active boolean default true, timestamps dan deleted_at. Code trim/uppercase,
nama/kontak trim, kontak kosong null. Aktif/nonaktif diedit dari form.
Soft delete mempertahankan identitas; restore tidak memaksa aktif. Tidak hard delete.

Mutation melalui Application/Actions dan port internal SuppliersRepository;
adapter Eloquent berada di Infrastructure. Satu transaction untuk mutation/audit,
row lock update/delete/restore dan retry deadlock terbatas. No-op tanpa audit.
Audit purchasing.suppliers: created/updated/deleted/restored, subject ULID dan
actor bila authenticated. Hanya code/name/status/deleted_at; kontak dicatat nama
field yang berubah saja, bukan nilainya. Audit gagal/veto membatalkan mutation.

## HTTP Dan Permission

Auth session/CSRF. GET /purchasing/suppliers dan /data memakai
purchasing.suppliers.view. POST create, PUT /{id}, DELETE /{id} dan
POST /{id}/restore memakai purchasing.suppliers.manage melalui HasMiddleware.
Super-system bypass permission saja; validasi tetap wajib. ULID route canonical,
ID invalid/tidak tersedia404. Create JSON201, update/delete/restore200;
Inertia mutation redirect back dengan Sonner. Duplicate/invalid422.

Index/data: search code/name literal max150, status all/active/inactive,
trashed boolean, page positif. Pagination25, order code/id. Daftar kontak hanya
untuk role view; manage tidak otomatis memberi view. Hanya field payload yang
diizinkan dipersist; ID/deleted_at/actor dari server, tidak dari request.

## UI, Demo Dan Batas

Admin shell existing, komponen lokal Suppliers, shadcn/Lucide/Sonner. Filter URL,
loading/error/empty, dialog tambah/edit, konfirmasi hapus/restore, shortcut dropdown
di atas workspace dan command palette. Desktop/tablet Light/Dark; theme tidak diubah.
Shortcut Alt+F cari, Ctrl/Cmd+Shift+N tambah, Ctrl/Cmd+Enter simpan, Esc tutup.

Seeder akses hanya katalog dua permission tanpa grant/reset. Demo local/testing:
distributor CCTV, kelistrikan dan kabel fiktif lewat Application, idempotent,
skip kode existing termasuk nonaktif/trashed/user-edit. Tidak membuat login,
transaksi atau saldo. Aktivasi MySQL lokal additive dengan fingerprint existing,
tanpa migrate:fresh, reset/grant atau update data lama; demo tiga supplier baru
boleh ditambahkan dan diaudit. SupplierLookup publik kini dikonsumsi draft
PurchaseOrders: findActive(id, lock=false) dan choices() maksimum1000, DTO
SupplierLookupData id/code/name tanpa kontak. Lock true wajib transaction
default dan memakai current read. Draft tidak melarang nonaktif/soft delete
supplier; snapshot draft tetap tersedia, simpan ulang memvalidasi master aktif.
Guard histori belum diperlukan sebelum posted purchase nyata tersedia.
