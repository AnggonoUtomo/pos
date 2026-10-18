# Finance/Payments

Status: Active. Fondasi master selesai/diverifikasi 15 Oktober 2026.
Memiliki master kas/bank dan metode pembayaran relasional sebagai prasyarat
pembayaran POS berikutnya. Penerimaan internal penjualan tersedia sejak
Increment 3 checkout (16 Oktober 2026); belum memiliki running balance.
UI checkout milik Sales/POS tersedia sejak Increment 5.

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
browser master PASS; DevTools smoke login PASS pada Increment 3.
Increment 4 memasang consumer PostInvoice melalui SalePaymentWriter/DTOs dan
pilihan aman SalePaymentMethods; tidak membuka repository/master mutation untuk
kasir. Backend dan UI multi-payment/receipt Sales/POS tersedia. Tidak ada running
balance atau route penerimaan Finance bebas.

[Pembayaran pembelian lunas](../../../work-items/purchase-paid-settlement/README.md)
tersedia melalui PurchasePaymentWriter/DTOs dengan consumer PayPurchaseOrder,
serta pilihan terbatas PurchasePaymentMethods. Ledger pengeluaran terpisah
payment_disbursements/payment_disbursement_lines, nilai exact6 sesuai biaya beli,
tanpa rounding/kembalian. Snapshot/audit/version commit bersama Purchasing,
tidak mengubah stok/FIFO. Guard master juga mencakup histori pengeluaran.
Tidak ada endpoint Finance pengeluaran bebas, running balance atau transfer bank
nyata. Local batch18/19 aktif tanpa seed/pay/grant. QA DevTools authenticated,
Playwright desktop/tablet dan native MySQL tercatat pada work item tersebut.

## Riwayat Pembayaran

Halaman [Pembayaran](http://pos.test/finance/payments/history) menyediakan daftar
kas masuk penjualan dan pengeluaran pembelian readonly. Permission khusus
finance.payments.history.view, bukan implikasi view/manage master. Filter nomor,
tanggal WIB, arah, akun dan metode; detail snapshot tanpa metadata rekening.
Satu baris per dokumen; Total dokumen tidak berubah menjadi total akun terfilter.
Penerimaan memakai jumlah bersih sesudah kembalian, pengeluaran exact6.
Read tidak membuat audit atau mutation ledger; tidak mengklaim saldo berjalan.
Lihat [work item](work-items/payment-history/README.md), termasuk alasan tidak
menambah seeder transaksi baru, dan hasil QA/aktivasi permission catalog saja.

## Writer Refund Retur

Increment 3 [refund retur](../../../work-items/sale-return-refund/README.md)
menyediakan writer internal/DTOs/choices dan ledger refund khusus decimal30,2,
canonical replay, audit wajib serta guard histori master. Bukan endpoint payout
bebas, bukan pembayaran purchase dan bukan klaim fitur refund siap UI.
Consumer settlement Returns dan quote tersedia sejak Increment 4;
riwayat refund dan UI masih increment lanjut.
Migration refund lokal Pending; QA schema dan native worker hanya pada database
disposable, tanpa payout/demo/grant di DB user.
