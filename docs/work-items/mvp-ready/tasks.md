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
- [ ] Gate paket lulus, catatan aktivasi, commit dan push diverifikasi.

## Paket 2: Transaksi POS

- [x] Diskon persen/nominal dan pajak server-authoritative.
  - Acceptance: subtotal, pembulatan, total dan pembayaran konsisten;
    harga level/satuan, permission dan stok tidak boleh dilewati.
  - Verification: test perhitungan/checkout/tampering/rollback terfokus.
- [x] Struk, detail invoice dan retur/refund mengikuti snapshot nilai bersih.
  - Acceptance: refund tidak melebihi penjualan setelah diskon/pajak;
    sisa pembulatan terjaga.
  - Verification: test retur dan browser transaksi lengkap.
- [ ] Gate paket lulus, commit dan push diverifikasi.

## Paket 3: Administrasi Toko

- [ ] Pengaturan identitas toko/struk dengan permission dan audit.
  - Acceptance: input tervalidasi; struk baru memakai snapshot identitas toko.
  - Verification: test update/permission/snapshot dan browser.
- [ ] Laporan operasional minimum dari data nyata.
  - Acceptance: ringkasan penjualan, pembelian, retur, stok dan pembayaran;
    filter tanggal/gudang sesuai arti data; bukan klaim saldo bank/laba bersih.
  - Verification: fixture relasional, total/refund/filter/permission.
- [ ] Gate paket lulus, commit dan push diverifikasi.

## Paket 4: Persiapan Penggunaan

- [ ] Aktivasi aman tanpa reset data, grant berlebih, atau demo transaksi otomatis.
- [ ] Backup dan restore database teruji pada database disposable.
- [ ] Konfigurasi produksi, build assets dan keamanan dasar diperiksa.
- [ ] Skenario operator desktop/tablet: master -> beli/lunas -> stok -> jual/lunas
  -> retur -> uang kembali -> transfer/koreksi -> laporan/struk.
- [ ] Blocker ditutup; kebutuhan printer/perangkat dan persetujuan operator dicatat.
- [ ] Gate final sekali, commit/push diverifikasi; tidak mengklaim UAT manusia
  selesai tanpa pelaksanaan operator.

## Bukti Verifikasi

Paket stok: test terfokus baru 9/71 PASS; regresi backend 783/6194 PASS.
Typecheck, ESLint Stock/Transfers, build2751 dan Pint scoped PASS. Browser
operasional DevTools PASS: transfer posted, koreksi keluar 0,5, ledger, screenshot
desktop1440/tablet1024 dan console. Bukti ignored pada
storage/framework/testing/transfer-draft-browser-a9c4e904f019871a. Belum aktivasi
migration lokal/push paket. Savepoint source 132477c.

Paket POS: focused POS/Returns 87/694 PASS; sesudah tambahan DTO snapshot,
checkout16/153 PASS. Typecheck, ESLint POS dan Pint PASS. Browser transaksi baru
dan build final menunggu gabungan dengan identitas toko (tidak mengulang tema).
