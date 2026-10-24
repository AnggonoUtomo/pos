# Tasks: MVP Siap Operasional Toko

## Sebelum Mulai

- [x] AGENTS.md, README, arsitektur dan struktur canonical dibaca.
- [x] Empat paket dan batas MVP langsung lunas disetujui user.
- [x] QA terfokus, reuse bukti lulus, dan scope freeze ditetapkan.
- [x] Perubahan user yang staged di SampleUI/storage diidentifikasi dan dikecualikan.

## Paket 1: Operasional Stok

- [x] Posting transfer penuh antar-gudang melalui public contract Stock.
  - Acceptance: stok asal berkurang FIFO, tujuan bertambah dengan biaya sama;
    status, ledger dan audit atomik; tidak minus/duplikasi posting.
  - Verification: test integrasi sukses, kurang stok, rollback, idempotensi,
    permission, biaya; migration/status dan browser alur posting.
- [x] Koreksi stok minimum dengan alasan dan permission khusus.
  - Acceptance: selisih masuk/keluar masuk ledger/FIFO, posted immutable,
    audit wajib, kuantitas/nilai tetap konsisten.
  - Verification: test terfokus dan browser alur koreksi.
- [x] Gate paket lulus, catatan aktivasi, commit dan push diverifikasi.

## Paket 2: Transaksi POS

- [x] Penerimaan dan pelunasan pembelian satu konfirmasi atomik.
  - Acceptance: alur utama draft -> diterima/lunas; pembayaran gagal tidak
    meninggalkan stok/pembelian posted tanpa uang. Dokumen legacy belum dibayar
    tetap dapat dilunasi, tanpa dianggap utang baru.
  - Verification: sukses, replay, rollback pembayaran dan dua permission.

- [x] Diskon persen/nominal dan pajak server-authoritative.
  - Acceptance: subtotal, pembulatan, total dan pembayaran konsisten;
    harga level/satuan, permission dan stok tidak boleh dilewati.
  - Verification: test perhitungan/checkout/tampering/rollback terfokus.
- [x] Struk, detail invoice dan retur/refund mengikuti snapshot nilai bersih.
  - Acceptance: refund tidak melebihi penjualan setelah diskon/pajak;
    sisa pembulatan terjaga.
  - Verification: test retur dan browser transaksi lengkap.
- [x] Gate paket lulus, commit dan push diverifikasi.

## Paket 3: Administrasi Toko

- [x] Pengaturan identitas toko/struk dengan permission dan audit.
  - Acceptance: input tervalidasi; struk baru memakai snapshot identitas toko.
  - Verification: test update/permission/snapshot dan browser.
- [x] Laporan operasional minimum dari data nyata.
  - Acceptance: ringkasan penjualan, pembelian, retur, stok dan pembayaran;
    filter tanggal/gudang sesuai arti data; bukan klaim saldo bank/laba bersih.
  - Verification: fixture relasional, total/refund/filter/permission.
- [x] Gate paket lulus, commit dan push diverifikasi.

## Paket 4: Persiapan Penggunaan

- [x] Aktivasi aman tanpa reset data, grant berlebih, atau demo transaksi otomatis.
- [x] Backup dan restore database teruji pada database disposable.
- [x] Konfigurasi produksi, build assets dan keamanan dasar diperiksa.
  - Produksi belum diaktifkan pada environment local; readiness --production
    sengaja menolak debug/local/HTTP dan identitas toko kosong. Ini pending
    konfigurasi deploy/operator, bukan bukti produksi sudah berjalan.
- [x] Skenario otomatis desktop/tablet: master -> beli/lunas -> stok -> jual/lunas
  -> retur -> uang kembali -> transfer/koreksi -> laporan/struk.
- [x] Blocker teknis ditutup; kebutuhan printer/perangkat dan persetujuan operator dicatat.
- [x] Patch dependency existing sesuai persetujuan user, tanpa major/dependency baru.
  - Verification: Vite6.4.3 ->6.4.5, follow-redirects1.16.0 ->1.16.1;
    npm audit penuh/production0, Composer0; lint/typecheck/build dan browser PASS.
- [x] Gate final sekali, commit/push diverifikasi; tidak mengklaim UAT manusia
  selesai tanpa pelaksanaan operator.

## Bukti Verifikasi

Paket stok: test terfokus baru 9/71 PASS; regresi backend 783/6194 PASS.
Typecheck, ESLint Stock/Transfers, build2751 dan Pint scoped PASS. Browser
operasional DevTools PASS: transfer posted, koreksi keluar 0,5, ledger, screenshot
desktop1440/tablet1024 dan console. Bukti ignored pada
storage/framework/testing/transfer-draft-browser-a9c4e904f019871a. Aktivasi lokal
batch27 sudah PASS. Savepoint source 132477c; guard transfer kosong/replay stale
cbb52a1 dengan focused5/45 PASS.

