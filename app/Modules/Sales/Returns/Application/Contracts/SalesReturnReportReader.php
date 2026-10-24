<?php

namespace App\Modules\Sales\Returns\Application\Contracts;

interface SalesReturnReportReader
{
    public function summary(string $from, string $until, ?string $warehouseId): array;
}
