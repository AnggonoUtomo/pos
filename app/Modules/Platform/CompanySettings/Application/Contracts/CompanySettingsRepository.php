<?php

namespace App\Modules\Platform\CompanySettings\Application\Contracts;

interface CompanySettingsRepository
{
    public function update(array $fields, int $version, ?int $actorId): void;
}
