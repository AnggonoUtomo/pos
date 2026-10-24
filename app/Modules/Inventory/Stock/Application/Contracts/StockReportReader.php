<?php

namespace App\Modules\Inventory\Stock\Application\Contracts;

interface StockReportReader
{
    public function summary(?string $warehouseId): array;
}
