# Matriks Dampak Dan Alternatif Integrasi

Scope organisasi: single company, multi-gudang, tanpa tenancy/cabang.
Semua path relatif root POS. Kolom integrasi kelak merupakan usulan, bukan
allowlist implementasi task ini.

| Surface | Kondisi sekarang | Usulan paling sedikit dampak | Izin yang dibutuhkan |
| --- | --- | --- | --- |
| Checkout/FIFO/Payments | Atomic dan idempotent | Jangan diubah; kegagalan cetak tidak membatalkan penjualan | Perubahan bisnis tidak diajukan |
| Master item-unit/barcode | Items memiliki barcode unik dan generator Code128 | Baca melalui published contract; jangan buat identitas produk baru | PLAN read boundary ketika slice label diajukan |
| Scan POS/pembelian | Input fokus/Enter sudah tersedia | Uji kompatibilitas scanner melalui harness terisolasi; gunakan scan existing tanpa listener global baru | Tidak ada perubahan sekarang |
| Cetak label browser | A4/label tunggal, preview JsBarcode | Pertahankan fallback; CT420D memakai adapter label terpisah kelak | Integrasi tombol pada dialog existing harus exact-file dan disetujui |
| Receipt | Snapshot posted + window.print | Tetap seperti sekarang; jangan gunakan CT420D sebagai receipt printer | Auto-print bukan bagian task ini |
| Auth | Session web/CSRF, permission dan ownership | Reuse backend; gateway kelak credential terpisah per perangkat/assignment | Permission baru/grant/redeem butuh izin terpisah |
| Organisasi | Single company/multi-gudang | Terminal/printer/job milik instalasi toko; gudang hanya konteks stok bila relevan | Tidak menambah tenant_id/branch_id/company_id global |
| UI global | Sidebar, palette, theme, PageGuide | Mockup terpisah; shell baru kelak reuse komponen existing | Menu/palette butuh izin masing-masing path |
| Preferensi | OperationalSettings mengatur paper struk | Feature hardware OFF melalui config module kelak; jangan tambah field wajib ke form operasional existing | Config/route activation harus PLAN baru |
| Database | MySQL; ULID utama; module migrations | Tidak ada DDL sekarang. Registry/job/attempt baru hanya bila consumer disetujui | Backup + izin migrasi, bukan persetujuan dokumen ini |
| OS/deployment | Laravel scheduler diurus admin | Tidak ada runner, driver, port lokal, service/gateway nyata pada fase virtual | Instalasi/aktivasi eksplisit kelak |
| Audit | Activitylog bukan ledger | Audit hardware saja ketika ada mutation; jangan log token/raw payload sensitif | PLAN slice terkait |

## Pilihan Yang Dibandingkan

| Pilihan | Dampak dan batas | Rekomendasi |
| --- | --- | --- |
| A. Pertahankan cetak browser dan scanner HID existing | Tidak mengubah checkout; kemampuan driver/hasil kertas tetap perlu UAT | Baseline tetap dipertahankan |
| B. Workspace hardware terpisah, flag OFF, virtual-first | Harness fake membaca fixture, preview label, simulasi gateway; tidak memerlukan perangkat nyata | Arah pengembangan setelah PLAN implementasi disetujui |
| C. Ubah checkout agar langsung membuat print job | Mengubah critical transaction surface, recovery dan regression blast radius | Tidak diajukan untuk TASK-HW-001 |
| D. Browser langsung USB/Bluetooth atau pasang OS runner | Dukungan/izin/perangkat belum diverifikasi; raw command dan deployment berisiko | Tidak dipilih; tidak memasang apa pun |

## Exact-File Surface Existing Yang Memerlukan Izin Bila Kelak Dipilih

| File | Perilaku sebelum | Perubahan yang mungkin diajukan | Status saat ini |
| --- | --- | --- | --- |
| `resources/js/components/app-sidebar.tsx` | Tidak ada menu hardware | Menu baru saat flag ON dan user berizin | Tidak diizinkan/disentuh |
| `resources/js/components/command-palette.tsx` | Tidak ada navigasi hardware | Navigasi hardware terjaga flag/permission | Tidak diizinkan/disentuh |
| `resources/js/pages/inventory/items/components/barcode-label-dialog.tsx` | Cetak browser saja | Pilihan eksplisit dispatch label setelah preview | Tidak diizinkan/disentuh |
| `app/Modules/Platform/Identity/Database/Seeders/IdentityPermissionsSeeder.php` | Catalog/grant existing | Hanya bila pola module access seeder tidak cukup; alternatif seeder milik module baru lebih kecil dampaknya | Tidak diizinkan/disentuh |

Tidak ada kebutuhan mengubah AppServiceProvider karena discovery provider
module sudah tersedia. Jangan mengimpor controller/repository/model privat
module lain untuk jalan pintas. Public contract pembacaan label yang belum
cukup harus diusulkan terpisah, bukan ditambahkan diam-diam.

## Flag OFF Dan Kegagalan

Desain kelak: `pos_hardware.enabled = false` secara default di config milik
module. Nama masih usulan; config belum dibuat. Tidak ada switch tenant/cabang.
Flag OFF berarti tidak ada menu/request/listener/hotkey/job/polling/IO hardware;
login, scan, checkout dan window.print existing tetap berjalan. Endpoint hardware
kelak juga harus menolak akses langsung ketika OFF, termasuk super-system:
flag/capability/invariant tidak boleh hanya dijaga Gate yang bisa dibypass.

Print gagal sebelum send boleh retry terbatas sesuai slice yang disetujui.
Timeout sesudah kemungkinan send menjadi `uncertain`, tanpa retry otomatis.
`sent`/`accepted` bukan bukti fisik tercetak. Semua status hardware terpisah
dari status pembayaran dan posted invoice.

## Perubahan Yang Tidak Memiliki Izin Saat Ini

Seluruh source POS, root AGENTS/docs aktif, .env/config/cache, database,
dependency/lockfile, route/provider, permission/grant, deployment, gateway
service dan perangkat fisik. Allowlist dokumen saja ada pada [PLAN](PLAN.md).
