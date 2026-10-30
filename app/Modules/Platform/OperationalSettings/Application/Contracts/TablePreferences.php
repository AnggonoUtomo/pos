<?php

namespace App\Modules\Platform\OperationalSettings\Application\Contracts;

interface TablePreferences
{
    public const SIZES = [10, 25, 50, 100];

    public function defaultPerPage(): int;
}
