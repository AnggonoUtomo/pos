<?php

namespace App\Modules\Finance\Payments\Application\DTOs;

final readonly class SaleRefundPaymentRequest
{
    /** @param list<array{method_id: string, amount: string}> $payments */
    public function __construct(public string $sourceId, public string $sourceNumber, public string $total, public array $payments, public ?int $actorId = null) {}
}
