# Dua Tampilan Workspace POS

Status: selesai, verifikasi terfokus lulus; hasil pada [checklist](tasks.md).

Permintaan 28 Oktober 2026: pertahankan tampilan Klasik dan tambahkan Visual
berdasarkan referensi user (katalog bergambar di kiri, keranjang di kanan).
Keduanya memakai state dan operasi transaksi yang sama, tanpa navigasi ulang.
Visual menjadi pilihan awal; preferensi tampilan disimpan lokal di browser.

Gudang Utama dan pelanggan berlevel Eceran dipilih saat pertama membuka POS
jika masing-masing dapat diidentifikasi secara unik dari pilihan aktif.
Jika ambigu/tidak tersedia, kasir harus memilih; tidak mengambil data acak.
Recall keranjang dan recovery pembayaran tetap mengalahkan default awal.

Scope hanya UI dan pilihan awal. Tidak mengubah permission, schema, stok,
FIFO, matrix harga, checkout, seed, atau data toko. Tema existing dipertahankan.

Referensi gambar bukan instruksi menambahkan kategori restoran, kredit, atau
metode pembayaran baru. QA desktop/tablet menggunakan database disposable.

## Catatan Tertunda

- UAT scanner fisik dan cetak label/printer fisik masih pending, bukan lulus
  hanya karena simulasi keyboard berhasil.
- Advisory npm existing `qs` GHSA-6pq8-jhm2-2vcw (moderate) dicatat pada
  work item barcode-scanner; tidak diperbarui dalam pekerjaan UI ini.
