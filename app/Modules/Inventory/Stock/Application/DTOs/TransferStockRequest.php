<?php

namespace App\Modules\Inventory\Stock\Application\DTOs;

final readonly class TransferStockRequest
{
    public function __construct(public string $id, public string $number, public string $sourceWarehouseId,
        public string $destinationWarehouseId, public array $lines, public ?int $actorId) {}
}
