<?php

namespace App\Modules\Inventory\Stock\Application\DTOs;

final readonly class PurchaseReturnStockRequest
{
    /** @param list<array{source_line_id:string,purchase_line_id:string,base_quantity:string}> $lines */
    public function __construct(public string $warehouseId, public string $sourceId, public string $sourceNumber,
        public string $purchaseId, public array $lines, public ?int $actorId = null) {}
}
