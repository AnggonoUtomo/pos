<?php

namespace App\Modules\Inventory\Stock\Application\DTOs;

final readonly class PurchaseStockResult
{
    public function __construct(public string $id, public string $postedAt) {}
}
