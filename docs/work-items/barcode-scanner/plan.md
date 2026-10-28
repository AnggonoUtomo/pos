# Plan: Barcode Scanner

1. Dokumentasikan scope/checklist dan install JsBarcode sesuai izin user.
2. RED focused exact scan + metadata purchase serta helper quantity/label.
3. Public scan boundary/backend, kode internal + label form/master; verifikasi
   focused dan save point tanpa file LSP user.
4. Antrean scanner POS serta input scan pembelian, shortcut dan feedback Sonner.
5. Satu gate regresi backend + lint/typecheck/build/audit; QA Chrome desktop/tablet
   pada database disposable. Simulasikan scanner Enter tanpa delay debounce,
   barcode leading zero, scan ulang, salah satuan, unknown dan cetak terisolasi.
6. Aktifkan route cache (tanpa migrate/seed), panduan/checklist/status, commit
   terfokus dan push setelah seluruh capability selesai.
