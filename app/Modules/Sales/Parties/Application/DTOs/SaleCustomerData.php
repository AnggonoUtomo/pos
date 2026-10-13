<?php

namespace App\Modules\Sales\Parties\Application\DTOs;

final readonly class SaleCustomerData
{
    public function __construct(public string $id, public string $code, public string $name,
        public string $levelId, public string $levelName) {}
}
