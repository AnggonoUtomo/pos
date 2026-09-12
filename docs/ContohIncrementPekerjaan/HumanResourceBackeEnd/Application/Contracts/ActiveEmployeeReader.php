<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Application\Contracts;

use App\Modules\HumanResource\HumanResource\Application\DTO\ActiveEmployeeOptionData;

interface ActiveEmployeeReader
{
    public function findActive(string $employeeId, ?string $primaryUnitId = null, ?string $employmentType = null): ?ActiveEmployeeOptionData;

    /** @return list<ActiveEmployeeOptionData> */
    public function options(?string $primaryUnitId = null, ?string $employmentType = null, ?string $search = null, int $limit = 50): array;
}
