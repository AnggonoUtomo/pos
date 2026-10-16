<?php

namespace App\Modules\Inventory\Stock\Application\Contracts;

use App\Modules\Inventory\Stock\Application\DTOs\SaleStockRequest;
use App\Modules\Inventory\Stock\Application\DTOs\SaleStockResult;

interface SaleStockWriter
{
    public function issue(SaleStockRequest $request): SaleStockResult;
}
