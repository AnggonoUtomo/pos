# Spesifikasi Perangkat POS

## Workspace Label Dan Gateway

TASK-HW-012 menambahkan GET /platform/hardware (Inertia index), GET /catalog,
CRUD/status/restore /printers, GET/POST /labels serta POST
/labels/{job}/dispatch, /outcome dan /reprint. Route scanner di bawah tetap
mempertahankan contract semula. Semua route memakai web/auth/verified dan
feature middleware; controller HasMiddleware membatasi can per action.

- Workspace/history: platform.hardware.view; history own, atau semua jika manage.
- Profil: platform.hardware.manage. Host hanya dari deployment allowlist,
  ukuran20..100 x20..150 mm, DPI203, portrait/landscape dan optimistic version.
- Katalog/create: platform.hardware.print + inventory.view. Katalog public
  ItemLabelCatalog mencakup satuan aktif berbarcode termasuk satuan beli.
- Dispatch/outcome: platform.hardware.print dan creator job. Reprint juga
  memerlukan platform.hardware.reprint serta owner atau manage.
- Create/dispatch/reprint hanya saat printing_enabled ON; outcome/cancel job
  lama tetap diterima saat pengiriman OFF, selama module enabled.
- Catalog dibatasi120 request/menit; create/dispatch/reprint30 request/menit.
  Mutasi JSON memakai CSRF; field asing ditolak. Tidak ada raw command endpoint.

Tabel hardware_printers (ULID, version, JSON specification, soft delete),
hardware_label_jobs (ULID, creator+idempotency unique, hash request, immutable
snapshot/printer snapshot, copies1..100, original_job_id/reason/status) dan
hardware_print_attempts (ULID, unique job, claim hash/expiry/outcome) dimiliki
module. Jobs/attempts tidak soft delete; profil ber-job queued/sending tidak
boleh diedit/nonaktif/diarsipkan. Audit memakai whitelist tanpa token/raw PNG.

Same key + request sama mengembalikan job sama, payload berbeda409. Queued
hanya dapat diklaim sekali dengan token acak yang disimpan sebagai hash,
berlaku2 menit. Submitted/failed_before_send/uncertain adalah hasil akhir;
ack hilang atau timeout setelah print tidak boleh auto-retry. Reprint membuat
job baru beralasan dan memakai snapshot identitas asal. Sending kedaluwarsa
ditandai uncertain ketika recovery/reprint, tidak diambil ulang untuk dispatch.
Pembatalan queued dicatat failed_before_send/cancelled tanpa print attempt.

SDK QZ lazy dan koneksi manual WSS allowlist (default localhost/127.0.0.1),
tanpa downgrade TLS. Discovery membaca printer terdaftar OS; pemilihan selalu
eksplisit. Jalur image-only memakai raster PNG203dpi, modul Code128 integer
minimal2dot, quiet zone10modul dan nearest-neighbor. Barcode/nama tidak
diperkecil paksa jika tidak muat. Tidak ada raw TSPL/CPCL, ESC/POS, signing
proxy, akses USB/serial generik atau auto-print checkout.

Workspace menyediakan Printer/Label/Riwayat/Scanner, remote Autocomplete,
shared TablePagination, Sonner, F1 dan Alt+1..4/Alt+F. Scanner test memakai
input fokus + Enter, mempertahankan nol awal, membandingkan kode label dan
menyimpan maksimal20 hasil sementara. Tidak mengklaim identitas HC-4208DB.

SDK npm bukan aplikasi QZ desktop. Admin memasang QZ/driver secara manual;
unsigned permission prompt tetap berlaku. Driver/ukuran/gap/scan-back nyata
belum diverifikasi. Physical print OFF sampai UAT dan persetujuan pemilik.

## HTTP

GET `/platform/hardware/scanner` merender Inertia platform/hardware/scanner.
GET `/platform/hardware/scanner/resolve` menerima hanya code, string ASCII
printable maksimal64 karakter; trim mengikuti existing, nol awal tetap teks.
Field asing ditolak422. Endpoint resolve dibatasi120 request/menit menggunakan
throttle existing. Session web/verified serta can:inventory.view per action;
tanpa permission403, guest401/redirect sesuai jenis request. Feature/env gate
di luar Gate menolak404 saat OFF, termasuk super-system. Saat ON, akses juga
didukung pada production tanpa melewati auth/verified/permission.

Response200 hanya item_id, sku, name, unit_id, unit_name dan barcode nullable.
Tidak mengirim HPP/harga/stok/customer/token atau field persistence. Unknown,
nonaktif, terhapus dan satuan bukan jual ditolak422. Response melalui middleware
bersifat private/no-store. Endpoint scanner sendiri tidak melakukan mutation.

SaleItemCatalog existing tetap source of truth: barcode terreservasi menang
atas SKU fallback, termasuk ketika pemilik barcode tidak dapat dijual. Tidak
membuat lookup paralel atau menerima gudang/tenant/cabang buatan.

## Frontend

BarcodeInput existing menerima teks + Enter dalam input fokus. Tidak ada
listener yang menangkap barcode secara global. Antrean serial dibatasi20
outstanding, hasil terakhir20 di memori tab; scan sama sengaja dua kali tetap
dua hasil. Hitungan tersebut bukan kuantitas transaksi atau saldo.

Timeout15 detik dan network/API/response invalid menjadi hasil error tanpa
retry otomatis. Hapus hasil meminta konfirmasi, membatalkan request/queue dan
menaikkan generation agar respons lama tidak mengisi hasil baru. Unmount juga
membatalkan request; reload/berpindah halaman tidak menyimpan hasil.

Alt+S fokus input, Alt+R konfirmasi bersihkan; tidak bekerja saat dialog lain
terbuka. F1 memakai PageGuide shared. shadcn/Lucide, Sonner dan theme existing;
hasil tanpa pagination karena hanya diagnostics sementara, bukan daftar bisnis.

## Data Dan Pengujian

HardwareDemoSeeder sengaja no-op. Fixture memakai seeder owner Units/Items dan
kode CCTV/MCB fiktif hanya dalam SQLite QA. Sebelum bootstrap, verifikasi
APP_ENV, DB connection/path, storage/cache di root nonce; setelah bootstrap
periksa lagi koneksi/config aktual sebelum menjalankan migrate/seed disposable.

FakeBarcodeInput hanya tests/Browser/Hardware, tidak dibundel ke aplikasi.
Scanner fixture lama tidak membuka printer, USB/Bluetooth atau gateway.
Fixture label baru memakai SQLite/root nonce terpisah dan fake WebSocket QZ
test-only, bukan hardware/OS transport nyata.
Fingerprint seluruh business/master/grant/audit dibandingkan sebelum/sesudah
uji lookup; session/cache QA dikecualikan. Lihat REPORT task untuk hasil aktual.

Status software mengikuti REPORT masing-masing task, bukan bukti fisik. Physical
HC-4208DB dan CT420D tetap PHYSICAL_PENDING; pilot perangkat belum diizinkan.
Aktivasi diagnostik production memiliki PLAN/approval tersendiri TASK-HW-011;
izin akses halaman tidak menjadi izin pilot printer atau verifikasi hardware.
