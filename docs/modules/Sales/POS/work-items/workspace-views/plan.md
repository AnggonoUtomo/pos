# Rencana

1. Tetapkan pilihan awal yang aman dan test terfokus; dokumentasikan scope.
2. Tambahkan switch Klasik/Visual tanpa mengganti pemilik state transaksi.
   Visual memakai tile gambar, context ringkas, dan katalog/keranjang sejajar
   pada tablet. Layar lebih sempit memakai pilihan panel, bukan membagi tinggi
   menjadi dua panel yang terpotong. Klasik tetap memakai kartu daftar.
3. QA default, penggantian view tanpa kehilangan input/quote, scanner,
   hold/recall dan dialog pembayaran; periksa desktop/tablet, lint/type/build.
   Tutup checklist berdasarkan bukti, commit lalu push setelah paket selesai.

4. Koreksi lanjutan dari user: autocomplete pelanggan pada kedua view. Gunakan
   primitive aksesibel existing dan styling shadcn, filter lokal daftar aktif
   yang sudah diberikan backend; keyboard, hasil kosong, batal dan harga diuji
   terfokus tanpa mengulang seluruh paket QA sebelumnya.
