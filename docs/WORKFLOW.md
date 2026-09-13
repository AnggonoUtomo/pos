# Workflow Kerja

## Tentukan Jenis Pekerjaan

| Jenis pekerjaan | Dokumentasi minimum |
| --- | --- |
| Modul baru | Folder module, README, specification, plan, dan tasks |
| Capability signifikan dalam module | Folder work item module, README, plan, dan tasks |
| Pekerjaan lintas module | Folder global work item, README, plan, dan tasks |
| Bug kecil atau satu file | Tidak perlu folder kerja baru, kecuali user meminta |

Gunakan PRD bila pekerjaan memperkenalkan kebutuhan produk baru atau requirement
belum jelas. Gunakan ADR hanya untuk keputusan yang mahal atau sulit dibalik.

## Sebelum Mengubah Kode

1. Pilih atau buat work item.
2. Baca dokumen rujukan work item.
3. Baca dokumen module bila pekerjaan menyentuh module.
4. Informasikan module target yang akan dikerjakan.
5. Isi checklist sebelum coding pada `tasks.md`.
6. Pastikan rencana increment dan QA automated sudah tertulis.
7. Pastikan acceptance criteria jelas.
8. Pastikan dampak database, route, permission, UI, laporan, seeder, dan audit
   disebut bila tersentuh.
9. Pastikan risiko FIFO, stok, payment, tax, diskon, dan pricing dipertimbangkan
   bila tersentuh.
10. Cek status git jika repository tersedia.

Untuk module, generator, atau struktur baru, jalankan dry-run bila struktur
belum pasti:

```powershell
php artisan module:make {Domain} {Module} --dry-run
```

Jika dokumen, kode, generator, dan test structure bertentangan, hentikan
perubahan struktural dan minta keputusan.

## Saat Mengubah

- Kerjakan satu increment kecil pada satu waktu.
- Jangan membuat fitur di luar work item.
- Tambahkan atau update test bila behavior berubah.
- Controller memanggil Application action/query, bukan persistence langsung.
- Gunakan permission backend untuk action yang dilindungi.
- Untuk UI, gunakan shadcn/ui, `lucide-react`, dan Sonner toast untuk feedback
  CRUD.
- Untuk master/operasional mutable, gunakan soft delete secara default kecuali
  ada alasan eksplisit.
- Untuk transaksi posted dan ledger-like records, gunakan reversal, void, retur,
  atau adjustment, bukan soft delete sebagai koreksi.

## Setelah Mengubah

1. Jalankan verifikasi yang tertulis pada `tasks.md`.
2. Jalankan focused test terlebih dahulu.
3. Jalankan lint/build bila frontend tersentuh.
4. Jalankan route/migration check bila route atau schema berubah.
5. Jalankan Chrome DevTools MCP bila UI/browser tersentuh, atau catat alasan
   `SKIPPED/BLOCKED`.
6. Catat command, hasil, dan bukti ringkas pada work item.
7. Isi checklist sesudah coding.
8. Update status increment, status work item, dan `tasks.md` module.
9. Laporkan risiko yang masih terbuka.
10. Buat commit otomatis sebagai save point setelah task atau increment selesai
    dan verifikasi relevan lulus.
11. Berhenti setelah scope work item terpenuhi dan tunggu arahan berikutnya.

Push hanya dilakukan saat keseluruhan work item/task besar selesai atau jika
user meminta eksplisit.

## Status Work Item

- `Draft`: scope masih disusun.
- `Ready`: siap dikerjakan.
- `In Progress`: sedang dikerjakan.
- `Blocked`: menunggu keputusan atau dependency.
- `Done`: implementasi dan verifikasi scope selesai.
- `Cancelled`: dibatalkan.

## Status Increment

- `Planned`: belum mulai.
- `Ready`: acceptance criteria dan verifikasi jelas.
- `In Progress`: sedang dikerjakan.
- `Passed`: coding dan verifikasi increment selesai.
- `Failed`: verifikasi gagal.
- `Deferred`: ditunda dengan alasan jelas.

## Catatan Bukti

Untuk perubahan penting, catat singkat:

- apa yang berubah;
- alasan;
- command yang dijalankan;
- hasil;
- risiko;
- gap conformance bila ada.

Tidak perlu membuat execution log panjang untuk perubahan kecil yang sudah
tercakup oleh test dan Git history.
