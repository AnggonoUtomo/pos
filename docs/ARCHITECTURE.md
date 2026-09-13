# Arsitektur

## Gaya Arsitektur

POS Modular ERP-Lite memakai **DDD-lite Modular Monolith dengan Hexagonal
Architecture**. Setiap module adalah boundary bisnis dan satu hexagon.
Abstraction dibuat karena ada behavior, dependency, atau consumer nyata, bukan
untuk melengkapi diagram.

- Framework reusable: tidak ada untuk fase ini.
- Module aplikasi: `app/Modules/{Domain}/{Module}`.
- Frontend module: `resources/js/pages/{domain}/{module}`.
- Struktur canonical: [FOLDER-STRUCTURE.md](FOLDER-STRUCTURE.md).
- Auth: Laravel starter kit.
- Authorization: Spatie Laravel Permission.
- Audit trail: Spatie Laravel Activitylog.

## Hexagon Pada Setiap Module

```text
HTTP / Console / Queue / UI
            |
            v
Presentation (inbound adapter)
            |
            v
Application (use case, command, query, DTOs, port)
            |
            v
Domain (aturan bisnis)
            ^
            |
Infrastructure (outbound adapter)
            |
Database / framework / package / layanan eksternal
```

`ServiceProvider.php` pada module menjadi composition root. File ini menghubung-
kan contract dengan adapter, memuat route/migration, mendaftarkan policy/listener,
dan tidak berisi business logic.

## Tanggung Jawab Layer

| Layer | Tanggung jawab | Tidak boleh |
| --- | --- | --- |
| Domain | Entity, value object, domain service, event, invariant, rule bisnis murni | Bergantung pada framework, HTTP, UI, Eloquent, atau layer luar |
| Application | Use case, action, command, query, DTOs, port, orchestration, transaction boundary | Mengimpor adapter Infrastructure konkret atau detail UI/HTTP |
| Infrastructure | Persistence, repository implementation, adapter framework/package, listener side effect | Menjadi pemilik business rule |
| Presentation | Controller, request, resource, route, middleware, console command, Inertia response | Melakukan business mutation atau persistence langsung |

Domain boleh belum ada pada capability yang benar-benar CRUD sederhana dan belum
memiliki rule murni. Jika Domain dibuat, ia harus bebas dari detail framework.

## Arah Dependency

```text
Presentation ------> Application ------> Domain
Infrastructure ----> Application ------> Domain
```

- Domain tidak mengimpor layer lain.
- Application tidak mengimpor Infrastructure.
- Presentation memanggil Application.
- Infrastructure mengimplementasikan port milik Application atau Domain.
- Binding port-adapter dilakukan oleh `ServiceProvider.php` module.
- Route mengarah ke Presentation.

## Port Dan Adapter

- Action, Command, atau Query menjadi inbound use case.
- Outbound port berada di `Application/Contracts` ketika Application memerlukan
  persistence, runtime setting, publisher, session, atau integrasi eksternal.
- DTOs publik berada di `Application/DTOs`.
- Event publik berada di `Application/Events`.
- Adapter berada di `Infrastructure` dan mengimplementasikan outbound port.
- `Domain/Contracts` hanya untuk abstraction yang menjadi bahasa domain.
- Port, DTOs, event, repository, service, atau adapter tidak dibuat tanpa
  consumer nyata.

## Komunikasi Lintas Module

Public boundary yang disarankan:

- `Application/Contracts`
- `Application/DTOs`
- `Application/Events`
- Domain event atau integration event yang memang memiliki consumer

Module tidak mengambil Eloquent model, repository, controller, policy, adapter,
atau Domain privat module lain untuk business mutation. Untuk invariant yang
harus sinkron, gunakan published contract/application service yang eksplisit.
Untuk side effect yang dapat dipisahkan, gunakan event/listener.

Dependency nyata antar module harus terlihat pada dokumentasi module terkait dan
work item yang mengubahnya.

## CQRS Dan Action

CQRS digunakan pragmatis. Command/action dipakai untuk mutation yang memiliki
orchestration, transaction boundary, authorization, event, audit, posting stok,
posting pembayaran, atau risiko integritas data. Query boleh memakai read query
yang efisien. CRUD sederhana tidak wajib diberi ceremony CQRS penuh.

## Transaksi Dan Konsistensi Data

- Mutation penting dijalankan dalam database transaction.
- Posting stok, pembayaran, dan dokumen posted memakai locking yang sesuai.
- Posted transaction tidak boleh diedit bebas.
- Koreksi posted transaction dilakukan lewat retur, void/reversal, atau
  adjustment yang audited.
- Activity log mencatat aktivitas operasional, tetapi bukan ledger stok atau
  ledger finance.

## Aturan Stok Dan FIFO

- Stok tidak boleh minus.
- Semua kuantitas stok disimpan dalam base unit terkecil.
- Multi satuan hanya representasi input/output; perhitungan stok memakai base
  unit.
- FIFO berjalan per `item + warehouse`.
- Satu transaksi POS memakai satu gudang untuk MVP.
- User boleh memilih gudang saat transaksi.
- FIFO layer dan stock movement tidak boleh dihapus lunak sebagai koreksi.

## Pricing, Diskon, Pajak, Dan Payment

- Harga jual berdasarkan customer level dan satuan.
- Snapshot harga, satuan, diskon, pajak, HPP, dan payment disimpan pada transaksi.
- Diskon mendukung persen dan nominal.
- Pajak masuk dalam transaksi sejak awal.
- Multi payment didukung pada transaksi penjualan.
- Finance Lite menjadi boundary awal untuk payment, piutang/utang dasar, dan
  ringkasan operasional; full accounting ditunda.

## Authorization Dan Audit

- Backend adalah security authority.
- Controller module yang butuh permission memakai `HasMiddleware` dan middleware
  `can:{permission}` per action, atau policy eksplisit.
- Frontend permission guard memakai `resources/js/hooks/use-permission.ts` dengan
  `isSuperSystem`; guard ini hanya untuk UX.
- Activity log wajib dipertimbangkan pada mutation penting, terutama role,
  permission, setting, posting, void, retur, adjustment, dan payment.

## UI/UX

- Admin ERP memakai layout sidebar.
- POS memakai mode fullscreen untuk desktop atau tablet.
- Admin responsive dasar.
- shadcn/ui menjadi default komponen UI.
- `lucide-react` menjadi default icon.
- Sonner toast dari shadcn/ui digunakan untuk feedback operasi CRUD.
- Komponen fitur diletakkan dekat page:
  `resources/js/pages/{domain}/{module}/components`.
- Komponen shared hanya untuk UI lintas fitur yang benar-benar stabil.

## Guardrail Arsitektur

- Jangan membuat folder kosong hanya untuk melengkapi diagram.
- Jangan membuat port, event, service, repository, adapter, atau integration
  tanpa consumer nyata.
- Jangan menggunakan Laravel Boost atau Wayfinder sebagai source of truth.
- Jangan memindahkan migration module ke `database/migrations` global untuk
  table milik module.
- Perubahan bounded context, stack utama, strategi identifier, atau struktur
  canonical memerlukan keputusan eksplisit dan ADR bila sulit dibalik.

## Gap Conformance

Penyimpangan source code terhadap baseline dicatat pada work item terkait.
Jangan menutup gap di luar scope pekerjaan tanpa persetujuan user.
