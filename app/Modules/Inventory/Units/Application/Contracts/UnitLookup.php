<?php

namespace App\Modules\Inventory\Units\Application\Contracts;

use App\Modules\Inventory\Units\Application\DTOs\UnitData;

interface UnitLookup
{
    /** @return list<UnitData> At most 1000 active choices for master forms. */
    public function listActive(): array;

    /** Lock requires a transaction on the shared default connection. */
    public function findActiveById(string $id, bool $lock = false): ?UnitData;

    public function findActiveByCode(string $code): ?UnitData;
}
