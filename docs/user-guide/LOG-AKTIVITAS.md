# Memeriksa Log Aktivitas

## Siapa Yang Dapat Membuka

Masuk dengan akun sendiri, lalu pilih **Platform > Log Aktivitas**.
Super-system dapat langsung membuka halaman. Akun lain harus diberi permission
`platform.audit-log.view` oleh administrator lewat Identitas & Akses.
Penambahan fitur ini tidak otomatis mengubah akses role produksi.

## 1. Menemukan Aktivitas

1. Isi **Pencarian** dengan kode/nomor dokumen, ID objek atau nama pengguna.
2. Bila perlu, pilih **Modul**, **Pengguna**, dan **Tindakan**.
3. Isi **Dari tanggal (WIB)** dan **Sampai tanggal (WIB)**. Tanggal akhir
   mencakup seluruh hari tersebut. Kosongkan tanggal untuk melihat semua waktu.
4. Klik **Terapkan**. Gunakan **Reset filter** untuk menghapus semua filter.
5. Ubah **Baris per halaman** atau gunakan nomor halaman untuk melihat hasil
   berikutnya. Mengganti jumlah baris tidak menghapus filter yang sudah diterapkan.

Pencarian membaca nomor/kode yang dicatat pada snapshot, bukan data barang atau
pelanggan terkini. Cari dengan ID objek bila nomor/kode tidak tersedia.

## 2. Membaca Detail

1. Klik ikon mata pada baris aktivitas.
2. Periksa waktu WIB, pengguna, modul, tindakan dan ID objek.
3. Bandingkan **Sebelum** dan **Sesudah** pada field yang tersedia.
4. **Tidak dicatat** berarti nilai itu tidak tersedia dalam audit. **Kosong**
   berarti nilai kosong memang dicatat. Jangan menganggap keduanya sama.
5. Klik **Tutup** setelah selesai. Bila permintaan gagal, gunakan **Muat ulang
   detail**; tindakan ini hanya membaca ulang dan tidak mengulangi transaksi.

**Sistem** berarti tidak ada pengguna pelaku yang dicatat. **Pengguna tidak
tersedia** berarti ID pelaku tercatat tetapi akun tidak ditemukan. Nama pelaku
yang masih tersedia mengikuti nama akun saat ini, bukan snapshot nama historis.
Objek yang dihapus tetap bisa ditelusuri selama catatan auditnya masih ada.

## 3. Pemeriksaan Lintas Modul

Contoh: bila stok berubah, cari modul Stok atau Pembelian dan cocokkan nomor/ID
dokumennya. Lalu periksa **Kartu stok** untuk pergerakan barang. Untuk uang,
periksa **Pembayaran** atau **Buku Kas**. Audit menjelaskan aktivitas yang
dicatat; saldo resmi tetap berasal dari ledger modul tersebut.

Halaman ini tidak menyediakan edit, hapus atau ekspor log. Informasi sensitif
dan field di luar daftar aman tidak ditampilkan. Tidak semua aktivitas memiliki
nilai sebelum/sesudah; fitur ini tidak membuat riwayat yang belum pernah dicatat.

## Shortcut

- `Alt+F`: fokus pencarian.
- `Ctrl+Enter` atau `Cmd+Enter`: terapkan filter.
- `Esc`: tutup detail.
- `F1`: buka/tutup panduan halaman.
- `Ctrl+K` atau `Cmd+K`: cari menu Log Aktivitas melalui command palette.

Panduan ini belum digabungkan ke PDF global/per-role versi sebelumnya.
