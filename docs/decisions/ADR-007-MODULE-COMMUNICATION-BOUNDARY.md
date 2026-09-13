# ADR-007: Komunikasi Lintas Module Melalui Public Boundary

## Status

Accepted

## Tanggal

2026-09-12

## Konteks

Modular monolith tetap berbagi proses dan database, tetapi business mutation
tidak boleh mengambil detail privat module lain. Tanpa boundary yang jelas,
module POS, stok, payment, dan reporting akan saling terikat ke model atau
repository internal.

## Keputusan

Komunikasi lintas module memakai public boundary berikut:

- `Application/Contracts`
- `Application/DTOs`
- `Application/Events`
- Domain event atau integration event yang memang memiliki consumer

Module tidak boleh memakai Eloquent model, repository, controller, policy,
adapter, atau Domain privat module lain untuk business mutation.

## Konsekuensi

- Contract lintas module harus eksplisit dan stabil.
- Event hanya dibuat bila ada consumer nyata.
- Invariant sinkron menggunakan application service/contract eksplisit.
- Side effect yang dapat dipisahkan memakai event/listener.

## Verifikasi

- Import lintas module diperiksa pada code review.
- Work item mencatat dependency module yang berubah.
- Tidak ada mutation yang menulis langsung ke tabel milik module lain tanpa
  contract yang disepakati.
