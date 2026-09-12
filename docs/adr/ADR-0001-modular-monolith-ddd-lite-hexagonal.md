# ADR-0001: Modular Monolith Dengan DDD-Lite dan Hexagonal Architecture

## Status

Diterima

## Tanggal

2026-09-12

## Konteks

Aplikasi POS ini akan berkembang dari workflow kasir menjadi ERP-lite untuk retail/grosir. Domainnya mencakup catalog, parties, sales, purchasing, inventory, payments, reporting, identity, dan company settings.

Sistem membutuhkan Boundary yang jelas, tetapi belum membutuhkan kompleksitas microservices.

## Keputusan

Gunakan modular monolith dengan struktur:

```text
app/Modules/{Category}/{Module}
```

Kategori awal:

- `Platform`
- `Inventory`
- `Sales`
- `Purchasing`
- `Finance`
- `Reporting`

Gunakan DDD-lite dan hexagonal architecture secara pragmatis. Modul boleh memiliki `Application`, `Domain`, `Infrastructure`, dan `Presentation`, tetapi folder kosong tidak dipaksakan.

## Alternatif Yang Dipertimbangkan

### Flat Laravel App

- Kelebihan: cepat untuk mulai.
- Kekurangan: business rule FIFO, payment, tax, pricing, dan retur mudah tersebar di controller/model.
- Ditolak karena domain akan besar.

### Microservices

- Kelebihan: Boundary keras dan deployment independen.
- Kekurangan: terlalu kompleks untuk fase awal POS, meningkatkan biaya operasional dan integrasi.
- Ditolak untuk fase 1.

### Package Per Module

- Kelebihan: Boundary lebih kuat.
- Kekurangan: overhead tinggi sebelum pola domain matang.
- Ditunda.

## Konsekuensi

- Modul punya Boundary jelas tanpa overhead distributed system.
- Agent dan pengembang harus membaca kontrak modul sebelum mengubah kode.
- Cross-module access harus lewat service/event/read model yang disepakati.
- Refactor tetap mungkin karena aplikasi masih satu codebase.
