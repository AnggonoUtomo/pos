<?php

namespace App\Modules\Finance\Payments\Application\DTOs;

final readonly class PurchasePaymentRequest
{
    public function __construct(public string $sourceId, public string $sourceNumber, public string $total, public array $payments, public ?int $actorId = null) {}
}
