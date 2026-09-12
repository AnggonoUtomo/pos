<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Application\DTO;

final readonly class UpsertEmployeeUnitAssignmentData
{
    public function __construct(
        public string $employeeId,
        public string $organizationUnitId,
        public string $role,
        public ?string $startsOn,
        public ?string $endsOn,
        public bool $isPrimary,
    ) {}

    /** @return array<string, string|bool|null> */
    public function toArray(): array
    {
        return [
            'employee_id' => $this->employeeId,
            'organization_unit_id' => $this->organizationUnitId,
            'role' => $this->role,
            'starts_on' => $this->startsOn,
            'ends_on' => $this->endsOn,
            'is_primary' => $this->isPrimary,
        ];
    }
}
