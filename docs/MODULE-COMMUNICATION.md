# Komunikasi Lintas Modul

Status: Draf

Dokumen ini menjelaskan cara modul berkomunikasi di dalam modular monolith POS. Tujuannya menjaga boundary modul tetap jelas tanpa membuat aplikasi terasa seperti microservices.

## Prinsip

- Modul lain tidak boleh mengakses Eloquent model internal modul secara langsung.
- Modul lain tidak boleh menulis tabel milik modul lain secara langsung.
- Komunikasi sinkron memakai public contract di `Application/Contracts`.
- Data yang keluar/masuk boundary modul memakai DTO di `Application/DTOs`.
- Komunikasi asinkron memakai event di `Application/Events`.
- Domain event atau integration event hanya dibuat jika memang memiliki consumer.
- Reporting boleh membaca lintas modul melalui read model/query yang disepakati, tetapi tidak boleh menjalankan business rule posting transaksi.

## Struktur Public Boundary

Setiap modul yang dipakai modul lain dapat mengekspos folder berikut:

```text
app/Modules/{Category}/{Module}/
  Application/
    Contracts/
    DTOs/
    Events/
```

Makna:

- `Application/Contracts`: interface/service contract yang boleh dipanggil modul lain.
- `Application/DTOs`: object data stabil untuk input/output contract.
- `Application/Events`: event publik lintas modul.

Folder ini hanya dibuat jika ada kebutuhan nyata. Jangan membuat folder kosong untuk modul CRUD tipis.

## Contract Sinkron

Gunakan contract sinkron ketika caller membutuhkan hasil langsung agar workflow dapat lanjut.

Contoh kasus:

- Sales memanggil Inventory untuk cek dan consume stok FIFO.
- Purchasing memanggil Inventory untuk membuat FIFO layer.
- Sales memanggil Payments untuk mencatat pembayaran.
- Purchasing memanggil Payments untuk mencatat hutang/pembayaran supplier.

Contoh bentuk contract:

```php
namespace App\Modules\Inventory\Inventory\Application\Contracts;

use App\Modules\Inventory\Inventory\Application\DTOs\ConsumeStockData;
use App\Modules\Inventory\Inventory\Application\DTOs\ConsumedStockResult;

interface ConsumesStock
{
    public function consume(ConsumeStockData $data): ConsumedStockResult;
}
```

Aturan:

- Contract berisi bahasa use case, bukan detail database.
- Contract tidak mengembalikan Eloquent model internal.
- Input dan output memakai DTO.
- Error domain penting dilempar sebagai exception yang terdokumentasi.
- Contract harus idempotent jika dipanggil dalam flow yang mungkin retry, atau minimal mendokumentasikan bahwa ia tidak idempotent.

## DTO

DTO dipakai untuk menjaga boundary stabil dan mencegah bocornya internal model.

Contoh:

```php
namespace App\Modules\Inventory\Inventory\Application\DTOs;

final readonly class ConsumeStockData
{
    public function __construct(
        public string $itemId,
        public string $warehouseId,
        public string $sourceType,
        public string $sourceId,
        public string $sourceLineId,
        public string $baseUnitId,
        public string $baseQuantity,
        public string $occurredAt,
    ) {}
}
```

Aturan DTO:

- Gunakan value eksplisit, bukan array bebas.
- Gunakan ULID string untuk ID lintas modul.
- Gunakan string decimal untuk nilai uang/qty presisi tinggi jika belum ada value object bersama.
- DTO tidak boleh membawa Eloquent model.
- Tambahan field harus backward-compatible jika contract sudah dipakai modul lain.

## Domain Event

Domain event adalah event internal domain yang muncul dari perubahan state penting di dalam modul.

Contoh:

```text
SalesInvoicePosted
PurchaseInvoicePosted
StockConsumed
PaymentReceived
```

Aturan:

- Domain event boleh kaya konteks domain internal.
- Domain event tidak otomatis menjadi kontrak publik.
- Domain event hanya dibuat jika ada consumer nyata di dalam modul atau akan dipublish sebagai integration event.
- Jangan membuat domain event spekulatif hanya untuk semua perubahan state.
- Jangan membuat modul lain bergantung pada detail internal domain event tanpa menyatakannya sebagai public event.

## Integration Event

Integration event adalah event publik lintas modul. Ini adalah kontrak observable, jadi harus stabil.

Contoh:

```php
namespace App\Modules\Commerce\Sales\Application\Events;

final readonly class SalesInvoicePosted
{
    public function __construct(
        public string $invoiceId,
        public string $invoiceNo,
        public string $warehouseId,
        public string $customerId,
        public string $postedAt,
        public string $grandTotal,
        public string $cogsTotal,
    ) {}
}
```

Aturan:

- Event publik berada di `Application/Events`.
- Event publik dibuat hanya jika ada consumer lintas modul atau consumer eksternal yang jelas.
- Event publik memakai DTO/value scalar yang stabil.
- Event publik tidak membawa Eloquent model.
- Event name mengikuti bahasa bisnis.
- Tambah field baru boleh, menghapus/mengubah makna field butuh ADR atau work-item eksplisit.
- Listener tidak boleh mengubah transaksi asal secara diam-diam.

## Read Model Dan Query Lintas Modul

Reporting membutuhkan data lintas modul. Untuk ini, boleh memakai read model/query yang disepakati.

Aturan:

- Reporting boleh membaca tabel posted transaction, stock movement, payment ledger, dan read model yang disepakati.
- Reporting tidak boleh melakukan posting, void, consume FIFO, create payment, atau mutasi state operasional.
- Query laporan harus eksplisit tentang status transaksi yang dihitung, default hanya `POSTED`.
- Jika query lintas modul menjadi kompleks atau dipakai banyak tempat, buat read model atau query contract.

## Larangan

Tidak boleh:

- Modul A langsung memanggil Eloquent model modul B untuk write operation.
- Modul A mengubah tabel modul B lewat query builder langsung.
- Controller memanggil beberapa repository lintas modul dan merakit business rule sendiri.
- Event publik membawa model internal.
- DTO menjadi array bebas tanpa contract.
- Menjadikan activity log sebagai mekanisme integrasi.
- Membuat domain event atau integration event tanpa consumer yang jelas.

## Contoh Alur

### Sales Posting

```text
Commerce.Sales
  -> Pricing snapshot dari Catalog/Parties
  -> Inventory ConsumesStock contract
  -> Payments RecordSalesPayment contract
  -> publish SalesInvoicePosted integration event jika ada consumer
```

### Purchase Posting

```text
Commerce.Purchasing
  -> Inventory ReceivesStock contract
  -> Payments RecordPurchasePayment contract
  -> publish PurchaseInvoicePosted integration event jika ada consumer
```

### Sales Return

```text
Commerce.Sales
  -> validasi transaksi asal
  -> Inventory RestoreReturnedStock contract
  -> Payments RecordRefundOrReceivableAdjustment contract
  -> publish SalesReturnPosted integration event jika ada consumer
```

## Checklist Saat Membuat Integrasi Modul

- [ ] Apakah integrasi ini sinkron atau asinkron?
- [ ] Apakah caller membutuhkan hasil langsung?
- [ ] Apakah contract sudah berada di `Application/Contracts`?
- [ ] Apakah input/output memakai DTO?
- [ ] Apakah event publik berada di `Application/Events`?
- [ ] Apakah event punya consumer nyata?
- [ ] Apakah tidak ada Eloquent model internal yang bocor?
- [ ] Apakah error/exception penting terdokumentasi?
- [ ] Apakah test contract atau feature test lintas modul tersedia?
