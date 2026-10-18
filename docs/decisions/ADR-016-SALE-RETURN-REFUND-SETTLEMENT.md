# ADR-016: Penyelesaian Refund Retur Penjualan

## Status

Accepted untuk aturan dan desain Increment 2 berdasarkan scope interview user.
Writer/ledger Finance dan lock master diimplementasi pada Increment 3 serta
diuji disposable. Scope invoice/settlement Returns dan endpoint tersedia pada
Increment 4. UI/history dan aktivasi lokal masih rencana Increment 5..6;
penerimaan ADR bukan bukti payout siap operasional.

## Tanggal

2026-10-18

## Konteks

Retur posted sudah menerima barang melalui Stock sesuai ADR-015. Hak refund
pelanggan berasal dari harga bersih penjualan, bukan biaya FIFO. Beberapa retur
dapat menunjuk baris invoice sama, mempunyai pecahan enam desimal, dan dibayar
dalam urutan berbeda dari penerimaan fisik.

Penjualan menyimpan decimal30,2; pembelian memakai decimal30,6. Memakai ledger
pembelian langsung untuk refund akan mempersempit batas integer uang dari28
menjadi24. Route riwayat outgoing existing juga tidak mengenal source refund.

## Disetujui User

- Hanya retur posted; satu penyelesaian penuh dengan multi-payment tepat,
  tanpa kembalian. Metode aktif boleh berbeda dari invoice asal.
- Nilai otomatis dari snapshot bersih baris asal; sebagian round down scale2,
  penghabisan kuantitas mengambil residual. Total tidak melebihi hak asal.
- Hasil nol diselesaikan dengan audit tanpa metode/ledger pembayaran nol;
  kuantitas tetap dihitung telah diselesaikan.
- Permission refund terpisah. Payout, alokasi dan audit atomic/replay aman,
  tanpa mutasi Stock/FIFO/invoice/penerimaan uang asal.
- Parsial uang, kredit pelanggan, tukar, reversal dan saldo kas di luar scope.

## Keputusan Valuasi

`Sales/Returns/Domain/ValueObjects/RefundInvoiceBasis` memeriksa seluruh snapshot
invoice, bukan hanya baris yang sedang diretur. Input header wajib mempunyai
subtotal, discount_amount, tax_amount, total dan paid_total dari snapshot total
pembayaran immutable milik POS. `paid_total` adalah uang masuk bersih, bukan
tendered sebelum kembalian. Jumlah subtotal/diskon/pajak semua baris harus sama
dengan header, serta jumlah net baris = total header = paid_total >0.

Net baris = subtotal - discount_amount + tax_amount. Diskon tidak melebihi
subtotal, semua nilai nonnegatif; line net boleh nol. Snapshot invalid atau
diskon/pajak header yang belum dialokasikan ke baris ditolak, bukan dibagikan
diam-diam. Ini tidak menambahkan input diskon/pajak baru pada POS.

Uang input/output maksimal28 digit integer dan2 pecahan; kuantitas14 digit
integer dan6 pecahan, positif pada baris asal/input retur. Semua decimal string,
bukan float. Penjumlahan, pengurangan dan perkalian menggunakan presisi arbitrary
sebelum validasi batas penyimpanan; overflow atau presisi berlebih ditolak.

`RefundAllocation` menerima kuantitas retur, kuantitas/net asal dan total
kuantitas/uang yang sudah diselesaikan per original invoice line. Hitung:

```text
remaining_quantity = sold_quantity - settled_quantity
remaining_amount   = original_net - settled_amount
partial_amount     = floor(remaining_amount * quantity / remaining_quantity, 2)
full_amount        = remaining_amount
```

Alokasi nol tetap menambah settled_quantity. Setiap original line dihitung
terpisah, termasuk barang sama yang dijual dengan satuan berbeda. Proporsi
memakai satuan jual snapshot baris; tidak mengalikan harga per base unit yang
sudah dibulatkan. Hasil residual mengikuti urutan settlement, bukan urutan
posted fisik. Tidak ada perubahan invoice asal untuk menyimpan perhitungan ini.

## Boundary Publik Yang Dirancang

Tidak membuat interface/DTOs kosong pada Increment 2. File baru diterbitkan
saat consumer operasional berikutnya ada:

- Increment 4 menambah `ReturnInvoiceLookup::refundBasis(id)` dengan
  `ReturnRefundBasisData` (header ringkas dan lines). Adapter POS membaca tabel
  miliknya sendiri dan memproyeksikan total pembayaran dari `sales_invoices.payments`
  menggunakan parser JSON. Ia tidak membaca tabel/model privat Finance.
