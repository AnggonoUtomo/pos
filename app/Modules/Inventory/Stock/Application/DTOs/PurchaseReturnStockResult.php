<?php

namespace App\Modules\Inventory\Stock\Application\DTOs;

final readonly class PurchaseReturnStockResult
{
    public function __construct(public string $id, public string $postedAt) {}
}
