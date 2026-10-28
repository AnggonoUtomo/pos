<?php

namespace App\Modules\Finance\Payments\Application\Contracts;

interface CashbookReader
{
    public function accounts(): array;

    public function statement(?string $accountId, ?string $from, ?string $to, int $page): array;
}
