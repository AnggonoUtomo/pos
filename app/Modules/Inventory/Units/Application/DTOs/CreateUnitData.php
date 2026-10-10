<?php

namespace App\Modules\Inventory\Units\Application\DTOs;

final readonly class CreateUnitData
{
    public function __construct(
        public string $code,
        public string $name,
        public string $symbol,
        public bool $isActive = true,
    ) {}
}
