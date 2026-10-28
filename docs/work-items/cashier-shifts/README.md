# Shift Kasir

Status: implementasi. Ownership Sales/POS, public boundary ke Finance/Payments.

## Keputusan User

- Satu kasir satu shift aktif; satu akun kas/laci satu shift aktif.
- Semua checkout baru wajib shift, termasuk non-tunai dan super-system.
  Retry penjualan yang sudah committed tetap replay sebelum pemeriksaan shift.
- Tunai hanya memakai metode yang mengarah ke akun kas/laci shift tersebut.
- Modal awal = snapshot saldo Buku Kas; bukan pemasukan atau transfer baru.
  Penambahan modal memakai transfer Buku Kas sebelum membuka shift.
- Selisih saat tutup diperbolehkan dengan alasan wajib dan pending review.
  Shift ditutup/laci dilepas; tidak membuat koreksi uang otomatis.

## Batas Implementasi

Shift baru memerlukan akun cash aktif dengan Buku Kas terinisialisasi. Modal
dan saldo seharusnya dibaca melalui contract/DTOs Finance, bukan model/repository
privat. Saldo minus tetap peringatan sebagaimana Buku Kas, bukan pengaman dana.
Saldo tutup merupakan snapshot seluruh pergerakan akun laci (termasuk biaya,
setoran/refund dari luar POS), bukan penjualan kasir saja. Penjualan tunai dan
non-tunai shift diringkas terpisah; uang non-tunai tidak menambah uang laci.

Kasir menghitung fisik; selisih = fisik - seharusnya, exact6. Hasil tutup tidak
ditulis ulang setelah mutasi/backdate berikutnya. Review hanya catatan dan
identitas pemeriksa; bukan edit hitungan atau approval mutasi Buku Kas.
Permission review eksplisit; tidak menerapkan aturan empat-mata yang belum
diminta. Tidak ada force-close, pengalihan kasir, pembukaan ulang shift, shift
otomatis berdasarkan jam, setoran/adjustment otomatis, atau perubahan struk lama.

Kasir pemegang invoices.post mengoperasikan shift sendiri tanpa grant baru.
Permission shifts.view untuk semua riwayat dan shifts.review untuk review.
Invariant kepemilikan buka/tutup tetap berlaku bagi super-system.
Invoice lama nullable shift tanpa backfill. Client checkout baru menyertakan
shift_id untuk mencegah pending payment berpindah ke shift lain. API internal
tanpa shift_id tetap harus punya shift aktif dan atomik terhubung ke shift itu.

Demo/fixture disesuaikan agar memakai shift nyata, tanpa bypass environment.
Tidak menjalankan reset/seed operational atau mengisi modal/shift produksi.
Old-docs dan file LSP user tidak disentuh. Desktop/tablet; bukan QA mobile.
