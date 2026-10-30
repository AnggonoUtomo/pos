# Penyegaran Area Pencarian Dan Filter

Status: implementasi.

## Scope

Perbaikan visual lintas module pada toolbar pencarian master (shared
MasterWorkspace), Identity, Stock, Transfers, pembelian, retur penjualan dan
pembelian, riwayat invoice/pembayaran, Buku Kas, laporan dan katalog POS.
Pencarian sumber retur dan pilihan akses di dialog mengikuti pola compact.

## Kontrak Visual

- Gunakan token tema existing; tint tipis pada bidang pencarian, garis aksen,
  field lebih jelas, jarak konsisten dan focus keyboard tetap terlihat.
- Area filter menjadi band operasional, bukan kartu dekoratif atau kartu bertumpuk.
- Light tetap terang dan tenang; Dark mengikuti surface dan accent tema aktif.
- Desktop/tablet tidak overflow; tombol pencarian tidak menyusut.
- Tidak mengubah handler, parameter URL, debounce, shortcut, permission,
  pagination, data transaksi, timezone ataupun .env.
- Tidak menambah dependency, tema atau design system baru.

Panduan operator tidak berubah karena operasi dan langkah penggunaan tetap sama.
Verifikasi terfokus dan bukti browser dicatat saat penutupan.

## Verifikasi Increment 1

- `npx tsc --noEmit`: PASS.
- ESLint scoped seluruh TSX berubah: PASS (tanpa --fix global).
- `npm run build`: PASS, 2924 module.
- `node scripts/qa/page-guides.mjs`: PASS, 26 halaman.
- Handler/query/permission tidak berubah; diff hanya class dan CSS presentasi.
- Tidak ada migration atau kebutuhan seeder karena tidak menambah data/fitur.
