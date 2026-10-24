<?php

namespace App\Modules\Platform\OperationalSettings\Application\Contracts;

interface PrintingPreferences
{
    /** @return array{paper: string} */
    public function printing(): array;
}
