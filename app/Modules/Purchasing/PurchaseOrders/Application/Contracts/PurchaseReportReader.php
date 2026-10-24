<?php

namespace App\Modules\Purchasing\PurchaseOrders\Application\Contracts;

interface PurchaseReportReader
{
    public function summary(string $from, string $until, ?string $warehouseId): array;
}
