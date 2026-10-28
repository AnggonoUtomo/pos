<?php

namespace App\Modules\Finance\Payments\Application\DTOs;

final readonly class CashDrawerData
{
    public function __construct(public string $id, public string $code, public string $name, public string $balance, public string $startsOn, public string $asOf) {}

    public function toArray(): array
    {
        return ['id' => $this->id, 'code' => $this->code, 'name' => $this->name, 'balance' => $this->balance, 'starts_on' => $this->startsOn, 'as_of' => $this->asOf];
    }
}
