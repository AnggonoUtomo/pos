# Arsitektur: POS Modular ERP-Lite

Status: Draf

## Ringkasan

Aplikasi memakai modular monolith. Semua modul berjalan dalam satu aplikasi Laravel, satu deployment, dan satu database MySQL, tetapi Boundary domain tetap dipisah melalui folder modul, application services, domain contracts, events, permission naming, dan route naming.

## Struktur Modul

```text
app/
  Modules/
    Platform/
      Identity/
      Company/

    Commerce/
      Catalog/
      Parties/
      Sales/
      Purchasing/
      Payments/

    Inventory/
      Inventory/

    Reporting/
      Reports/
```

## Layer Modul

Struktur ideal modul:

```text
Application/
Domain/
Infrastructure/
Presentation/
```

Makna layer:

- `Presentation`: controller, request, route, Inertia page entry.
- `Application`: use case, command/query handler, DTO, orchestration transaction.
- `Domain`: value object, domain service, domain exception, event, kontrak domain.
- `Infrastructure`: Eloquent model, repository implementation, migration, provider, adapter package.

Folder kosong tidak diwajibkan. Gunakan layer saat ada kebutuhan nyata.

## Generator Modul

Gunakan generator modul untuk menjaga struktur folder dan namespace konsisten:

```bash
php artisan module:make Platform Identity
php artisan module:make Inventory Catalog --with-routes --with-tests
php artisan module:make Sales POS --with-routes --with-tests --dry-run
```

Aturan generator:

- Membuat modul pada `app/Modules/{Category}/{Module}`.
- Membuat `ServiceProvider.php` sebagai composition root module.
- Opsi `--with-routes` membuat `Presentation/Routes/web.php`.
- Opsi `--with-tests` membuat test scaffold di `tests/Feature/Modules` dan `tests/Unit/Modules`.
- Opsi `--dry-run` wajib dipakai jika ingin melihat rencana file tanpa menulis ke filesystem.
- Opsi `--force` hanya dipakai jika memang ingin overwrite file generated.
- Generator tidak membuat `Domain`, repository, port, event, adapter, atau migration tanpa kebutuhan nyata.
- Provider module pada `app/Modules/*/*/ServiceProvider.php` diregister otomatis oleh aplikasi.

## Hexagon Per Modul

Setiap modul diperlakukan sebagai hexagon kecil di dalam modular monolith:

```text
HTTP / Console / Queue / UI
            |
            v
Presentation (inbound adapter)
            |
            v
Application (use case, command, query, DTO, port)
            |
            v
Domain (aturan bisnis)
            ^
            |
Infrastructure (outbound adapter)
            |
Database / framework / package / layanan eksternal
```

`ServiceProvider.php` menjadi composition root module. File ini menghubungkan contract dengan adapter, memuat route/migration, mendaftarkan policy/listener, dan tidak berisi business logic.

## Tanggung Jawab Layer

| Layer | Tanggung jawab | Tidak boleh |
| --- | --- | --- |
| Domain | Entity, value object, domain service, event, invariant, rule bisnis murni | Bergantung pada framework, HTTP, UI, Eloquent, atau layer luar |
| Application | Use case, action, command, query, DTO, port, orchestration, transaction boundary | Mengimpor adapter Infrastructure konkret atau detail UI/HTTP |
| Infrastructure | Persistence, repository implementation, adapter framework/package, listener side effect | Menjadi pemilik business rule |
| Presentation | Controller, request, resource, route, middleware, console command, Inertia response | Melakukan business mutation atau persistence langsung |

Domain boleh belum ada pada capability yang benar-benar CRUD sederhana dan belum memiliki rule murni. Jika Domain dibuat, ia harus bebas dari detail framework.

## Dependency Rule

- Presentation boleh memanggil Application.
- Application boleh memakai Domain contracts dan DTO.
- Domain tidak boleh bergantung pada Laravel request, controller, Inertia, atau Eloquent detail.
- Infrastructure mengimplementasikan kontrak dari Domain/Application.
- Modul lain berkomunikasi lewat public application service, event, atau read model yang disepakati.

Arah dependency:

```text
Presentation ------> Application ------> Domain
Infrastructure ----> Application ------> Domain
```

Aturan:

- Domain tidak mengimpor layer lain.
- Application tidak mengimpor Infrastructure.
- Presentation memanggil Application.
- Infrastructure mengimplementasikan port milik Application atau Domain.
- Binding port-adapter dilakukan oleh ServiceProvider module.
- Route mengarah ke Presentation.

