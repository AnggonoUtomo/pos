<?php

namespace App\Modules\Inventory\Stock\Application\Contracts;

use App\Modules\Inventory\Stock\Application\DTOs\PurchaseReturnStockRequest;
use App\Modules\Inventory\Stock\Application\DTOs\PurchaseReturnStockResult;

interface PurchaseReturnStockWriter
{
    /** Requires caller transaction; source replay is immutable and payload-sensitive. */
    public function issue(PurchaseReturnStockRequest $request): PurchaseReturnStockResult;
}
