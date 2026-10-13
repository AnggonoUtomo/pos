<?php

namespace App\Modules\Sales\Parties\Application\DTOs;

final readonly class PriceLevelData
{
    public function __construct(public string $id, public string $code, public string $name) {}
}
