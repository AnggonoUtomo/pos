# Work Item: Pembayaran Pembelian Lunas

Status Done, 17 Oktober 2026. Baseline3470416.
User menyetujui pembayaran
sesudah penerimaan penuh, sekali lunas, multi-metode dengan jumlah tepat tanpa
kembalian. Owner Purchasing/PurchaseOrders dan Finance/Payments.

## Contract Dan Batas

POST /purchasing/purchase-orders/{id}/pay, permission
purchasing.purchase-orders.pay terpisah dari view/manage/post. Payload version
dan payments1..10 {method_id,amount}; field lain ditolak (CSRF diizinkan).
Order harus posted dengan receipt stok, tidak terhapus. Harga/total berasal
snapshot order; tidak memvalidasi ulang supplier/item yang sudah posted.
Total dan setiap amount positif exact scale6 sesuai pembelian; tanpa rounding,
float, kembalian atau partial. Metode unik, akun/metode aktif dan compatible.

Finance memiliki payment_disbursements/payment_disbursement_lines immutable,
terpisah dari receipt kas masuk penjualan. PurchasePaymentWriter/DTOs publik
dipanggil PayPurchaseOrder dalam transaction caller. Snapshot aman hanya nama/
jenis metode dan amount; tidak mengekspor metadata bank atau repository privat.
PurchasePaymentMethods menyediakan pilihan aktif, adapter lookup existing
dipakai ulang tanpa mengubah contract POS.

Lock order: purchase+lines, akun sorted, metode sorted, disbursement. Unique
source purchase dan hash canonical (tanpa actor) melindungi replay. Version
order naik sekali, paid_from_version menyimpan request asal. Replay versi asal
dengan pembayaran identik mengembalikan hasil lama sebelum lookup master;
payload/versi berbeda ditolak. Pointer/snapshot/payment/time/dua audit commit
bersama atau rollback. Stok/FIFO dan penerimaan penjualan tidak disentuh.

Schema additive tanpa backfill paid. Existing posted tetap berlabel Belum
dicatat, bukan dianggap lunas atau dimigrasikan menjadi utang. Tidak ada saldo
kas/bank berjalan atau pengujian kecukupan kas; ini pencatatan pengeluaran,
bukan transfer bank/gateway. Utang/jatuh tempo, partial, uang muka, reversal,
pajak/diskon/cetak laporan bukan scope. Down menolak histori posted.

Demo CCTV/kelistrikan local/testing eksplisit, skip data user-edited/trashed/
paid. Aktivasi lokal hanya migration/catalog tanpa grant/seed/pay/reset akun.
QA destructive/concurrency memakai database disposable; data existing wajib
identik. Old-docs/SampleUI/empat LSP user tidak disentuh.

Lihat [plan](plan.md) dan [tasks](tasks.md).

## Verifikasi Aktual

Full476 test/3757 assertion, typecheck/lint/build2724/Pint, audit dependency PASS.
DevTools MCP authenticated dan Playwright desktop/tablet Light/Dark PASS, console
kosong. Native MySQL worker double payment/current read LOCK WAIT PASS, audit
rollback dan stock fingerprint aman. Migration lokal batch18/19 Ran, helper dua
kali PASS tanpa mengubah data lama, seed/pay/grant/reset. Matriks QA dan batas
DevTools viewport/root dicatat di [tasks](tasks.md).
Delivery implementasi/QA1715598 sudah dipush origin/main; HEAD sama dengan
ls-remote, divergence0/0. SampleUI/empat LSP user tetap untracked dan terpisah.
