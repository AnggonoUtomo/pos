# Rencana Implementasi

1. Schema ULID sesi/baris, unique gudang aktif, port/repository sesi dan guard
   ledger bersama. Test fail dahulu untuk freeze, cancel/release dan gudang lain.
2. Lifecycle mulai, snapshot, tambah barang, simpan hitung, review/kembali,
   posting selisih FIFO/harga pokok dan optimistic version. Permission dan audit
   terpisah view/manage/post. UI modular, tabel shared, konfirmasi dan shortcut.
3. Panduan lokal/operator, demo disposable, QA native MySQL concurrency serta
   browser desktop/tablet. Satu regresi akhir lengkap, aktivasi additive dengan
   fingerprint data existing, commit per increment dan push setelah tuntas.
