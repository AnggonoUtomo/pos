<?php

namespace App\Modules\Inventory\Stock\Application\DTOs;

final readonly class PurchaseStockRequest
{
    /** @param list<array{source_line_id:string,item_id:string,base_unit_id:string,base_quantity:string,total_value:string}> $lines */
    public function __construct(public string $warehouseId, public string $sourceId, public string $sourceNumber, public array $lines, public ?int $actorId = null) {}
}
