<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Application\DTO;

final readonly class ActiveEmployeeOptionData
{
    public function __construct(
        public string $id,
        public string $employeeNo,
        public string $name,
        public ?string $primaryUnitId,
        public string $employmentType,
        public ?string $position,
    ) {}
}
