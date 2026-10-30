<?php

namespace App\Modules\Inventory\Stock\Application\Contracts;

interface StockReportReader
{
    public function summary(?string $warehouseId, int $page = 1, ?int $perPage = null): array;
}
