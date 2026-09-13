<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Application\DTO;

final readonly class UpsertEmployeeData
{
    public function __construct(
        public ?string $primaryUnitId,
        public string $employeeNo,
        public string $name,
        public ?string $preferredName,
        public string $employmentType,
        public ?string $position,
        public string $status,
        public ?string $joinedOn,
        public ?string $leftOn,
        public ?string $notes,
    ) {}

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return [
            'primary_unit_id' => $this->primaryUnitId,
            'employee_no' => $this->employeeNo,
            'name' => $this->name,
            'preferred_name' => $this->preferredName,
            'employment_type' => $this->employmentType,
            'position' => $this->position,
            'status' => $this->status,
            'joined_on' => $this->joinedOn,
            'left_on' => $this->leftOn,
            'notes' => $this->notes,
        ];
    }
}
