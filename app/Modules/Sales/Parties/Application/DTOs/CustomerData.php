<?php

namespace App\Modules\Sales\Parties\Application\DTOs;

final readonly class CustomerData
{
    public function __construct(
        public string $code, public string $name, public string $levelId,
        public ?string $phone = null, public ?string $email = null, public ?string $address = null,
        public bool $isActive = true,
    ) {}
}
