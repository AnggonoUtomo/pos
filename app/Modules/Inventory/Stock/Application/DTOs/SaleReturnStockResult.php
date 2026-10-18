<?php

namespace App\Modules\Inventory\Stock\Application\DTOs;

final readonly class SaleReturnStockResult
{
    public function __construct(public string $id, public string $postedAt) {}
}
