# Plan: Gambar Utama Barang

1. Backend: test RED upload/validasi/permission/read dan rollback; tambahkan
   migration media ULID, model/observer/path generator package, port/DTO,
   adapter, integrasi SaveItemCatalog dan endpoint baca.
2. Frontend: editor gambar lokal dengan preview/hapus/batalkan, multipart form,
   thumbnail master, gambar katalog POS. Pertahankan shortcut, Sonner,
   barcode/satuan/harga dan tampilan desktop/tablet.
3. Gate akhir: focused test dan satu regresi backend; Pint, lint, typecheck,
   build, route, schema MySQL disposable, browser terisolasi desktop/tablet.
   Coba discovery Chrome DevTools; catat BLOCKED bila tool tidak tersedia,
   gunakan Playwright existing tanpa instalasi dependency.
4. Aktivasi additive: migrate hanya migration baru setelah status diperiksa,
   refresh cache yang terdampak, verifikasi HTTP dan data tidak berubah.
   Update panduan/checklist, commit scoped; push saat work item ini selesai.

Jangan menjalankan lifecycle replacement, seed ulang database toko, mengganti
role akun atau mengaktifkan pending UAT/printer/tablet.
