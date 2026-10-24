<?php

namespace App\Modules\Inventory\Stock\Application\Contracts;

use App\Modules\Inventory\Stock\Application\DTOs\TransferStockRequest;

interface TransferStockWriter
{
    public function transfer(TransferStockRequest $request): void;
}