Paket POS: focused POS/Returns 87/694 PASS; sesudah tambahan DTO snapshot,
checkout16/153 PASS. Tambahan receive-and-pay7/53 PASS. Savepoint f38430d,
f9c82c5 dan 29bc3e8. Test client Node meliputi diskon/pajak nol, locale, negatif
pecahan kecil di laporan, recovery payload pricing, payment dan ULID PASS.

Regresi gabungan backend `php artisan test --compact`: 795/6266 PASS. Sesudahnya
behavior backend hanya berubah pada guard transfer dan readiness;
focused transfer5/45 dan readiness1/3
PASS, bukan mengulang seluruh suite. Backup CLI unsupported SQLite1/2 PASS.
Fixture filter laporan menggunakan tanggal WIB agar stabil saat pergantian hari;
focused laporan1/11 PASS sesudah pergantian hari, tanpa perubahan perhitungan.
ESLint resources/js, typecheck, Pint scoped dan build Vite6.4.5/2754 module PASS.
Review source dan screenshot desktop/tablet PASS; tidak mengulang matrix tema.

`php scripts/qa/mvp-integrity-mysql.php`: database MySQL disposable dengan nama
acak/actual connection guard, migrate lengkap, fixture alur beli -> jual diskon/
pajak -> retur/refund kedua arah -> transfer -> koreksi -> laporan PASS.
7 movement; invoice199800; refund pelanggan99900; penerimaan supplier120000;
nilai stok900000; selisih arus uang-980100. Semua exact, source/grant toko bukan
target fixture; database dibersihkan. Bukan klaim uji race worker tambahan.

`php scripts/qa/activate-mvp-local.php`: lima migration additive batch27 dan
katalog permission PASS. Fingerprint kolom/row existing, pivot/grant dan ledger
tetap identik; tanpa reset/password/demo/posting/grant otomatis.
`php artisan migrate:status`: seluruh migration Ran.

`php artisan pos:backup --verify`: dump private, restore ke MySQL disposable,
fingerprint sumber/restored identik dan cleanup PASS sebelum/sesudah aktivasi.
Backup/artefak QA diabaikan Git. `php artisan pos:readiness`: semua cek runtime
lokal PASS termasuk identitas toko dan rekonsiliasi FIFO/no-negative. Mode
--production secara benar menolak environment local/debug/HTTP; konfigurasi
produksi tidak diubah otomatis.

`node scripts/qa/mvp-checkout-devtools.mjs`: Chrome DevTools MCP executable
existing/isolated, fixture SQLite disposable, credential random stdin PASS.
Pengaturan toko -> terima dan bayar -> POS diskon10%/pajak11% -> total199800 ->
receipt snapshot -> laporan. Screenshot desktop1440/tablet1024, console bersih,
DB assertion invoice1/pembelian lunas2. Bukti ignored:
storage/framework/testing/mvp-browser-97aba51144c0e504. Toast quote yang semula
menutupi Pembayaran dipindah ke kanan atas dan alur diuji ulang sampai selesai.

Sesudah label navigasi dipendekkan menjadi Pengaturan toko, hanya
`--navigation-only` yang diulang: batas teks/badge tablet tidak bertumpuk,
tanpa mengulang workflow checkout di UI. Bukti mvp-browser-6b6705fd47e07ed8.

## Pengiriman Git

Implementasi, patch dependency, QA dan panduan operasional sudah dikirim ke
`origin/main` sampai `12ad441`. Push berhasil; HEAD lokal cocok dengan
`refs/heads/main` remote dan divergence `0 0`. Checklist pengiriman diperbarui
sesudah verifikasi tersebut, bukan sebelum push. Perubahan staged milik user
di SampleUI/storage tidak dimasukkan; Old-docs tidak disentuh.

## Pending Operator / Go-Live

- [ ] Persetujuan UAT pemilik/operator dengan data dan perangkat toko nyata.
- [ ] Printer fisik/lebar kertas dan prosedur cetak diverifikasi operator.
- [ ] Penanggung jawab, jadwal dan salinan backup terpisah ditetapkan.
- [ ] Konfigurasi production/HTTPS/debug/cookie pada server tujuan diverifikasi.

Rincian [OPERASIONAL.md](OPERASIONAL.md). Pending manusia/deploy ini tidak
ditandai selesai hanya karena QA automated lulus. Tidak menambah fitur baru.
