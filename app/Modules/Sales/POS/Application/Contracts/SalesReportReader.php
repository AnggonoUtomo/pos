<?php

namespace App\Modules\Sales\POS\Application\Contracts;

interface SalesReportReader
{
    public function summary(string $from, string $until, ?string $warehouseId): array;
}
