# Rencana Demo CCTV Dan Kelistrikan

1. Increment 1: dataset, regresi dan implementasi seeder relasional.
   Acceptance: sembilan barang, delapan satuan, konversi per barang, meter
   pecahan, biaya FIFO tepat; existing row dan posted history terjaga.
   Verification: focused tests Inventory, Pint, full PHP suite.
2. Increment 2: konsistensi dokumentasi dan fixture QA.
   Acceptance: contoh aktif mengikuti domain toko; browser/MySQL QA tidak
   lagi mengandalkan grocery seed. Tidak mengubah theme atau mock POS.
   Verification: disposable MySQL QA, browser desktop/tablet, diff check.

Setiap increment selesai diverifikasi sebelum commit. Push setelah work item
selesai. Pengembangan Sales/Parties memakai dokumen dan increment terpisah.
