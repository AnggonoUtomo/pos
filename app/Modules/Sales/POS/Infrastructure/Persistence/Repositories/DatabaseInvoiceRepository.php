<?php

namespace App\Modules\Sales\POS\Infrastructure\Persistence\Repositories;

use App\Modules\Sales\POS\Application\Contracts\InvoiceRepository;
use App\Modules\Sales\POS\Infrastructure\Persistence\Models\Invoice;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DatabaseInvoiceRepository implements InvoiceRepository
{
    public function lockByKey(int $actorId, string $key): ?array
    {
        $this->assertTransaction();
        $row = DB::table('sales_invoices')->where('actor_id', $actorId)->where('idempotency_key', $key)->lockForUpdate()->first();

        return $row === null ? null : $this->read((array) $row, true);
    }

    public function find(string $id): ?array
    {
        $row = DB::table('sales_invoices')->where('id', $id)->first();

        return $row === null ? null : $this->read((array) $row, false);
    }

    private function read(array $row, bool $lock): array
    {
        $row['actor_id'] = (int) $row['actor_id'];
        $row['payments'] = json_decode($row['payments'], true, flags: JSON_THROW_ON_ERROR);
        $row['posted_at'] = Carbon::parse($row['posted_at'], config('app.timezone'))->utc()->toISOString();
        foreach (['subtotal', 'total', 'discount_amount', 'tax_amount', 'total_cost'] as $key) {
            $row[$key] = (string) BigDecimal::of((string) $row[$key])->toScale($key === 'total_cost' ? 6 : 2);
        }
        $row['lines'] = DB::table('sales_invoice_lines')->where('invoice_id', $row['id'])->orderBy('line_number')
            ->when($lock, fn ($q) => $q->lockForUpdate())->get()->map(function ($line): array {
                $line = (array) $line;
                foreach (['quantity', 'base_quantity', 'conversion_ratio', 'unit_price', 'subtotal', 'discount_amount', 'tax_amount', 'total_cost'] as $key) {
                    $line[$key] = (string) BigDecimal::of((string) $line[$key])->toScale(in_array($key, ['quantity', 'base_quantity', 'conversion_ratio', 'total_cost'], true) ? 6 : 2);
                }

                return $line;
            })->all();

        return $row;
    }

    public function createPosted(array $header, array $lines): void
    {
        $this->assertTransaction();
        $invoice = Invoice::create($header);
        if (! $invoice->exists) {
            throw new \RuntimeException('Invoice tidak tersimpan.');
        }
        foreach ($lines as $line) {
            DB::table('sales_invoice_lines')->insert($line + ['invoice_id' => $header['id']]);
        }
    }

    public function audit(string $id, int $actorId): void
    {
        $this->assertTransaction();
        $invoice = Invoice::findOrFail($id);
        $audit = activity('sales.pos')->performedOn($invoice)->causedBy($actorId)->event('posted')
            ->withProperties(['number' => $invoice->number, 'warehouse_id' => $invoice->warehouse_id])
            ->log('Posting penjualan POS');
        if ($audit === null || ! $audit->exists) {
            throw new \RuntimeException('Audit invoice tidak tersimpan.');
        }
    }

    private function assertTransaction(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Persistensi/locking invoice memerlukan transaction default.');
        }
    }
}
