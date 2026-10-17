<?php

namespace App\Modules\Inventory\Stock\Application\Contracts;

use App\Modules\Inventory\Stock\Application\DTOs\PurchaseStockRequest;
use App\Modules\Inventory\Stock\Application\DTOs\PurchaseStockResult;

interface PurchaseStockWriter
{
    /** Requires caller transaction; source replay is immutable and payload-sensitive. */
    public function receive(PurchaseStockRequest $request): PurchaseStockResult;
}
