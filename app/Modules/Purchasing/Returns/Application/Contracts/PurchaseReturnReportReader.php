<?php

namespace App\Modules\Purchasing\Returns\Application\Contracts;

interface PurchaseReturnReportReader
{
    public function summary(string $from, string $until, ?string $warehouseId): array;
}
