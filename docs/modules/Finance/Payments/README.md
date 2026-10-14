# Finance/Payments

Status: In Progress. Scope fondasi master disetujui 15 Oktober 2026.
Memiliki master kas/bank dan metode pembayaran relasional sebagai prasyarat
pembayaran POS berikutnya. Belum memiliki saldo/mutasi atau payment record.

Satu metode menunjuk satu akun aktif; beberapa metode boleh memakai akun sama.
Tunai memakai akun cash; Debit/Transfer memakai bank. Akun terpakai, termasuk
oleh metode terhapus/nonaktif, tidak boleh dihapus/nonaktif atau diganti jenis.
Metadata bank opsional, tidak disalin ke audit. Kode unik tetap direservasi.

Route/migration/seeder di app/Modules/Finance/Payments. Backend permission
finance.payments.view/manage, tanpa auto-grant. Super-system bypass permission
saja. CRUD/restore master memakai soft delete dan audit atomic. Tidak membuat
public payment contract/event sebelum ada consumer transaksi nyata.

UI admin: /finance/payment-accounts dan /finance/payment-methods, shadcn/Sonner,
shortcut/palette dan desktop/tablet. Theme dan POS payment tidak diubah.
Demo hanya local/testing: Kas Toko Demo, Bank Operasional Demo, Tunai/Debit/
Transfer. Tidak menimpa master existing, tidak membuat akun login/grant/saldo.

Detail [specification](specification.md), [plan](plan.md), [tasks](tasks.md).
