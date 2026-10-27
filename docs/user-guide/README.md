# Panduan Pengguna POS Modular ERP-Lite

Panduan pemula untuk toko CCTV dan kelistrikan. Disusun 27 Oktober 2026 dari
fitur yang tersedia, bukan daftar fitur yang akan dibangun.

## Mulai Dari Sini

1. Baca [alur global](01-alur-global.md) untuk memahami urutan pekerjaan.
2. Ikuti [panduan per modul](02-panduan-modul.md) sesuai tahap yang dikerjakan.
3. Pelajari [pembagian role dan akses](03-role-dan-akses.md).
4. Gunakan lembar tugas role masing-masing pada tabel berikut.

Panduan tambahan: [Gambar dan barcode barang](GAMBAR-BARANG.md). Tambahan ini
terpisah dari PDF global versi sebelumnya.

[Unduh buku global PDF](../../output/pdf/user-guide/panduan-global-pos.pdf).
PDF global berisi alur global, panduan modul, serta role/akses. Gunakan daftar
isi dan pencarian pembaca PDF. Versi Markdown merupakan sumber penyusunan PDF.

## Panduan Per Role

| Tugas | Markdown | PDF |
| --- | --- | --- |
| Administrator sistem | [Panduan](roles/administrator-sistem.md) | [PDF](../../output/pdf/user-guide/administrator-sistem.pdf) |
| Pemilik / supervisor | [Panduan](roles/pemilik-supervisor.md) | [PDF](../../output/pdf/user-guide/pemilik-supervisor.pdf) |
| Admin data master | [Panduan](roles/admin-master.md) | [PDF](../../output/pdf/user-guide/admin-master.pdf) |
| Kasir | [Panduan](roles/kasir.md) | [PDF](../../output/pdf/user-guide/kasir.pdf) |
| Staf gudang | [Panduan](roles/staf-gudang.md) | [PDF](../../output/pdf/user-guide/staf-gudang.pdf) |
| Staf pembelian | [Panduan](roles/staf-pembelian.md) | [PDF](../../output/pdf/user-guide/staf-pembelian.pdf) |
| Staf keuangan | [Panduan](roles/staf-keuangan.md) | [PDF](../../output/pdf/user-guide/staf-keuangan.pdf) |

Selain `super-system`, nama profil role di panduan adalah rekomendasi. Nama
yang sama belum tentu sudah dibuat atau diberi izin pada aplikasi toko.
Penyusunan panduan ini tidak mengubah role/akun/permission database.

## Cara Memakai Panduan

- Latihan hanya pada lingkungan pelatihan yang disiapkan administrator, bukan
  dengan membuat transaksi coba-coba pada data toko produksi.
- Nama tombol mengikuti aplikasi. Ikon tanpa teks dapat dikenali dari tooltip
  saat pointer diarahkan ke ikon. Tombol dapat tidak muncul karena izin/status.
- Setiap tahap menjelaskan prasyarat, langkah, hasil dan pemeriksaan sebelum
  lanjut. Jangan melakukan posting hanya untuk mencoba tombol.
- Panduan ini bukan bukti printer fisik, tablet jaringan, UAT operator atau
  backup terpisah sudah disahkan; lihat [operasional](../work-items/mvp-ready/OPERASIONAL.md).
- Fitur masa depan berada di [backlog](../BACKLOG.md), bukan prosedur aktif.

## Pemeliharaan Dokumen

Jika label/alur berubah, ubah Markdown dan bangun ulang PDF, lalu periksa hasil
render. Command dan bukti verifikasi ada pada
[work item panduan](../work-items/user-guide/README.md).
