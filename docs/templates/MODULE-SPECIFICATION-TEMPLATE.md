# Specification: {Category}/{Module}

## Status

Draf.

## Tujuan dan Scope

Scope awal:

- ...

## Arsitektur

- Hexagon: `app/Modules/{Category}/{Module}`.
- Inbound adapter:
  - HTTP/UI/Console/Queue sesuai kebutuhan nyata.
- Use case awal:
  - ...
- Candidate public contract:
  - ...
- Composition root:
  - `app/Modules/{Category}/{Module}/ServiceProvider.php`.

Domain dibuat ketika rule murni mulai bernilai. Jika rule masih CRUD sederhana, implementasi awal boleh berada di Application dengan test yang ketat.

## Di Luar Scope

- ...

## Contract

### Input

- ...

### Output

- ...

### Failure

- Validasi gagal: `422`.
- Actor tidak punya permission: `403`.
- Record tidak ditemukan: `404`.

## Data

Table kandidat:

- ...

## Authorization dan Audit

- Permission:
  - ...
- Backend guard:
  - Controller Presentation memakai `HasMiddleware` dan `new Middleware('can:{permission}', only: [...])` atau policy yang eksplisit.
- Audit:
  - ...

## UI

- Page canonical:
  - `resources/js/pages/{category}/{module}/...`
- Komponen business-specific ditempatkan dekat page module.
- UI guard memakai `resources/js/hooks/use-permission.ts` bila page menampilkan aksi berbasis permission.
- Backend permission tetap menjadi authority.

## Dependency

- ...

## Acceptance Criteria

- [ ] ...

## Risiko Terbuka

- ...
