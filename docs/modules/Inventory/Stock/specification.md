# Specification: Stok Awal Dan Fondasi FIFO

## Status Dan Persetujuan

Disetujui untuk scope awal pada 12 Oktober 2026. Implementasi masih pending.
User menyetujui harga pokok per satuan pilihan wajib positif, satu barang satu
baris dokumen, dan stok awal hanya sekali per pasangan barang + gudang.

## Alur Dan Invariant

1. Admin memilih satu gudang, lalu barang, satuan, kuantitas dan harga pokok.
2. Simpan draft; belum mempengaruhi saldo, movement atau FIFO.
3. Draft dapat diedit, soft delete dan restore. Posted tidak boleh diedit,
   dihapus, atau dipulihkan sebagai draft.
4. Posting memvalidasi master aktif/tidak terhapus dan konversi terkini. Rasio
   draft yang berubah wajib disimpan ulang; bukan diam-diam dihitung ulang.
5. Posting pertama membuat saldo, satu movement masuk per baris dan satu layer
   FIFO per baris. Posting ulang dokumen yang sama tidak menggandakan stok/audit.
6. Pasangan barang + gudang yang sudah mempunyai saldo/histori tidak boleh
   menerima stok awal kedua. Unique constraint dan lock master melindungi race.
7. Kuantitas/harga pokok positif, maksimum 100 baris, item tidak berulang.
8. Urutan layer kelak berdasarkan waktu posting lalu ULID, per item + warehouse.
   Pengeluaran/consumption FIFO belum dibuat karena belum ada consumer nyata.

## Presisi Dan Snapshot

- Decimal string, bukan float: quantity/ratio/cost maksimal enam desimal.
- Quantity/base_quantity decimal(20,6); total nilai decimal(30,6).
- SQLite disposable memakai string untuk kolom decimal Stock agar NUMERIC
  affinity tidak membuang digit. MySQL tetap DECIMAL; domain memvalidasi scale/range.
- Konversi quantity × ratio dan quantity × harga pokok harus tepat pada enam
  desimal; overflow atau kebutuhan rounding ditolak sebelum persistence.
- Harga pokok input per satuan pilihan, bukan per satuan dasar. Contoh 2 dus,
  ratio 12, Rp120000/dus: 24 pcs dan total nilai Rp240000.
- Original/remaining quantity dan original/remaining value menjadi sumber FIFO.
  Nilai dibagi quantity hanya representasi biaya dasar; pembagian berulang
  tidak boleh membuang residual nilai ketika consumer FIFO kelak dibuat.
- Snapshot SKU/nama barang, satuan/base unit, rasio, nama/kode gudang dan harga
  pokok disimpan pada posting. Rename master tidak mengubah histori.
- Tanggal posting berasal dari server; backdating/nomor dokumen custom ditunda.
  Nomor awal memakai prefix `SA-` dan ULID dokumen, bukan sequence global.

## Data Dan Arsitektur

Module app/Modules/Inventory/Stock dengan ServiceProvider sebagai composition
root. Presentation memanggil Application; Domain valuasi bebas Laravel.
Application memakai port repository serta lookup publik; adapter DB milik Stock
tidak mengimpor model privat module lain.

Tabel ULID: stock_openings, stock_opening_lines, stock_balances,
stock_movements, fifo_layers. Hanya stock_openings draft menggunakan soft delete.
Lines draft diganti dalam transaksi saat edit; lines posted, balance, movement,
layer tidak menyediakan delete bebas. FK restrict menjaga referensi master.
Audit created/updated/deleted/restored/posted atomic dengan mutation.
Lock order: dokumen existing, gudang, satuan terurut, barang terurut; posting
pasangan kosong diserialisasi melalui lock gudang. Guard master memeriksa
histori setelah lock master yang sama, bukan query tanpa lock.

## Guard Master Dan Public Boundary

Setelah barang punya histori stok, dasar tetap terkunci, satuan existing tidak
boleh dibuang atau rasionya diubah; penambahan satuan baru, barcode, flag dan
nama masih boleh diubah. Hapus/nonaktif barang atau gudang berhistori ditolak
pada scope awal yang belum menyediakan closing/reversal. Guard berlaku untuk
super-system. Tidak membaca model/repository privat lintas module.

## UI Dan Endpoint

Halaman resources/js/pages/inventory/stock dengan tab Stok awal, Saldo stok,
Kartu stok. Filter gudang/pencarian, pagination server dan detail dokumen.
Dialog draft, konfirmasi posting/lifecycle, Sonner, loading/empty/error,
shortcut dropdown dan navigasi command palette. Desktop/tablet; mobile ditunda.
View/manage/post adalah permission terpisah, tidak ada auto-grant.

## Seeder Dan QA

Permission-only seeder aman diulang. Demo terkait master demo menggunakan action
yang sama, idempotent tanpa mengubah posted data; hanya dijalankan eksplisit.
QA destructive memakai SQLite disposable. Migration lokal additive, tidak
menjalankan migrate:fresh pada MySQL user. Pengujian konkurensi MySQL perlu
dibuktikan terpisah; test SQLite tidak diklaim membuktikan row locking.

## Acceptance

- [ ] Draft/lifecycle tidak mengubah saldo.
- [ ] Posting exact, snapshot, atomic dan idempotent; stok awal kedua ditolak.
- [ ] Guard master dan permission backend teruji termasuk super-system.
- [ ] Saldo/kartu stok menggunakan ledger, bukan activity log.
- [ ] UI desktop/tablet dan seluruh gate relevan diverifikasi.
- [ ] Tidak ada consumer FIFO pengeluaran palsu atau koreksi posted bebas.
