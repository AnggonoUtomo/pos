<?php

namespace App\Modules\Inventory\Units\Application\DTOs;

final readonly class UnitData
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public string $symbol,
    ) {}
}
