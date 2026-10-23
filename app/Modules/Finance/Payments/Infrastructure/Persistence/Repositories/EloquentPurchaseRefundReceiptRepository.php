<?php

namespace App\Modules\Finance\Payments\Infrastructure\Persistence\Repositories;

use App\Modules\Finance\Payments\Application\Contracts\PurchaseRefundReceiptRepository;
use App\Modules\Finance\Payments\Infrastructure\Persistence\Models\PurchaseRefundReceipt;
use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;

class EloquentPurchaseRefundReceiptRepository implements PurchaseRefundReceiptRepository
{
    public function receivedForPurchase(string $disbursementId): string
    {
        $this->transaction();
        $total = BigDecimal::zero()->toScale(6);
        foreach (DB::table('purchase_refund_receipts')->where('disbursement_id', $disbursementId)->orderBy('id')->lockForUpdate()->get(['total']) as $row) {
            $total = $total->plus((string) $row->total);
        }

        return (string) $total;
    }

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
        $row = DB::table('purchase_refund_receipts')->where('source_type', 'purchase_return')->where('source_id', $sourceId)
            ->when($lock, fn ($q) => $q->lockForUpdate())->first();
        if ($row === null) {
            return null;
        }
        $lines = DB::table('purchase_refund_receipt_lines')->where('receipt_id', $row->id)->orderBy('line_number')
            ->when($lock, fn ($q) => $q->lockForUpdate())->get()
            ->map(fn ($line) => ['method_id' => $line->method_id, 'method_name' => $line->method_name,
                'method_type' => $line->method_type, 'amount' => (string) BigDecimal::of($line->amount)->toScale(6)])->all();

        return ['id' => $row->id, 'request_hash' => $row->request_hash,
            'received_at' => Carbon::parse($row->received_at, config('app.timezone'))->utc()->toISOString(),
            'snapshot' => ['total' => (string) BigDecimal::of($row->total)->toScale(6), 'payments' => $lines]];
    }

    public function create(array $header, array $lines): string
    {
        $this->transaction();
        $model = PurchaseRefundReceipt::create($header);
        if (! $model->exists) {
            throw new RuntimeException('Penerimaan uang retur tidak tersimpan.');
        }
        foreach ($lines as $index => $line) {
            DB::table('purchase_refund_receipt_lines')->insert($line + ['id' => strtolower((string) Str::ulid()), 'receipt_id' => $model->id, 'line_number' => $index + 1]);
        }

        return $model->id;
    }

    public function audit(string $id, ?int $actorId): void
    {
        $this->transaction();
        $model = PurchaseRefundReceipt::findOrFail($id);
        $logger = activity('finance.payments')->performedOn($model)->event('supplier_refund_received')
            ->withProperties(['source_type' => 'purchase_return', 'source_id' => $model->source_id, 'number' => $model->source_number]);
        if ($actorId === null) {
            $logger->causedByAnonymous();
        } else {
            $logger->causedBy($actorId);
        }
        $audit = $logger->log('Penerimaan uang retur supplier');
        if ($audit === null || ! $audit->exists) {
            throw new RuntimeException('Audit refund tidak tersimpan.');
        }
    }
}
