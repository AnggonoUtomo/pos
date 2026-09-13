# ADR-0007: Public Boundary Komunikasi Lintas Modul

## Status

Diterima

## Tanggal

2026-09-12

## Konteks

POS Modular ERP-Lite memakai modular monolith. Modul seperti Sales, Purchasing, Inventory, Payments, Catalog, dan Reporting harus saling berkomunikasi, tetapi boundary modul tetap perlu dijaga agar business rule tidak tersebar dan modul tidak saling bergantung pada detail internal.

Risiko utama:

- Modul langsung mengakses Eloquent model modul lain.
- Business rule transaksi tersebar di controller.
- Event publik membawa model internal.
- DTO tidak stabil dan berubah menjadi array bebas.
- Reporting menjalankan mutasi state.
- Domain event dan integration event dibuat spekulatif tanpa consumer.

## Keputusan

Gunakan public boundary berikut untuk komunikasi lintas modul:

```text
Application/Contracts
Application/DTOs
Application/Events
```

Aturan:

- Contract sinkron didefinisikan di `Application/Contracts`.
- Input/output contract memakai DTO dari `Application/DTOs`.
- Event publik lintas modul didefinisikan di `Application/Events`.
- Domain event boleh ada di dalam domain modul, tetapi hanya dibuat jika ada consumer nyata.
- Integration event hanya dibuat jika ada consumer lintas modul atau consumer eksternal yang jelas.
- Modul lain tidak boleh mengakses Eloquent model internal modul secara langsung untuk write operation.
- Reporting boleh membaca lintas modul melalui query/read model yang disepakati, tetapi tidak boleh memutasi state operasional.

## Alternatif Yang Dipertimbangkan

### Direct Model Access Antar Modul

- Kelebihan: cepat dibuat.
- Kekurangan: boundary bocor, business rule tersebar, refactor mahal.
- Ditolak.

### Event-Only Communication

- Kelebihan: decoupling tinggi.
- Kekurangan: terlalu rumit untuk workflow sinkron seperti consume FIFO dan payment posting.
- Ditolak sebagai aturan tunggal.

### Shared Service Global

- Kelebihan: mudah ditemukan.
- Kekurangan: berisiko menjadi god service dan melemahkan ownership modul.
- Ditolak.

## Konsekuensi

- Setiap integrasi lintas modul harus punya contract/event/DTO yang jelas.
- Modul consumer bergantung pada public contract, bukan class internal.
- Perubahan contract publik harus backward-compatible atau lewat ADR/work-item eksplisit.
- Feature test lintas modul harus menguji contract penting seperti posting sales, purchase, return, transfer, dan payment.
- Tidak semua perubahan domain membutuhkan event; event hanya dibuat ketika ada consumer nyata.
