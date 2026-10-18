<?php

namespace App\Modules\Inventory\Stock\Application\DTOs;

final readonly class SaleReturnStockRequest
{
    /** @param list<array{source_line_id:string,sale_line_id:string,base_quantity:string}> $lines */
    public function __construct(public string $warehouseId, public string $sourceId, public string $sourceNumber,
        public string $invoiceId, public array $lines, public ?int $actorId = null) {}
}