## Komunikasi Lintas Modul

Public boundary lintas modul menggunakan:

```text
Application/Contracts
Application/DTOs
Application/Events
```

Aturan ringkas:

- Gunakan `Application/Contracts` untuk komunikasi sinkron yang membutuhkan hasil langsung.
- Gunakan `Application/DTOs` untuk input/output contract.
- Gunakan `Application/Events` untuk integration event lintas modul.
- Domain event internal tidak otomatis menjadi kontrak publik.
- Domain event atau integration event hanya dibuat jika memang memiliki consumer.
- Modul lain tidak boleh menulis Eloquent model atau tabel internal modul secara langsung.
- Reporting boleh membaca lintas modul melalui read model/query yang disepakati, tetapi tidak boleh memutasi state.

Detail lengkap ada di `docs/MODULE-COMMUNICATION.md` dan ADR-0007.

## CQRS Dan Action

CQRS digunakan pragmatis.

- Command/action dipakai untuk mutation yang memiliki orchestration, transaction boundary, authorization, event, atau audit.
- Query boleh memakai read query yang efisien.
- CRUD sederhana tidak wajib diberi ceremony CQRS penuh.
- Action/use case transaksi tidak boleh bocor ke controller.
- Query laporan boleh dioptimalkan untuk kebutuhan baca selama tidak memutasi state.

## Naming Database

Dokumentasi dan UI label memakai Bahasa Indonesia, tetapi naming teknis database dan kode memakai English technical naming.

Aturan:

- Nama tabel memakai English plural snake_case, misalnya `sales_invoices`, `stock_movements`, `fifo_layers`.
- Nama kolom memakai English snake_case, misalnya `posted_at`, `warehouse_id`, `base_quantity`.
- Nama pivot mengikuti konvensi package/framework jika sudah ada, misalnya tabel Spatie Permission.
- Jangan mencampur tabel Indonesia dan English seperti `pelanggan` berdampingan dengan `customers`.
- Label UI tetap boleh Bahasa Indonesia, misalnya tabel `customers` ditampilkan sebagai "Pelanggan".

## Guardrail Arsitektur

- Jangan membuat folder kosong hanya untuk melengkapi diagram.
- Jangan membuat port, event, service, repository, adapter, atau integration tanpa consumer nyata.
- Jangan menggunakan Laravel Boost atau Wayfinder sebagai source of truth.
- Jangan memindahkan migration module ke `database/migrations` global untuk table milik module.
- Perubahan bounded context, stack utama, strategi identifier, naming database, atau struktur canonical memerlukan keputusan eksplisit dan ADR bila sulit dibalik.

## Gap Conformance

Jika source code menyimpang dari baseline arsitektur, catat gap pada work-item terkait. Jangan menutup gap di luar scope pekerjaan tanpa persetujuan user.

## Transaction Boundary

Posting transaksi stok/payment harus berada dalam database transaction.

Use case berisiko tinggi:

- Post purchase invoice.
- Post sales invoice/POS.
- Create sales return.
- Create purchase return.
- Transfer warehouse stock.
- Stock opname posting.
- Void/reversal.

## Inventory Boundary

Inventory adalah sumber kebenaran untuk:

- Stock balance.
- FIFO layers.
- Stock movements.
- Stock card.
- Warehouse transfer.

Sales dan Purchasing tidak boleh mengubah stok langsung. Mereka harus memanggil use case/contract Inventory.

## Payments Boundary

Payments adalah sumber kebenaran untuk:

- Payment methods.
- Cash/bank accounts.
- Payment records.
- Receivable/payable settlement.

Sales dan Purchasing boleh membuat request pembayaran melalui application service Payments, bukan menulis saldo langsung.

## Reporting Boundary

Reporting boleh membaca data lintas modul untuk query laporan, tetapi tidak boleh menjadi tempat business rule posting transaksi.

## Frontend Layout

```text
resources/js/layouts/AppLayout.tsx
resources/js/layouts/PosLayout.tsx
```

- `AppLayout`: admin ERP sidebar.
- `PosLayout`: POS fullscreen.

Jika page berada di modul, lokasi final akan disesuaikan dengan pola Laravel/Inertia yang tersedia setelah source fresh masuk.

## Struktur Frontend Modular

Frontend memakai pendekatan feature-folder per halaman/domain. Komponen yang hanya dipakai oleh satu fitur harus tinggal dekat dengan page fitur tersebut. Jangan menaikkan komponen ke shared hanya karena terlihat reusable; naikkan ke shared setelah dipakai lintas fitur dan kontraknya stabil.

