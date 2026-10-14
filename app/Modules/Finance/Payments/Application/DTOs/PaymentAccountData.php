<?php

namespace App\Modules\Finance\Payments\Application\DTOs;

final readonly class PaymentAccountData
{
    public function __construct(public string $code, public string $name, public string $type,
        public ?string $bankName = null, public ?string $accountNumber = null, public ?string $accountHolder = null,
        public bool $isActive = true) {}
}
