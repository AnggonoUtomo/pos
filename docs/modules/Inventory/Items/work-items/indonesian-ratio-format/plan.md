# Rencana Format Rasio

1. Formatter/parser lokal fitur dan regresi exact: nol akhir, grouping,
   koma, maksimum precision, serta penolakan input ambigu/tidak valid.
2. Form menampilkan angka lokal, memvalidasi format lalu mengirim canonical
   decimal string. Form gagal tidak kehilangan isian lokal. Base unit tetap 1.
3. Lint/typecheck/build, focused backend regression dan browser desktop/tablet
   untuk create/edit/reopen serta invalid ratio. Commit/push setelah lulus.
