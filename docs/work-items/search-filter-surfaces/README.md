# Penyegaran Area Pencarian Dan Filter

Status: selesai, tampilan dan QA browser terfokus lulus.

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

## QA Dan Penutupan

- Discovery Chrome DevTools MCP: namespace tidak tersedia. Percobaan executable
  existing melalui stdio isolated berhenti pada timeout initialize (45 detik).
  Tidak menginstal paket atau meminta restart editor; fallback sesuai QUALITY.
- `QA_SEARCH_SURFACES=1` + QA_PLAYWRIGHT_MODULE existing pada
  `node scripts/qa/workspace-tables-charts-browser.mjs`: PASS.
- 19 workspace diperiksa: seluruh consumer MasterWorkspace, Identity,
  Stock/Transfers, pembelian/retur, riwayat penjualan/pembayaran, Buku Kas,
  laporan dan POS. Band/field/tombol mengikuti token serta batas viewport.
- Alt+F, fokus keyboard/aksen, Enter search, hasil kosong/reset, pagination,
  apply/reset riwayat PASS. Light desktop1440 dan tablet1024 Light/Dark
  representatif PASS. Screenshot sudah ditinjau; console error kosong.
- Bukti ignored: `storage/framework/testing/mvp-browser-1515b706b1e37501/`
  (`visual-report.json` dan screenshot). Browser/server QA sudah ditutup;
  fingerprint ledger identik sebelum/sesudah pengujian.
- Style ditempatkan pada layer utilities existing agar tidak dikalahkan
  utility bg-background/border-input shadcn. Focus ring shadcn tetap utuh.
- Build akhir setelah koreksi cascade: PASS, 2924 module. ESLint QA scripts
  dan form dialog yang dikembalikan format existing-nya: PASS. Typecheck dan
  panduan dari source semantik yang sama dipakai kembali, tidak diulang.
- Review scope: perubahan runtime hanya CSS/class/warna icon; formatting churn
  dialog dibuang sehingga diff akhir pada dialog hanya satu class pencarian.
- Backend/full suite/MySQL tidak diulang untuk presentasi saja. Gap filter
  tanggal invoice tetap terpisah; tidak diklaim diperbaiki atau lulus di sini.
- .env (termasuk Asia/Jakarta), database toko, permission, transaksi, Old-docs
  dan file LSP user tidak diubah. Tidak menjalankan migration/seeder atau runner OS.

Commit increment awal `284189c`; QA/koreksi cascade dan penutupan disimpan
sebagai save point berikutnya. Push dilakukan setelah keseluruhan scope selesai.