Struktur dasar:

```text
resources/js/
  components/
    ui/
    shared/

  layouts/
    app-layout.tsx
    pos-layout.tsx
    auth-layout.tsx

  pages/
    platform/
      users/
        components/
          user-card.tsx
          user-data-table.tsx
          user-form.tsx
          user-role-dialog.tsx
          user-filter.tsx
        index.tsx
        create.tsx
        edit.tsx
        types.ts
        columns.tsx
        filters.ts

    commerce/
      catalog/
        items/
          components/
            item-card.tsx
            item-data-table.tsx
            item-form.tsx
            unit-conversion-table.tsx
            price-level-table.tsx
            barcode-list.tsx
          index.tsx
          create.tsx
          edit.tsx
          types.ts
          columns.tsx

      sales/
        pos/
          components/
          hooks/
          index.tsx
          types.ts

    inventory/
      warehouses/
        components/
        index.tsx
        types.ts

    reporting/
      sales/
        components/
        index.tsx
        types.ts

  hooks/
  lib/
```

Aturan folder:

- `components/ui/`: hanya primitive shadcn/ui hasil generate atau adaptasi primitive.
- `components/shared/`: komponen lintas fitur yang benar-benar umum, misalnya `page-header`, `confirm-dialog`, `empty-state`, `money-input`, `date-range-picker`, dan `permission-guard`.
- `pages/{category}/{feature}/index.tsx`: entry page Inertia.
- `pages/{category}/{feature}/components/`: komponen lokal fitur tersebut.
- `pages/{category}/{feature}/types.ts`: tipe lokal fitur jika dibutuhkan.
- `pages/{category}/{feature}/columns.tsx`: definisi kolom tabel jika kompleks.
- `pages/{category}/{feature}/filters.ts`: helper filter/query lokal jika dibutuhkan.
- `pages/{category}/{feature}/schema.ts`: schema validasi client jika dibutuhkan.
- `hooks/`: hook lintas fitur yang benar-benar umum.
- `lib/`: helper non-React, formatter, permission helper, dan utilitas route.

Struktur khusus POS boleh lebih dalam karena workflow kasir lebih kompleks:

```text
resources/js/pages/commerce/sales/pos/
  components/
    cart/
      cart-panel.tsx
      cart-line.tsx
      cart-summary.tsx
    payment/
      payment-dialog.tsx
      payment-method-row.tsx
      payment-summary.tsx
    product-search/
      barcode-input.tsx
      product-search-dialog.tsx
    customer/
      customer-picker.tsx
    warehouse/
      warehouse-switcher.tsx
  hooks/
    use-pos-cart.ts
    use-pos-hotkeys.ts
    use-pos-payment.ts
  index.tsx
  types.ts
```

Prinsip:

- Dekatkan komponen dengan fitur yang memakainya.
- Page Inertia tetap tipis: menyusun layout, menerima props, dan menghubungkan komponen.
- Business rule berat tetap di backend/application service, bukan di page React.
- POS boleh punya hook lokal untuk cart, hotkeys, dan payment UI, tetapi hasil posting tetap mengikuti kontrak backend.
- Jangan membuat struktur frontend flat yang mencampur semua komponen fitur di satu folder global.

## Standar UI

Frontend memakai shadcn/ui sebagai default UI/UX aplikasi.

Konfigurasi awal mengikuti `components.json`:

- Style: `default`.
- Tailwind base color: `neutral`.
- CSS variables: aktif.
- TypeScript/TSX: aktif.
- React Server Components: tidak dipakai.
- Icon library: `lucide`.
- Alias komponen:
  - `@/components`
  - `@/components/ui`
  - `@/lib`
  - `@/hooks`

Aturan:

- Gunakan komponen shadcn/ui sebelum membuat komponen dasar baru.
- Gunakan `lucide-react` untuk ikon.
- Jangan membuat design system paralel.
- Komponen khusus POS boleh dibuat jika workflow kasir membutuhkan interaksi yang lebih cepat, tetapi token visual tetap mengikuti shadcn/ui/Tailwind.

## Public Contracts

Kontrak yang harus stabil:

- Route names.
- Permission names.
- Event names.
- Transaction status enum.
- Tax mode enum.
- Payment status enum.
- Stock movement type enum.
- Document number types.

Perubahan kontrak publik harus ditulis dalam ADR atau work-item yang disetujui.
