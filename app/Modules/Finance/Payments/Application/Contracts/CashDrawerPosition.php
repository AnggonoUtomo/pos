<?php

namespace App\Modules\Finance\Payments\Application\Contracts;

use App\Modules\Finance\Payments\Application\DTOs\CashDrawerData;

interface CashDrawerPosition
{
    public function choices(): array;

    public function read(string $accountId, bool $lock = false): CashDrawerData;
}
