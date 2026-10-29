<?php

namespace App\Modules\Finance\Payments\Application\Contracts;

interface SalePaymentMethods
{
    /** @return list<array{id: string, code: string, name: string, type: string}> */
    public function choices(): array;

    public function forDrawer(string $accountId): array;
}