- Header hanya id/subtotal/discount_amount/tax_amount/total/paid_total; lines
  hanya id/quantity/subtotal/discount_amount/tax_amount. Caller Returns memberi
  header/lines tersebut ke `RefundInvoiceBasis::fromSnapshot`.
- `find/search`, `ReturnInvoiceData::toArray`, snapshot draft dan endpoint sumber
  existing tidak berubah. Data pembayaran/split/HPP tidak ditambahkan ke lookup
  draft. Snapshot summary dipakai internal untuk verifikasi, bukan payload UI.
- Increment 3 menerbitkan `SaleRefundPaymentWriter::pay(SaleRefundPaymentRequest)`
  dan `SaleRefundPaymentMethods::choices()` milik Finance untuk consumer
  `SettleReturnRefund`/quote milik Returns pada Increment 4. Request sourceId
  (return ID), sourceNumber, total, payments {method_id,amount}, actorId nullable.
  Result id/paidAt/snapshot {total,payments method_id/name/type/amount} tanpa akun,
  rekening, request hash atau HPP.
- Shared default DB transaction caller wajib. Nilai total/split scale2 positif,
  1..10 metode unik, tepat jumlah. Nol tidak memanggil writer. Finance menemukan
  akun sendiri, mengunci akun terurut lalu metode terurut, recheck assignment/
  status/type. Replay identik membaca snapshot sebelum validasi master live.

## Persistence Yang Dirancang

Schema berikut baru dibuat pada Increment 3/4 di module owner, bukan migration
global. Semua tabel baru menggunakan ULID dan tidak mempunyai soft delete.

Finance memiliki `payment_refunds` dan `payment_refund_lines`, decimal30,2.
Header: id, source_type sale_return, source_id unique, source_number, request_hash,
total, paid_at server dan paid_by nullable actor. Lines: id, refund_id FK restrict, line_number,
method_id/account_id FK restrict, snapshot code/name/type metode+akun, amount.
Unique header/line_number dan header/method. Waktu dibentuk server, audit
finance.payments event refunded wajib. Guard histori akun/metode meliputi ledger
ini. Tidak ada payout manual bebas atau mutasi fisik rekening.

Returns memiliki:

- `sales_return_refund_scopes`: id ULID, invoice_id unique, revision unsigned
  integer mulai0. Invoice ID adalah sumber immutable; tidak mengimpor model POS.
- `sales_return_settlements`: id, sales_return_id FK restrict unique, scope_id FK
  restrict, outcome refunded/no_payment, total decimal30,2, finance_refund_id ULID
  nullable unique, from_version, scope_revision, quote_fingerprint, request_hash,
  snapshot hasil aman, settled_at/by. Hubungan Finance berupa ID hasil contract,
  tidak membutuhkan FK lintas module yang bergantung urutan migration.
- `sales_return_refund_allocations`: id, settlement_id FK restrict,
  sales_return_line_id FK restrict unique, invoice_line_id, quantity decimal20,6,
  amount decimal30,2. Index invoice_line_id dan unique settlement/invoice_line_id.
  Nilai nol tetap disimpan sebagai alokasi, bukan pembayaran uang.

Posted header/lines asli Returns tidak diubah. `sales_returns.status` tetap
posted dan version tidak bertambah akibat settlement, sehingga replay posting
fisik tetap stabil. Status uang dibaca dari settlement, bukan menimpa status fisik.
Outcomes nol wajib finance_refund_id null; positif wajib ID hasil Finance.

## Locking, Quote Dan Replay Yang Dirancang

Quote menggunakan read transaction konsisten, tanpa membuat guard row/menulis
audit atau memesan hak. Scope belum ada dianggap revision0. Fingerprint SHA256
dibentuk server atas return ID/version, revision invoice, basis asal dan alokasi
terurut/decimal canonical. Pilihan metode tidak menjadi reservasi.

Settlement dalam transaction default retry deadlock terbatas mengikuti urutan:

1. Baca invoice_id dari Returns untuk discovery; insert-if-absent scope unique
   dalam transaction, lalu lock scope/current revision. Tangani initial-create
   race melalui constraint dan retry, bukan lock pada row yang belum ada saja.
2. Lock return header dan lines terurut; recheck invoice_id/status/version,
   receipt fisik dan kelengkapan snapshot. Jangan lock atau update tabel POS.
3. Baca settlement/alokasi sebelumnya via current locking read terurut, dengan
   scope yang sama. Cocokkan returned line, original line dan jumlah eligible.
4. Jika sudah settled, bandingkan hash input canonical tersimpan dan kembalikan
   hasil yang sama sebelum menghitung ulang hak atau lookup master.
5. Untuk settlement baru, hitung ulang basis/quote dan validasi fingerprint.
   Quote stale ditolak422 meskipun nominal kebetulan sama. UI harus konfirmasi
   ulang; tidak mengubah nominal setelah kasir menekan konfirmasi.
