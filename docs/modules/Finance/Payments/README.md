# Finance/Payments

Status: Active. Fondasi master selesai/diverifikasi 15 Oktober 2026.
Memiliki master kas/bank dan metode pembayaran relasional sebagai prasyarat
pembayaran POS berikutnya. Penerimaan internal penjualan tersedia sejak
Increment 3 checkout (16 Oktober 2026); belum memiliki running balance atau UI checkout.

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

Migration dan demo sudah diterapkan pada MySQL local tanpa reset data.
QA mencakup feature tests, dua koneksi MySQL dan browser desktop/tablet melalui
fallback Playwright existing; DevTools MCP tidak tersedia pada sesi pengerjaan.
Detail bukti dan delivery pada work item, bukan klaim bahwa Finance penuh selesai.

Detail [specification](specification.md), [plan](plan.md), [tasks](tasks.md).

Pekerjaan lanjutan [checkout lunas](../../../work-items/pos-paid-checkout/README.md):
Increment 1..3 selesai: PaymentSettlement serta RecordSalePayment internal,
payment_receipts/payment_records immutable, snapshot dan net cash, audit wajib,
replay canonical dan guard histori master. Metode berhistori tidak boleh hapus/
nonaktif/ganti type/pindah akun; rename tetap boleh tanpa mengganti snapshot.
Schema lokal batch12 Ran, fingerprint existing terjaga, tidak seed pembayaran.
390 tests/3045 assertions, MySQL nominal besar/current read/worker dan regresi
browser master PASS; DevTools smoke login PASS. Belum ada consumer lintas module,
saldo uang, route penerimaan publik atau tombol checkout aktif.
