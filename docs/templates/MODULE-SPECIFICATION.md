# Specification: [Nama Module]

## Status

Draft | Disetujui | Implemented

## Tujuan Dan Scope

[Behavior yang menjadi tanggung jawab module.]

## Arsitektur

- Hexagon: `[Domain/Module]`.
- Inbound adapter: [HTTP, console, queue, atau lainnya].
- Use case/inbound port: [Action, Command, atau Query].
- Outbound port: [Application/Contracts yang dibutuhkan].
- Outbound adapter: [Infrastructure adapter yang mengimplementasikan port].
- Composition root: `app/Modules/{Domain}/{Module}/ServiceProvider.php`.

## Di Luar Scope

- [Behavior yang tidak dimiliki module.]

## Contract

- Input: [DTO/request/command].
- Output: [DTO/response/event].
- Failure: [error atau exception publik].

## Data

- [Entity, tabel, ownership migration, identifier, soft delete, dan retention.]

## Seeder Demo

- [Data demo yang relevan, relasi yang harus ada, atau alasan skip.]

## Authorization Dan Audit

- [Permission, policy, resource rule, dan event yang diaudit.]

## UI

- Page: [path Inertia].
- Komponen: [path components].
- Toast: [success/error CRUD dengan Sonner].
- State: [loading, empty, error, disabled].
- Browser QA: [viewport dan alur yang dicek].

## Dependency

- [Public dependency dan alasan.]

## Acceptance Criteria

- [ ] [Behavior positif.]
- [ ] [Behavior negatif atau failure handling.]

## Risiko Terbuka

- [Risiko yang belum ditutup dan owner keputusannya.]
