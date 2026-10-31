# Stock Opname Per Gudang

## Scope dan Keputusan

Inventory/Stock memiliki sesi opname, snapshot saldo, hasil hitung satuan
dasar, review supervisor dan posting selisih. User menyetujui penghentian
mutasi stok selama sesi aktif dan harga pokok positif wajib untuk selisih
tambah. Selisih kurang memakai FIFO existing; selisih nol tanpa ledger.

Sesi: counting -> review -> posted, atau cancelled dengan alasan. Review bisa
dikembalikan untuk hitung ulang. Mulai sesi langsung menahan gudang; konfirmasi
UI wajib. Satu sesi aktif per gudang; gudang lain tetap berjalan. Draft dan
pembayaran yang tidak mengubah stok tetap tersedia. Replay posted berhasil
tidak diblokir dan tidak membuat stok/payment baru.

Snapshot mencakup semua pasangan saldo gudang, termasuk nol. Barang tambahan
tanpa saldo boleh ditambahkan eksplisit. Baris belum dihitung bukan angka nol;
seluruh baris wajib diisi sebelum review. Input/harga presisi enam desimal,
tampilan angka Indonesia. Barcode mengidentifikasi barang/satuan, hasil hitung
tetap dalam satuan dasar, bukan auto-menambah kuantitas scan.

Master bersaldo yang nonaktif harus diaktifkan sebelum mulai sesi. Snapshot
nama/satuan disimpan; perubahan master tidak boleh mengubah satuan dasar atau
melewati revalidasi posting. Header sesi diaudit, bukan CRUD soft-delete:
pembatalan menggantikan penghapusan agar jejak penahanan gudang terpelihara.

## Pengaman

Warehouse row lock menjadi urutan serialisasi bersama writer existing.
Guard di create receipt/issue/post opening membaca sesi aktif dengan current
read. Hanya posting sesi yang sama dalam status internal posting boleh lewat;
identifier ini tidak diterima dari endpoint koreksi biasa. Semua adjustment,
FIFO, audit, status dan pelepasan gudang berada dalam satu transaction.

Tidak ada perubahan aturan stok minimum, serial/garansi, Finance atau transaksi
lama. Tidak reset/seed data toko atau mengubah grant akun. Demo opname memakai
database disposable; tidak memulai sesi atau membekukan gudang saat deployment.

## Status

Implementasi, QA dan aktivasi selesai. Delivery dicatat pada
[checklist](tasks.md); [plan](plan.md) tetap menjadi urutan scope.

## Bukti Penutupan

- Backend save point `b1e1f7a`. Suite Stock awal 66 PASS/433 assertion;
  coverage tambahan terakhir StockCountTest 10 PASS/78 assertion.
- Native MySQL disposable PASS: schema, tiga worker terbukti LOCK WAIT,
  snapshot exact/current, replay no-duplicate, release sesudah commit dan
  rollback histori ditolak. Database/worker dibersihkan.
- Regresi penuh sekali: 937 PASS, 5 FAIL/7476 assertion. Tiga failure lifecycle
  karena FK schema additive ditutup dengan allowlist hanya tabel kosong;
  tabel opname berisi sesi tetap memblokir penggantian. Tidak menjalankan
  penggantian dataset toko. Dua failure tanggal retur existing ditutup atas
  izin user lewat [patch terpisah](../return-report-dates/README.md), commit
  `28086eb`. Sesudah patch, seluruh paket terdampak 104 PASS/981 assertion.
  Tidak mengulang seluruh suite yang tidak berubah atau menghapus failure lama.
- Typecheck, ESLint file berubah, build 2938 module, page-guides 29 halaman,
  Pint scoped dan diff whitespace PASS. Tidak memasang dependency baru.
- Browser Chrome/Playwright PASS, console kosong: mulai, scan SKU, base count
  Indonesia, simpan/review, posting selisih kurang/tambah dengan HPP, reload
  readonly, batal, filter, F1 dan akses ditolak. Transaksi sumber dan Finance
  identik. Framing desktop1440/tablet1024, tabel scroll tanpa mengecilkan nama
  barang dan satu menu sidebar aktif. Screenshot diperiksa:
  storage/framework/testing/mvp-browser-02479e0ae585c002 (visual-report.json).
- DevTools namespace tidak tersedia. Cached MCP bin/stdio terhubung, tetapi
  smoke login menunggu heading dashboard sampai batas waktu; BLOCKED khusus
  smoke MCP, bukan diklaim PASS. Fallback Chrome di atas selesai penuh.
- Backup `pos:backup --verify` PASS: restore disposable dan sumber identik;
  artefak private backups/pos-20261031-213100-b434aca4.sql tidak dikomit.
- Aktivasi lokal PASS: migration Ran batch34, tiga permission baru tanpa
  grant/reset/demo, seluruh data/kolom/grant existing identik dan dua tabel
  baru kosong. Sembilan route aktif dalam cache; migration/status tanpa
  Pending. HTTPS anonymous 302 ke login tanpa bypass sertifikat.

Stok minimum, serial/garansi, tempo, pending printer/tablet toko dan jadwal
backup OS tidak dikerjakan. .env, role akun dan Old-docs tidak diubah. Panduan
Markdown/F1 diperbarui; PDF lama tidak dibangun ulang, cakupannya dicatat.
