# Work Item: Riwayat Penjualan

## Status

In Progress. Scope readonly disetujui user 16 Oktober 2026.
Owner Sales/POS; baseline checkout selesai pada40f1c53.

## Tujuan Dan Scope

Kasir/admin dapat mencari invoice posted dari server, membuka snapshot dan
mencetak ulang tanpa bergantung pointer receipt terakhir pada tab browser.
Daftar admin /sales/invoices dengan pagination25, pencarian nomor invoice literal,
filter tanggal WIB (Asia/Jakarta), gudang dan pelanggan. Tanpa tanggal default
berarti semua tanggal; batas akhir inklusif melalui awal hari berikutnya.
Urutan posted_at desc lalu id desc stabil. Detail memakai GET /pos/invoices/{id}
existing dan komponen receipt module, bukan membuat versi receipt baru.

User dengan sales.pos.invoices.post hanya melihat miliknya; permission
sales.pos.invoices.view boleh lintas pemilik. Super-system tetap sesuai ADR-010.
List, jumlah, pilihan filter dan detail dibatasi backend, bukan client actor_id.
Pilihan gudang/pelanggan dari snapshot invoice yang diizinkan (maksimum1000
per jenis), termasuk master terhapus/nonaktif; nama representatif deterministic
per ID, sedangkan baris/detail selalu snapshot masing-masing. Tidak membuka
kontak, HPP, key/hash, identitas actor atau metadata rekening.

## Tidak Dikerjakan

Edit/hapus/void/retur, laporan margin/omzet agregat, export, recovery otomatis
hasil checkout yang ambigu, offline queue, perubahan retention akun, pricing,
Stock/FIFO/Finance writer, theme, dependency, permission baru atau auto-grant.
Tidak mengubah endpoint receipt/checkout publik existing.

## Dampak Dan Risiko

- Source app/Modules/Sales/POS dan resources/js/pages/sales/pos/history;
  navigation/palette mendapat tautan berizin.
- Tambah GET /sales/invoices dan /sales/invoices/data, bukan mutation.
- Query melalui port internal Application dan adapter persistence milik POS.
  Tidak mengimpor model privat module lain; tidak membuat module/generator baru.
- Tidak perlu schema/migration, soft delete atau audit read palsu. Ledger tetap
  immutable; read/print tidak mengubah Stock/Finance/activity log.
- POSDemoSeeder tetap no-op; fixture disposable CCTV/kelistrikan membuat posted
  invoice melalui PostInvoice, tanpa seeding transaksi pada database user.
- Snapshot/date sort dapat membutuhkan optimasi index setelah volume nyata
  diukur. Filter choices bounded; bukan customer remote search seluruh master.
- Receipt GET tetap ownership existing. Riwayat membantu pemeriksaan manual,
  bukan bukti otomatis transaksi ambigu sudah/belum posted. Jangan ulang bayar
  dengan key baru tanpa rekonsiliasi.

## Acceptance

- [ ] Daftar/filter/pagination exact dan snapshot historis benar.
- [ ] Hak sendiri/lintas kasir berlaku pada list/count/options/detail.
- [ ] UI readonly, detail/reload/cetak, shortcut/palette dan state lengkap.
- [ ] QA desktop/tablet Light/Dark dan checkout regression lulus.
- [ ] Data lokal aman, dokumen aktual dan commit/push terverifikasi.

Urutan dan checklist: [plan](plan.md), [tasks](tasks.md).
