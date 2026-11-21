# Spesifikasi Uji Scanner Virtual

## HTTP

GET `/platform/hardware/scanner` merender Inertia platform/hardware/scanner.
GET `/platform/hardware/scanner/resolve` menerima hanya code, string ASCII
printable maksimal64 karakter; trim mengikuti existing, nol awal tetap teks.
Field asing ditolak422. Endpoint resolve dibatasi120 request/menit menggunakan
throttle existing. Session web/verified serta can:inventory.view per action;
tanpa permission403, guest401/redirect sesuai jenis request. Feature/env gate
di luar Gate menolak404 saat OFF atau bukan local/testing, termasuk super-system.

Response200 hanya item_id, sku, name, unit_id, unit_name dan barcode nullable.
Tidak mengirim HPP/harga/stok/customer/token atau field persistence. Unknown,
nonaktif, terhapus dan satuan bukan jual ditolak422. Response melalui middleware
bersifat private/no-store. Tidak ada mutation endpoint, DB table atau job baru.

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
Tidak ada fake/real printer, socket printer, USB/Bluetooth atau gateway.
Fingerprint seluruh business/master/grant/audit dibandingkan sebelum/sesudah
uji lookup; session/cache QA dikecualikan. Lihat REPORT task untuk hasil aktual.

Status software yang lulus hanya meliputi slice scanner virtual. Physical
HC-4208DB dan CT420D tetap PHYSICAL_PENDING; production/pilot belum diizinkan.
