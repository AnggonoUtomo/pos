# Tabel Workspace Dan Chart Operasional

Status: selesai; QA desktop/tablet lulus, build lokal aktif dan implementasi
dipush ke origin/main. Koreksi visual lintas module sesuai instruksi user.
Patokan tabel: `/inventory/units`. Referensi widget:
https://react.tailwind-admin.com/.

## Scope Dan Kontrak Visual

- Samakan surface, header, separator, hover/focus dan badge status tabel daftar
  workspace. Header lembut, isi jelas, warna semantik mengikuti pola Satuan.
- Pertahankan theme/accent existing, layout, filter, pagination, permission,
  CRUD dan operasional; tabel struk/cetak serta editor transaksi tidak diubah.
- Dashboard dan Reporting/OperationalReports mendapat widget perbandingan
  transaksi serta komposisi arus pembayaran dari props real existing.
- Tidak membuat seri tren, persentase kenaikan atau kapasitas contoh. Pembayaran
  seluruh gudang tetap diberi label dan bukan saldo/laba. Stok tetap posisi kini.
- Animasi finite saat widget/data masuk, bukan loop. Reduced motion menampilkan
  hasil akhir langsung. Tooltip/keyboard dan angka Indonesia tetap tersedia.
- Tidak memasang dependency; grafik sederhana memakai HTML/CSS/SVG. Props/API,
  query, schema, ledger dan data produksi tidak diubah. Demo seeder tidak perlu.
- Desktop/tablet; QA hanya alur visual berubah, bukan seluruh tema atau CRUD.

## Ownership

Token visual pada app.css; kelas opt-in tabel lintas module. Komponen chart
presentasional dipakai nyata oleh dua halaman sehingga berada di components/
shared. Penyusunan nilai widget Reporting tetap di page module. Tidak membuat
design system baru atau dependency backend lintas module.
