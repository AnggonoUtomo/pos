# Specification: Platform/ModuleRuntime

## Status

Draft aktif, pending validasi struktur final.

## Tujuan Dan Scope

Menjaga agar module baru dibuat konsisten dengan arsitektur project:
`ServiceProvider.php`, `Routes/`, `Database/Migrations`, `Database/Seeders`, dan
opsi test.

## Arsitektur

- Hexagon: `Platform/ModuleRuntime` bila nanti dipindahkan menjadi module fisik.
- Inbound adapter: Laravel console command.
- Use case/inbound port: generator module.
- Outbound port: filesystem writer bila abstraction dibutuhkan.
- Outbound adapter: Laravel filesystem atau PHP filesystem.
- Composition root: pending bila menjadi module fisik.

## Di Luar Scope

- Menentukan business rule tiap module.
- Memvalidasi seluruh dependency lintas module.
- Menjadi package reusable eksternal.

## Contract

- Input: `Domain`, `Module`, opsi `--dry-run`, opsi `--with-tests`.
- Output: daftar file yang dibuat atau akan dibuat.
- Failure: input invalid, target sudah ada, atau filesystem gagal.

## Acceptance Criteria

- [x] Generator membuat target di `app/Modules`, bukan root `Modules`.
- [x] Generator menyertakan `Routes/`, `Database/Migrations`, dan
  `Database/Seeders`.
- [x] Generator memiliki dry-run.
- [x] Generator dapat membuat test bila diminta.
- [ ] Keputusan apakah capability ini tetap command global atau dipindah menjadi
  module fisik perlu dipastikan pada work item berikutnya.

## Risiko Terbuka

- Nama `ModuleRuntime` masih mewakili capability platform, belum source module
  fisik.
- Validator arsitektur module belum tersedia sebagai command khusus.
