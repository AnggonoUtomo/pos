<?php

namespace App\Modules\Inventory\Stock\Application\DTOs;

final readonly class SaleStockResult
{
    /** @param array<string, string> $lineCosts Source line ID => total FIFO value scale6. */
    public function __construct(public string $id, public array $lineCosts) {}
}
