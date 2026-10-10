<?php

namespace App\Modules\Inventory\Items\Application\Contracts;

interface ItemUnitUsage
{
    public function isReferenced(string $unitId): bool;
}
