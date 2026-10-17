# Work Item: Riwayat Pembayaran

Status QA selesai, menunggu delivery remote, 18 Oktober 2026; dimulai17 Oktober.
Baseline da3811c. Scope disetujui user:
admin/finance membaca penerimaan penjualan dan pengeluaran pembelian, pencarian
nomor, filter tanggal/arah/akun/metode dan detail snapshot. Owner Finance/Payments.

## Contract

GET /finance/payments/history (Inertia finance/payments/history/index), /data
JSON {data,meta}, /{direction}/{id} JSON detail. Permission khusus
finance.payments.history.view untuk seluruh endpoint; view/manage master,
permission POS/pay purchase tidak memberi akses otomatis. Super-system memakai
Gate existing, validasi tetap berlaku. Auth/web/verified; tidak ada mutation route.

Satu baris per header immutable, urut waktu descending/direction/id, pagination25.
Filter search literal nomor max150, direction all/incoming/outgoing, account_id/
method_id ULID, date_from/date_to Y-m-d, page positif. Tanggal Asia/Jakarta
inklusif (akhir exclusive hari berikutnya), dibanding waktu storage app.timezone.
Filter akun+metode harus cocok pada baris split yang sama. Kolom Total dokumen
selalu header penuh, bukan jumlah akun terfilter; tidak ada agregat/net/saldo.

Read adapter hanya tabel Finance sendiri. Incoming payment_receipts/payment_records
memakai total/applied (bersih sesudah kembalian), outgoing disbursements/lines
memakai total/amount exact6. UI Indonesia tanpa nol akhir; source amount tidak
dibulatkan. Detail incoming juga tendered/change, outgoing null. Snapshot nama/
kode/jenis akun+metode boleh dibaca pemegang history, tanpa metadata rekening,
hash request, actor/credential atau data privat module lain. No live master join:
rename/nonaktif/soft delete master tidak menulis ulang histori. Pilihan filter
dari ledger snapshot maksimal1000/jenis, label representatif (bukan nama terbaru).

GET tidak membuat audit/read ledger, session/cache bukan business mutation.
Tidak ada saldo berjalan, mutasi manual, edit/hapus/restore ledger, reversal,
utang atau ekspor. Source contract sale/checkout/FIFO tidak diubah. Tidak perlu
schema/ADR/module/port publik lintas module baru; read port internal nyata.
Demo read-only tidak membuat seeder transaksi baru: QA menggunakan writer sale
dan purchase existing eksplisit pada database disposable, bukan data toko.
Aktivasi hanya permission catalog tanpa grant/reset/seed/pay.

Lihat [plan](plan.md), [tasks](tasks.md).

## Verifikasi Aktual

Full480 test/3845 assertion, TS/ESLint/build2730/Pint, audit dependency, native
MySQL exact nominal/date/union/snapshot dan browser matrix desktop/tablet PASS.
Console unexpected0, tiga HTTP error sengaja diinjeksi/diharapkan dipisahkan.
DevTools MCP BLOCKED karena tool hilang setelah interupsi, fallback Playwright
existing tanpa dependency baru. Helper aktivasi dua kali PASS fingerprint data
lama, catalog permission saja; tidak seed/grant/transaksi/schema baru.
