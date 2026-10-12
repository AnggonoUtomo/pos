<?php

namespace App\Modules\Sales\Parties\Application\DTOs;

final readonly class CustomerLevelData
{
    public function __construct(public string $code, public string $name, public bool $isActive = true) {}
}
