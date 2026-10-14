<?php

namespace App\Modules\Finance\Payments\Application\DTOs;

final readonly class PaymentMethodData
{
    public function __construct(public string $code, public string $name, public string $type,
        public string $accountId, public bool $isActive = true) {}
}
