# QA Automated

Dokumen ini menetapkan gerbang QA otomatis yang wajib dipikirkan sejak work-item disiapkan dan wajib dijalankan sebelum work-item dinyatakan selesai.

## Prinsip

- QA automated adalah bagian dari scope pekerjaan, bukan aktivitas opsional di akhir.
- Setiap work-item harus menyebut command verifikasi yang relevan sebelum coding dimulai.
- Command yang gagal tidak boleh ditulis sebagai lulus.
- Jika command belum bisa dijalankan, alasannya harus dicatat pada work-item.
- Verifikasi manual boleh melengkapi QA automated, tetapi tidak menggantikan test otomatis untuk business rule penting.

## Baseline Command

Command baseline dipilih sesuai dampak perubahan.

| Area perubahan | Command minimum |
| --- | --- |
| PHP/backend umum | `php artisan test` |
| Use case tertentu | `php artisan test --filter=NamaTest` |
| Composer/autoload/module scaffold | `composer dump-autoload` |
| Route berubah | `php artisan route:list` |
| Migration/schema berubah | `php artisan migrate:status` dan test migration terkait |
| Frontend berubah | `npm run build` |
| Lint tersedia dan menyentuh frontend | `npm run lint` |
| Permission/auth berubah | test auth/permission terkait |
| FIFO/stok/transaksi berubah | feature test transaksi dan stock invariant terkait |

## Invariant Yang Wajib Ditest Otomatis

Invariant berikut wajib memiliki test otomatis sebelum modul transaksi dianggap selesai:

- Stok tidak boleh minus.
- FIFO consume mengambil layer tertua yang masih tersedia.
- Stock balance, movement, dan FIFO layer konsisten setelah posting.
- Posted transaction tidak dapat diedit bebas.
- Retur, void, reversal, dan adjustment tercatat audited.
- Harga jual mengikuti customer level dan satuan.
- Konversi multi satuan memakai base unit yang benar.
- Diskon persen dan nominal dihitung sesuai urutan yang disepakati.
- Multi payment tidak menghasilkan selisih bayar tanpa status jelas.
- Walk-in customer tidak boleh memiliki piutang.
- Activity log tercatat untuk aksi penting.

## Bukti QA

Setelah pekerjaan selesai, work-item harus mencatat bukti seperti ini:

```text
Command: php artisan test --filter=InventoryFifoTest
Hasil: PASS
Catatan: 8 tests, 24 assertions.

Command: npm run build
Hasil: PASS
Catatan: Vite build sukses.
```

## Gerbang Selesai Modul

Sebuah modul atau increment modul hanya boleh ditandai selesai jika:

- Checklist sebelum coding sudah terisi.
- Checklist sesudah coding sudah terisi.
- QA automated yang relevan sudah dijalankan.
- Bukti command dicatat.
- Gap atau command yang belum bisa dijalankan dicatat eksplisit.
- Work-item memperbarui status increment.
