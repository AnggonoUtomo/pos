<?php

namespace App\Modules\Platform\Hardware\Application\Contracts;

interface HardwareAvailability
{
    public function enabled(): bool;

    public function printingEnabled(): bool;

    /** @return list<string> */
    public function allowedHosts(): array;
}
