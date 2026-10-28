<?php

namespace App\Modules\Sales\POS\Application\Contracts;

interface ActiveShiftLookup
{
    public function hasActiveShift(int $actorId): bool;
}
