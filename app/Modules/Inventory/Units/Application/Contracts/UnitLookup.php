<?php

namespace App\Modules\Inventory\Units\Application\Contracts;

use App\Modules\Inventory\Units\Application\DTOs\UnitData;

interface UnitLookup
{
    /** Lock requires a transaction on the shared default connection. */
    public function findActiveById(string $id, bool $lock = false): ?UnitData;

    public function findActiveByCode(string $code): ?UnitData;
}
