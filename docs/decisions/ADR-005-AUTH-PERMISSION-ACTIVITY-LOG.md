# ADR-005: Auth, Permission, Dan Activity Log

## Status

Accepted

## Tanggal

2026-09-12

## Konteks

POS membutuhkan kontrol akses per role/action, public registration harus
dimatikan, dan perubahan penting harus memiliki audit trail.

## Keputusan

Project memakai Laravel starter kit untuk authentication, Spatie Laravel
Permission untuk authorization, dan Spatie Laravel Activitylog untuk audit
trail. Shared auth Inertia menyediakan `roles`, `permissions`, dan
`superSystem` untuk kebutuhan UX.

## Konsekuensi

- Backend tetap authority untuk semua authorization.
- Controller module memakai `HasMiddleware` dan middleware `can:{permission}`
  per action, atau policy eksplisit.
- Frontend memakai `resources/js/hooks/use-permission.ts` hanya untuk UX.
- Public registration dimatikan.
- Activity log dipakai untuk audit operasional, bukan ledger stok atau finance.

## Verifikasi

- Permission backend diuji dengan feature test.
- Shared Inertia auth memuat `superSystem`, `roles`, dan `permissions`.
- Mutation penting mencatat activity log bila relevan.
- User tanpa permission menerima 403 atau tidak dapat menjalankan action.
