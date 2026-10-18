<?php

namespace App\Modules\Sales\Returns\Infrastructure\Persistence\Repositories;

use App\Modules\Sales\Returns\Application\Contracts\ReturnRefundRepository;
use App\Modules\Sales\Returns\Infrastructure\Persistence\Models\ReturnSettlement;
use App\Modules\Sales\Returns\Infrastructure\Persistence\Models\SalesReturn;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;

class EloquentReturnRefundRepository implements ReturnRefundRepository
{
    private function transaction(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Refund Returns memerlukan transaction default.');
        }
    }

    public function available(): bool
    {
        return Schema::hasTable('sales_return_refund_scopes') && Schema::hasTable('sales_return_settlements') && Schema::hasTable('sales_return_refund_allocations');
    }

    public function findReturn(string $id, bool $lock = false): array
    {
        if ($lock) {
            $this->transaction();
        }
        $header = SalesReturn::whereKey($id)->when($lock, fn ($q) => $q->lockForUpdate())->firstOrFail()->toArray();
        $header['lines'] = DB::table('sales_return_lines')->where('sales_return_id', $id)->orderBy('invoice_line_id')
            ->when($lock, fn ($q) => $q->lockForUpdate())->get()->map(function ($row): array {
                $line = (array) $row;
                $line['snapshot'] = json_decode($row->snapshot, true, flags: JSON_THROW_ON_ERROR);
                foreach (['quantity', 'base_quantity'] as $field) {
                    $line[$field] = (string) BigDecimal::of($line[$field])->toScale(6);
                }

                return $line;
            })->all();

        return $header;
    }

    public function scope(string $invoiceId, bool $lock = false): ?array
    {
        if ($lock) {
            $this->transaction();
            DB::table('sales_return_refund_scopes')->insertOrIgnore(['id' => strtolower((string) Str::ulid()), 'invoice_id' => $invoiceId, 'revision' => 0]);
        }
        $row = DB::table('sales_return_refund_scopes')->where('invoice_id', $invoiceId)->when($lock, fn ($q) => $q->lockForUpdate())->first();

        return $row === null ? null : ['id' => $row->id, 'invoice_id' => $row->invoice_id, 'revision' => (int) $row->revision];
    }

    public function settlement(string $returnId, bool $lock = false): ?array
    {
        if ($lock) {
            $this->transaction();
        }

        return ReturnSettlement::where('sales_return_id', $returnId)->when($lock, fn ($q) => $q->lockForUpdate())->first()?->toArray();
    }

    public function allocations(string $scopeId, bool $lock = false): array
    {
        if ($lock) {
            $this->transaction();
        }
        $rows = DB::table('sales_return_refund_allocations as a')->join('sales_return_settlements as s', 's.id', '=', 'a.settlement_id')
            ->where('s.scope_id', $scopeId)->orderBy('a.invoice_line_id')->orderBy('a.id')->when($lock, fn ($q) => $q->lockForUpdate())
            ->get(['a.invoice_line_id', 'a.quantity', 'a.amount']);
        $totals = [];
        foreach ($rows as $row) {
            $current = $totals[$row->invoice_line_id] ?? ['quantity' => '0.000000', 'amount' => '0.00'];
            $totals[$row->invoice_line_id] = ['quantity' => (string) BigDecimal::of($current['quantity'])->plus($row->quantity)->toScale(6),
                'amount' => (string) BigDecimal::of($current['amount'])->plus($row->amount)->toScale(2)];
        }

        return $totals;
    }

    public function save(array $header, array $lines): void
    {
        $this->transaction();
        $model = ReturnSettlement::create($header);
        if (! $model->exists) {
            throw new RuntimeException('Penyelesaian refund gagal disimpan.');
        }
        foreach ($lines as $line) {
            DB::table('sales_return_refund_allocations')->insert($line + ['id' => strtolower((string) Str::ulid()), 'settlement_id' => $model->id]);
        }
        $changed = DB::table('sales_return_refund_scopes')->where('id', $header['scope_id'])->where('revision', $header['scope_revision'])->increment('revision');
        if ($changed !== 1) {
            throw new RuntimeException('Revisi refund invoice berubah.');
        }
    }

    public function audit(string $id, ?int $actorId): void
    {
        $this->transaction();
        $model = ReturnSettlement::findOrFail($id);
        $logger = activity('sales.returns')->performedOn($model)->event('refund_settled')
            ->withProperties(['sales_return_id' => $model->sales_return_id, 'outcome' => $model->outcome, 'finance_refund_id' => $model->finance_refund_id]);
        $actorId === null ? $logger->causedByAnonymous() : $logger->causedBy($actorId);
        $log = $logger->log('Penyelesaian refund retur penjualan');
        if ($log === null || ! $log->exists) {
            throw new RuntimeException('Audit penyelesaian refund gagal disimpan.');
        }
    }
}
