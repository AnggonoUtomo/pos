<?php

namespace App\Modules\Finance\Payments\Infrastructure\Persistence\Repositories;

use App\Modules\Finance\Payments\Application\Contracts\PurchasePaymentProofLookup;
use App\Modules\Finance\Payments\Application\DTOs\PurchasePaymentProofData;
use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

final class DatabasePurchasePaymentProofLookup implements PurchasePaymentProofLookup
{
    public function find(string $purchaseId, bool $lock = false): ?PurchasePaymentProofData
    {
        if ($lock && DB::transactionLevel() === 0) {
            throw new LogicException('Proof terkunci memerlukan transaction caller.');
        }
        $row = DB::table('payment_disbursements')->where('source_type', 'purchase')->where('source_id', strtolower($purchaseId))
            ->when($lock, fn ($q) => $q->lockForUpdate())->first();
        if ($row === null || $row->paid_at === null) {
            return null;
        }
        $lines = DB::table('payment_disbursement_lines')->where('disbursement_id', $row->id)->orderBy('line_number')
            ->when($lock, fn ($q) => $q->lockForUpdate())->get();
        $sum = BigDecimal::zero()->toScale(6);
        $payments = [];
        foreach ($lines as $line) {
            $amount = BigDecimal::of((string) $line->amount)->toScale(6);
            if (! $amount->isPositive() || isset($payments[$line->method_id])) {
                return null;
            }
            $payments[$line->method_id] = ['method_id' => $line->method_id, 'method_name' => $line->method_name,
                'method_type' => $line->method_type, 'amount' => (string) $amount];
            $sum = $sum->plus($amount);
        }
        $total = BigDecimal::of((string) $row->total)->toScale(6);
        if (! $total->isPositive() || count($payments) < 1 || count($payments) > 10 || ! $sum->isEqualTo($total)) {
            return null;
        }

        return new PurchasePaymentProofData($row->id, $row->source_id, $row->source_number, (string) $total,
            Carbon::parse($row->paid_at, config('app.timezone'))->utc()->toISOString(), array_values($payments));
    }
}
