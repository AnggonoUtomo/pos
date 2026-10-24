# MVP Siap Operasional Toko

Scope dikunci sesuai persetujuan user: toko CCTV/kelistrikan, single company,
multi gudang, multi satuan, harga level pelanggan, FIFO, stok tidak minus,
penjualan dan pembelian langsung lunas. Tidak menambah fitur di luar empat
paket pada checklist sebelum alur ini dapat dipakai.

Urutan eksekusi: stok, transaksi POS, administrasi toko, kesiapan penggunaan.
Pekerjaan yang selesai tetap memakai implementasi existing, bukan ditulis ulang.
Satu checklist ini menjadi acuan progres lintas module.

## Status

Implementasi empat paket dan QA automated selesai; siap uji operasional lokal.
Migration MVP sudah aktif tanpa reset/grant/demo posted otomatis. Persetujuan
go-live, perangkat/printer nyata, dan konfigurasi deploy produksi tetap milik
operator/admin, belum dianggap selesai oleh agent. Fitur tambahan tetap
dibekukan sampai UAT pada [panduan operasional](OPERASIONAL.md) disetujui.

Pengecualian eksplisit dari user: module
[Pengaturan Operasional](../../modules/Platform/OperationalSettings/README.md)
untuk opsi struk/backup. Implementasi aktif lokal; UAT printer fisik, tablet,
salinan backup terpisah dan konfigurasi production belum dianggap selesai.

## Batas MVP

- Transfer penuh satu posting atomik; tidak ada barang dalam perjalanan.
- Koreksi stok dengan alasan wajib, permission khusus, dan ledger/audit.
- Diskon persen/nominal dan pajak eksplisit; tarif tidak diasumsikan dari aturan
  perpajakan. Harga transaksi dan basis retur mengikuti snapshot server.
  MVP memakai diskon tingkat nota, lalu pajak tambahan persen atas nilai bersih.
  Tarif 0..100%, dua desimal; diskon tidak boleh menghabiskan seluruh subtotal.
  Alokasi diskon kumulatif proporsional dibulatkan turun per baris dengan sisa
  terjaga; pajak per baris HALF_UP dua desimal. Total header adalah jumlah baris.
  Nilai request, tipe diskon dan tarif disimpan; refund memakai nilai bersih
  baris asal, bukan harga master. Belum pajak inklusif/multi-tarif/gratis.
- Pengaturan identitas toko/struk dan laporan operasional minimum.
- Piutang/utang, pembayaran parsial, serial number, garansi, approval, multi
  cabang, akuntansi penuh, ekspor lanjutan, dan kosmetik tema ditunda.

## QA Hemat

Test terfokus selama coding; regresi lengkap sekali per paket. Ulang hanya gate
yang terdampak perubahan baru. Browser memeriksa alur utama desktop/tablet,
permission/error penting, bukan seluruh kombinasi tema. Catat command dan
hasil ringkas pada tasks.md. Jangan menyatakan siap produksi tanpa bukti
backup/restore dan persetujuan uji operasional pemilik/operator.
