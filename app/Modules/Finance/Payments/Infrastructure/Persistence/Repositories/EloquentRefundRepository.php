<?php

namespace App\Modules\Finance\Payments\Infrastructure\Persistence\Repositories;

use App\Modules\Finance\Payments\Application\Contracts\RefundRepository;
use App\Modules\Finance\Payments\Infrastructure\Persistence\Models\PaymentRefund;
use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;

class EloquentRefundRepository implements RefundRepository
{
    private function transaction(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Refund memerlukan transaction caller.');
        }
    }

    public function findSource(string $sourceId, bool $lock = false): ?array
    {
        if ($lock) {
            $this->transaction();
        }
        $row = DB::table('payment_refunds')->where('source_type', 'sale_return')->where('source_id', $sourceId)
            ->when($lock, fn ($q) => $q->lockForUpdate())->first();
        if ($row === null) {
            return null;
        }
        $lines = DB::table('payment_refund_lines')->where('refund_id', $row->id)->orderBy('line_number')
            ->when($lock, fn ($q) => $q->lockForUpdate())->get()
            ->map(fn ($line) => ['method_id' => $line->method_id, 'method_name' => $line->method_name,
                'method_type' => $line->method_type, 'amount' => (string) BigDecimal::of($line->amount)->toScale(2)])->all();

        return ['id' => $row->id, 'request_hash' => $row->request_hash,
            'paid_at' => Carbon::parse($row->paid_at, config('app.timezone'))->utc()->toISOString(),
            'snapshot' => ['total' => (string) BigDecimal::of($row->total)->toScale(2), 'payments' => $lines]];
    }

    public function create(array $header, array $lines): string
    {
        $this->transaction();
        $model = PaymentRefund::create($header);
        if (! $model->exists) {
            throw new RuntimeException('Pengeluaran refund tidak tersimpan.');
        }
        foreach ($lines as $index => $line) {
            DB::table('payment_refund_lines')->insert($line + ['id' => strtolower((string) Str::ulid()), 'refund_id' => $model->id, 'line_number' => $index + 1]);
        }

        return $model->id;
    }

    public function audit(string $id, ?int $actorId): void
    {
        $this->transaction();
        $model = PaymentRefund::findOrFail($id);
        $logger = activity('finance.payments')->performedOn($model)->event('refunded')
            ->withProperties(['source_type' => 'sale_return', 'source_id' => $model->source_id, 'number' => $model->source_number]);
        if ($actorId === null) {
            $logger->causedByAnonymous();
        } else {
            $logger->causedBy($actorId);
        }
        $audit = $logger->log('Pengeluaran refund retur penjualan');
        if ($audit === null || ! $audit->exists) {
            throw new RuntimeException('Audit refund tidak tersimpan.');
        }
    }
}
