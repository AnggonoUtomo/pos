# Rencana Posting Retur Pembelian

| Increment | Status | Isi | Verifikasi |
| --- | --- | --- | --- |
| 1. Baseline | Passed | ADR, scope, ownership, contract/lock plan, checklist | Review source/dokumen, sembilan dokumen/link dan diff lulus |
| 2. Stock writer | Passed | Writer asal receipt, alokasi FIFO, issue/movement/consumption dan audit | 35 focused test/174 assertion; native layer asal dan dua worker |
| 3. Returns posting | Passed | Migration metadata, action, permission, route dan HTTP guard | 37 focused test/285 assertion; route 9, replay/403/rollback |
| 4. UI | Passed | Konfirmasi posting, status/filter/detail/readonly, shortcut dan Sonner | Typecheck/lint/build, DevTools MCP isolated PASS pada 1440/1024/768 Light/Dark |
| 5. Aktivasi | Passed | QA penuh/native, activation schema/catalog, dokumentasi dan delivery | 745 test/5890 assertion; native/activation dua kali; lokal Ran batch24 |

## Urutan Dan Kontrak

Stock writer dipublikasikan hanya bersama consumer `PostPurchaseReturnDraft`.
Request memuat gudang, retur, pembelian asal dan baris {return_line_id,
purchase_order_line_id, base_quantity}; Stock mengambil identitas layer dan
nilai biaya dari receipt/ledger miliknya sendiri. Returns tidak mengimpor model
atau repository privat Stock/PurchaseOrders.

Transaction caller mengunci draft lebih dulu. Stock mengunci gudang asal,
master terurut, saldo dan seluruh layer barang current read. Trace receipt
source line menunjuk movement penerimaan dan satu layer asal. Alokasi memakai
aturan `FifoAllocation` pada layer terpilih; cek saldo terhadap seluruh layer
sebelum menulis. Unique source issue dan source line melindungi replay. Reader
posted boleh bekerja meskipun master kemudian nonaktif; first posting tidak.

Migration metadata posted dimiliki Returns dan additive. Ledger Stock yang ada
memiliki `source_type`, issue/movement/consumption dan FK trace yang cukup;
tidak membuat tabel baru tanpa kebutuhan nyata. Aktivasi lokal hanya migration
dan permission catalog, tidak menjalankan seeder demo.
