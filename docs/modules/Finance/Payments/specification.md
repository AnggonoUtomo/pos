# Specification: Master Kas/Bank Dan Metode Pembayaran

## Data Dan Invariant

payment_accounts: ULID id, code unique maksimal32, name maksimal150,
type cash/bank, bank_name/account_number/account_holder nullable maksimal150/
64/150, is_active boolean default true, timestamps dan soft delete.
Metadata bank hanya boleh terisi pada type bank; nomor rekening berupa string
agar nol awal tidak hilang. Metadata opsional, bukan credential integrasi.

payment_methods: ULID id, code unique maksimal32, name maksimal150,
type cash/debit/transfer, payment_account_id ULID FK restrict, is_active default
true, timestamps/soft delete. Cash harus menunjuk account cash; debit/transfer
account bank. Account wajib aktif/non-trashed pada create/edit/restore metode,
termasuk ketika metode akan disimpan nonaktif. Tidak ada mapping tersembunyi.

Kode trim/uppercase, nama trim, metadata trim/empty->null. Unik termasuk trashed.
Account yang dirujuk metode tidak boleh delete/nonaktif atau ganti type,
termasuk metode nonaktif/trashed. Nama/kode/metadata boleh diedit. Restore account
tidak mengaktifkan otomatis; restore metode tetap memerlukan account valid.
Metode dengan histori payment_records tidak boleh delete/nonaktif/ganti type
atau pindah akun. Nama/kode boleh berubah tanpa menulis ulang snapshot. Akun
juga dilindungi referensi histori langsung, bukan hanya relasi metode saat ini.

## Layer Dan Consistency

Domain enum PaymentAccountType/PaymentMethodType memiliki aturan kompatibilitas
murni. Application ManagePaymentAccount/ManagePaymentMethod memvalidasi DTO dan
transaction boundary. PaymentsRepository port internal, bukan public mutation
lintas module. Adapter Eloquent hanya mengambil model Finance/Payments sendiri.
ServiceProvider binding dan load route/migration, tanpa business logic.

Lock order account -> method untuk assignment/restore. Account delete/type/
status change memegang lock account yang sama sebelum reference current read.
Pembacaan sebelum mutation memakai current locking read MySQL, bukan dirty check
snapshot lama. Data dan activity log commit/rollback bersama; no-op tanpa audit.
Tidak menambah event/port payment tanpa consumer atau abstraction global baru.

## Penerimaan Penjualan Internal

RecordSalePayment memakai PaymentsRepository internal; public SalePaymentWriter
memiliki consumer PostInvoice POS sejak Increment 4. Tidak ada route posting
Finance bebas. Input source_id ULID/source_number maksimal32,
total decimal string scale2 dan 1..10 pasangan method_id/amount unik. Jenis dan
akun dibaca dari master, bukan caller. Urutan metode di-canonical berdasarkan
ID, angka scale2, actor masuk hash. Retry identik mengembalikan snapshot tanpa
menambah record/audit; payload/actor berbeda pada sumber sama ditolak.

payment_receipts: ULID, source_type sale/source_id unik, source_number, hash,
total/tendered/change decimal30,2, posted_at server dan posted_by nullable.
payment_records: ULID, receipt_id/line_number unik, receipt_id/method_id unik,
FK RESTRICT metode/akun/header, snapshot code/name/type metode/akun dan
tendered_amount/applied_amount/change_amount decimal30,2. Tidak ada soft delete,
update/delete action, bank metadata, running balance atau jurnal accounting.
Down migration menolak header posted, bukan menghapus histori.

PaymentSettlement menghitung lunas, maksimal satu cash, kembalian hanya dari
cash, noncash tidak berlebih dan setiap baris berkontribusi positif. Applied
adalah kas/bank masuk bersih: tagihan100000, transfer60000, tunai50000 memberi
change10000 dan applied cash40000/bank60000. Belum ada fee/gateway/piutang.

Discovery assignment kemudian lock akun unik terurut, metode unik terurut,
receipt dan records current read pada transaction default. Assignment berubah
ditolak; posting baru membutuhkan master aktif/non-trashed/type cocok.
Replay memakai snapshot tersimpan; guard histori mencegah konfigurasi penting
berubah lewat aplikasi. Unique sumber dan retry deadlock terbatas melindungi
persistensi. Orkestrasi invoice/FIFO ada di PostInvoice POS (Increment4).
Audit finance.payments event received wajib tersimpan atomic, termasuk penolakan
veto/disabled logger. Audit hanya source/number/actor, bukan payload rekening.

Published SalePaymentWriter::receive(SalePaymentRequest) memakai transaction
caller dan mengembalikan SalePaymentResult (receipt ID/snapshot total, tendered,
change dan baris method ID/name/type/tendered/applied/change). Tidak mengekspor
metadata/ID akun ke receipt POS. SalePaymentMethods::choices dipakai endpoint
POS untuk pilihan metode aktif dengan account aktif/compatible, maksimal1000,
hanya id/code/name/type. Ini bukan akses PaymentsRepository atau CRUD Finance.

## HTTP Dan UI

Prefix /finance/payment-accounts (finance.payment-accounts.*) dan
/finance/payment-methods (finance.payment-methods.*). GET index/data,
POST store, PUT {id}, DELETE {id}, POST {id}/restore. Auth/web/verified/CSRF,
HasMiddleware can:finance.payments.view untuk baca dan manage untuk mutation.
JSON create201/id, mutation200/id; Inertia redirect success. Error422 teredaksi,
missing resource404; super-system tetap tunduk invariant. Field tak dikenal
tidak dipersist. Account invalid/mismatch: payment_account_id; guard: payments.

Index records/filters; metode menambah accountOptions aktif (max1000). JSON data
{data,meta}, 25/baris urutcode/id. Search code/nama maksimal150, status
all/active/inactive, trashed0/1, page positif; filter account/type sesuai halaman.
UI di resources/js/pages/finance/payments, komponen lokal. MasterWorkspace dan
lifecycle shared existing dipakai tanpa membuat framework CRUD lain.
Account form metadata bank conditional, list nomor rekening masked; akses JSON
tetap hanya finance.view, editing hanya manage. Loading/empty/error, Sonner,
dropdown shortcut, command palette, sidebar dan desktop/tablet. Mobile ditunda.

## Audit Dan Demo

Log finance.payment_accounts dan finance.payment_methods: code/name/type/status/
relation/deleted_at, actor dan subject ULID. Nilai bank_name/account_number/
account_holder tidak disalin ke log; edit metadata-only mencatat changed_fields
berupa nama field saja. Tidak menambahkan read audit sebagai ledger uang.

PaymentsAccessSeeder hanya katalog permission tanpa user/role/grant.
PaymentsDemoSeeder local/testing saja, dua account dan tiga metode fiktif lewat
Application. Existing code termasuk trashed dilewati, prerequisite inactive/
incompatible pada metode baru membatalkan batch/audit. Tidak mengarang rekening
nyata, saldo atau transaksi. Aktivasi lokal eksplisit/additive dan fingerprint
akun/grant/master/stok/ledger/harga existing wajib terjaga.

## Non-Scope

Saldo awal, running balance kas/bank, settlement/fee, rekonsiliasi, utang/
piutang, jurnal/full accounting, payment gateway, QRIS, integrasi rekening nyata,
input diskon/pajak. Backend posting POS/FIFO berada di Sales/POS; UI pembayaran
belum diaktifkan sampai Increment 5.
