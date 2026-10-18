<?php

namespace App\Modules\Inventory\Stock\Application\Contracts;

use App\Modules\Inventory\Stock\Application\DTOs\SaleReturnStockRequest;
use App\Modules\Inventory\Stock\Application\DTOs\SaleReturnStockResult;

interface SaleReturnStockWriter
{
    /** Requires the caller's shared transaction. Never accepts a free cost. */
    public function receive(SaleReturnStockRequest $request): SaleReturnStockResult;
}
