<?php

namespace App\Modules\Sales\Returns\Application\Queries;

use App\Modules\Finance\Payments\Application\Contracts\SaleRefundPaymentMethods;
use App\Modules\Sales\Returns\Application\Contracts\ReturnRefundRepository;
use Brick\Math\Exception\MathException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use JsonException;

final class QuoteReturnRefund
{
    public function __construct(private ReturnRefundRepository $refunds, private ValueReturnRefund $value, private SaleRefundPaymentMethods $methods) {}

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
                $quote = $this->value->handle($record, $this->refunds->scope($record['invoice_id']));

                return $quote + ['methods' => $quote['total'] === '0.00' ? [] : $this->methods->choices()];
            });
        } catch (InvalidArgumentException|JsonException|MathException $error) {
            throw ValidationException::withMessages(['refund' => $error instanceof InvalidArgumentException ? $error->getMessage() : 'Snapshot retur atau invoice asal tidak valid.']);
        }
    }
}
