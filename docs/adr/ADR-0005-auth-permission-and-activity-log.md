# ADR-0005: Starter Kit Auth, Spatie Permission, dan Activity Log

## Status

Diterima

## Tanggal

2026-09-12

## Konteks

Aplikasi POS adalah sistem internal. User dibuat oleh admin, bukan registrasi publik. Sistem juga membutuhkan role, permission, dan audit trail untuk perubahan master, setting, transaksi, payment, dan stok.

## Keputusan

- Gunakan auth standar Laravel starter kit.
- Matikan public registration.
- Gunakan Spatie Laravel Permission untuk role dan permission.
- Gunakan middleware/policy backend sebagai authority authorization.
- Untuk controller module, pola default adalah `HasMiddleware` dengan middleware `can:{permission}` per action.
- Frontend memakai hook `resources/js/hooks/use-permission.ts` untuk UX guard berbasis `auth.permissions`, `auth.roles`, dan `auth.super`.
- Gunakan Spatie Laravel Activitylog untuk audit trail.
- Simpan actor columns eksplisit pada transaksi: `created_by`, `posted_by`, `voided_by`.

## Alternatif Yang Dipertimbangkan

### Custom Auth

- Kelebihan: fleksibel.
- Kekurangan: membuang fitur standar yang sudah matang.
- Ditolak.

### Custom Permission

- Kelebihan: bisa disesuaikan total.
- Kekurangan: Spatie Permission sudah cukup dan umum di Laravel.
- Ditolak.

### Audit Trail Manual Saja

- Kelebihan: sangat terkontrol.
- Kekurangan: mudah tidak konsisten antar modul.
- Ditolak.

## Konsekuensi

- Identity module membungkus manajemen user/role/permission, tidak perlu melawan struktur auth bawaan.
- UI dapat menyembunyikan aksi yang tidak boleh diakses user, tetapi setiap aksi tetap wajib divalidasi di backend.
- Activity log wajib untuk aksi penting, tetapi bukan sumber saldo stok atau finance.
