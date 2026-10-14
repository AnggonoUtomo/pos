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
Jenis metode/account belum memiliki histori payment; perubahan konfigurasi saat
ini audited. Consumer payment kelak wajib menambah guard/snapshot historis.

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

Saldo awal, mutasi kas/bank, payment record, settlement/fee, rekonsiliasi, utang/
piutang, jurnal/full accounting, payment gateway, QRIS, integrasi rekening nyata,
posting POS/FIFO dan diskon/pajak. Tidak mengaktifkan tombol pembayaran POS.
