<?php

namespace App\Modules\Platform\OperationalSettings\Application\Contracts;

interface BackupExecutor
{
    public function run(): string;
}
