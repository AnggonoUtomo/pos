# ADR-001: DDD-lite Modular Monolith Dengan Hexagonal Architecture

## Status

Accepted

## Tanggal

2026-09-12

## Konteks

Project POS membutuhkan batas module yang jelas, tetapi belum membutuhkan
kompleksitas tactical DDD penuh. Aplikasi harus tetap nyaman dikembangkan dalam
satu Laravel monolith, namun tiap capability harus punya boundary agar stok,
pricing, payment, permission, dan reporting tidak saling bocor.

## Keputusan

Setiap module berada di `app/Modules/{Domain}/{Module}` dan diperlakukan sebagai
satu hexagon. Layer yang dipakai sesuai kebutuhan nyata:

- `Presentation` untuk controller, request, resource, route, middleware, policy,
  console command, dan Inertia response.
- `Application` untuk use case, action, command, query, DTOs, port, dan
  orchestration.
- `Domain` untuk rule bisnis murni bila capability membutuhkan invariant.
- `Infrastructure` untuk persistence, adapter framework/package, repository
  implementation, dan listener side effect.

Arah dependency mengikuti:

```text
Presentation ------> Application ------> Domain
Infrastructure ----> Application ------> Domain
```

`ServiceProvider.php` module menjadi composition root.

## Konsekuensi

- Business rule penting dapat diuji tanpa HTTP/UI.
- Framework dan persistence detail tidak bocor ke Domain.
- CRUD sederhana tidak dipaksa memiliki Domain bila belum ada rule murni.
- Developer harus menahan diri dari membuat folder atau abstraction tanpa
  consumer nyata.

## Verifikasi

- Domain tidak mengimpor Laravel, Eloquent, HTTP, Inertia, atau layer luar.
- Application tidak mengimpor adapter Infrastructure konkret.
- Controller memanggil Application action/query.
- Route module berada di `Routes/`.
- Migration dan seeder module berada di `Database/`.
