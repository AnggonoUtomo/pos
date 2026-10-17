<?php

namespace App\Modules\Finance\Payments\Infrastructure\Persistence\Repositories;

use App\Modules\Finance\Payments\Application\Contracts\DisbursementRepository;
use App\Modules\Finance\Payments\Infrastructure\Persistence\Models\PaymentDisbursement;
use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;

class EloquentDisbursementRepository implements DisbursementRepository
{
    private function transaction(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Disbursement memerlukan transaction caller.');
        }
    }

    public function findSource(string $sourceId, bool $lock = false): ?array
    {
        if ($lock) {
            $this->transaction();
        }
        $row = DB::table('payment_disbursements')->where('source_type', 'purchase')->where('source_id', $sourceId)->when($lock, fn ($q) => $q->lockForUpdate())->first();
        if ($row === null) {
            return null;
        }
        $lines = DB::table('payment_disbursement_lines')->where('disbursement_id', $row->id)->orderBy('line_number')->get()
            ->map(fn ($line) => ['method_id' => $line->method_id, 'method_name' => $line->method_name, 'method_type' => $line->method_type, 'amount' => (string) BigDecimal::of($line->amount)->toScale(6)])->all();

        return ['id' => $row->id, 'request_hash' => $row->request_hash, 'paid_at' => Carbon::parse($row->paid_at, config('app.timezone'))->utc()->toISOString(),
            'snapshot' => ['total' => (string) BigDecimal::of($row->total)->toScale(6), 'payments' => $lines]];
    }

    public function create(array $header, array $lines): string
    {
        $this->transaction();
        $model = PaymentDisbursement::create($header);
        if (! $model->exists) {
            throw new RuntimeException('Pengeluaran tidak tersimpan.');
        }
        foreach ($lines as $index => $line) {
            DB::table('payment_disbursement_lines')->insert($line + ['id' => strtolower((string) Str::ulid()), 'disbursement_id' => $model->id, 'line_number' => $index + 1]);
        }

        return $model->id;
    }

    public function audit(string $id, ?int $actorId): void
    {
        $this->transaction();
        $model = PaymentDisbursement::findOrFail($id);
        $logger = activity('finance.payments')->performedOn($model)->event('disbursed')->withProperties(['source_type' => 'purchase', 'source_id' => $model->source_id, 'number' => $model->source_number]);
        if ($actorId === null) {
            $logger->causedByAnonymous();
        } else {
            $logger->causedBy($actorId);
        }
        $audit = $logger->log('Pengeluaran pembayaran pembelian');
        if ($audit === null || ! $audit->exists) {
            throw new RuntimeException('Audit pengeluaran tidak tersimpan.');
        }
    }
}
