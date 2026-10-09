# ADR-010: Super System Bypass Permission Backend

## Status Dan Tanggal

Accepted, 2026-10-09. Disetujui eksplisit oleh user pada Increment 5 Inventory.

## Konteks

Hook frontend isSuperSystem sudah memberi akses UX, tetapi role super-system
belum memiliki bypass Laravel Gate. Permission module baru tidak otomatis
diberikan oleh access seeder; akibatnya UX dan authorization backend berbeda.

## Keputusan

Platform/Identity mendaftarkan Gate::before pada ServiceProvider. User dengan
role super-system guard web memperoleh true untuk authorization Gate. User
lain menerima null agar evaluasi Spatie/policy normal tetap berjalan. Pemeriksaan
permission memakai can/Gate, bukan middleware permission langsung untuk modul
baru. Login/session dan middleware auth tetap diperlukan.

## Konsekuensi Dan Batas

- Menghindari sinkronisasi seluruh permission baru ke role super-system.
- Tidak menambah atau mengubah role/akses user saat seed module Inventory.
- Hanya bypass authorization; validation, transaction, locking, invariant base
  unit, no-negative-stock, FIFO, dan larangan mutation posted wajib dijalankan
  di use case/Domain, bukan disandarkan pada policy yang dapat dibypass.
- Assignment role super-system tetap operasi sensitif milik Identity yang
  harus diaudit. Increment ini tidak mengubah assignment maupun akun seed.
- Middleware permission: langsung dari Spatie tidak melewati Laravel Gate;
  source legacy tidak direfactor di luar scope.
- Tidak mengubah kontrak MustVerifyEmail atau flow authentication existing.

## Verifikasi

SuperSystemGateTest dan test endpoint Inventory membuktikan super-system tanpa
permission eksplisit tetap authorized; user biasa dan guest tidak ikut bypass;
input barang invalid tetap gagal tanpa mutation/audit parsial.
