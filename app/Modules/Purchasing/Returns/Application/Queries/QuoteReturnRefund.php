<?php

namespace App\Modules\Purchasing\Returns\Application\Queries;

use App\Modules\Finance\Payments\Application\Contracts\PurchaseRefundReceiptMethods;
use App\Modules\Purchasing\Returns\Application\Contracts\ReturnRefundRepository;
use Brick\Math\Exception\MathException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use JsonException;

final class QuoteReturnRefund
{
    public function __construct(private ReturnRefundRepository $refunds, private ValueReturnRefund $value, private PurchaseRefundReceiptMethods $methods) {}

    public function handle(string $id): array
    {
        if (! $this->refunds->available()) {
            throw ValidationException::withMessages(['refund' => 'Refund belum diaktifkan pada database ini.']);
        }
        try {
            return DB::transaction(function () use ($id): array {
                $record = $this->refunds->findReturn(strtolower($id));
                if ($this->refunds->settlement($record['id']) !== null) {
                    throw new InvalidArgumentException('Refund retur sudah diselesaikan.');
                }
                $quote = $this->value->handle($record, $this->refunds->scope($record['purchase_order_id']));

                return $quote + ['methods' => $quote['total'] === '0.000000' ? [] : $this->methods->choices()];
            });
        } catch (InvalidArgumentException|JsonException|MathException $error) {
            throw ValidationException::withMessages(['refund' => $error instanceof InvalidArgumentException ? $error->getMessage() : 'Snapshot retur atau pembelian asal tidak valid.']);
        }
    }
}