6. Panggil writer untuk total positif; hasil nol wajib payments []. Simpan
   settlement/alokasi, naikkan scope revision dengan overflow guard dan audit
   Returns. Commit bersama; audit veto di salah satu module rollback seluruhnya.

Canonical input berisi return ID, version, quote_fingerprint serta metode unik
sorted/amount scale2. Actor tidak masuk hash; aktor retry tetap memerlukan
permission, dan aktor asli settlement tidak ditimpa. Payload berbeda ditolak.
Scope mengurutkan semua settlement invoice sama, bukan hanya return sama;
native QA dua proses wajib sebelum mengklaim concurrency selesai.

Scope revision dibatasi0..2147483647; settlement baru memerlukan revision
di bawah batas maksimum sebelum increment. Tidak membungkus integer saat penuh.

## HTTP Dan Riwayat Yang Dirancang

`sales.returns.refund` melindungi quote dan settlement, terpisah dari manage/post.
GET `/{id}/refund-quote` menghasilkan total, alokasi, version/fingerprint dan
pilihan metode aman; POST `/{id}/refund` menerima version/quote_fingerprint/
payments. Semua route di prefix `/sales/returns`, auth/verified/CSRF. Detail
payload dan error tercatat di [API](../API.md#contract-refund-retur).

Finance history menambah union ledger refund positif dengan direction outgoing
dan source_type sale_return. Route detail existing incoming/outgoing tetap
utuh; tambah `/finance/payments/history/refunds/{id}` untuk detail refund dengan
permission history existing. Pemisahan path menghindari ambiguitas ID antar
ledger terpisah. Tambah source_type pada rows secara additive untuk memilih
detail/label sumber; tidak mengubah nilai total akun terfilter menjadi agregat.

## Alternatif Dan Konsekuensi

- Memperluas ledger purchase menjadi decimal34,6 dan writer generik ditolak:
  menambah migration/risiko kontrak lama untuk transaksi berbeda. Ledger khusus
  memerlukan union/guard baru, tetapi presisi dan makna histori lama tetap.
- Menambah total pembayaran pada endpoint draft ditolak: tidak diperlukan oleh
  consumer draft. Projection khusus refund menjaga boundary data yang ada.
- Membulatkan naik, mengarang pembayaran nol, memakai HPP atau mengubah invoice
  asal ditolak karena melanggar nilai pelanggan/immutability.
- Tidak memakai event async untuk invariant uang. Audit bukan ledger. Down
  migration menolak histori settlement/refund; koreksi setelah posted adalah
  forward fix/reversal terpisah yang disetujui, bukan delete atau soft delete.

## Verifikasi

Increment 2: unit valuasi/reconciliation/red-green, regresi Returns/POS/Finance,
Pint dan review docs/API/boundary. Tidak ada migration/endpoint/UI refund aktif.
Implementasi adapter/constraint/worker MySQL/QA browser tetap acceptance
Increment 3..6, bukan dianggap PASS karena ADR ini Accepted.

Increment 3 diuji pada 19 Oktober2026: schema decimal30,2/up-down disposable,
worker nyata LOCK WAIT untuk replay satu payout, current read menolak metode
nonaktif/assignment berubah, presisi28 digit/sen terakhir, audit veto, FK restrict
dan down guard PASS. Guard master tetap berjalan sebelum tabel refund tersedia.
Migration lokal masih Pending; tidak ada refund/demo/grant di DB user. Scope
invoice/alokasi dan rollback Returns setelah Finance masih acceptance Increment 4.

Increment 4: consumer QuoteReturnRefund/SettleReturnRefund dan summary POS
tersedia tanpa mengubah lookup draft. Current read/lock scope invoice melindungi
initial-create race dan retry. Native MySQL empat worker LOCK WAIT, stale quote
walau total sama, replay JSON, nol/sen terakhir, rollback audit Returns sesudah
Finance dan FK/down guard PASS. Version/status fisik tidak diubah. Initial
response dibaca dari snapshot settlement tersimpan agar urutan key JSON MySQL
tidak membedakan hasil first commit dan replay.

Quote/POST memeriksa ketersediaan tiga tabel Returns sebelum membaca ledger.
Schema belum aktif menghasilkan422 refund, bukan query ke tabel yang belum ada.
Permission catalog additive tidak memberi grant. Kedua migration lokal masih
Pending; native QA hanya pada database disposable yang sudah dibersihkan.

Referensi: [Work item](../work-items/sale-return-refund/README.md),
[ADR-014](ADR-014-PURCHASE-PAYMENT-DISBURSEMENTS.md),
[ADR-015](ADR-015-SALE-RETURN-COST-AND-FIFO-LAYERS.md).
