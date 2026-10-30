# Rencana Perbaikan

1. Reproduksi dan isolasi predicate pencocokan sumber yang gagal.
2. Tambahkan regresi, perbaiki penyebab tanpa melonggarkan integritas receipt.
3. Jalankan regresi retur/penerimaan uang dan gate backend akhir satu kali;
   periksa alur UI yang terdampak bila fasilitas browser tersedia.
4. Catat hasil, review diff, commit terfokus, lalu push setelah work item selesai.

Tidak ada migration, reset, seed produksi, atau perubahan posted historis yang
direncanakan. Jika dibutuhkan, hentikan dan minta keputusan user.
