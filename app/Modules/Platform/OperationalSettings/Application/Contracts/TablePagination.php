<?php

namespace App\Modules\Platform\OperationalSettings\Application\Contracts;

interface TablePagination
{
    public function perPage(): int;
}
