<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Application\DTO;

final readonly class EmployeeData
{
    /**
     * @param  array{id: string, code: string, name: string, status: string}|null  $primaryUnit
     */
    public function __construct(
        public string $id,
        public ?string $primaryUnitId,
        public ?array $primaryUnit,
        public string $employeeNo,
        public string $name,
        public ?string $preferredName,
        public string $employmentType,
        public ?string $position,
        public string $status,
        public ?string $joinedOn,
        public ?string $leftOn,
        public ?string $notes,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'primary_unit_id' => $this->primaryUnitId,
            'primary_unit' => $this->primaryUnit,
            'employee_no' => $this->employeeNo,
            'name' => $this->name,
            'preferred_name' => $this->preferredName,
            'employment_type' => $this->employmentType,
            'position' => $this->position,
            'status' => $this->status,
            'joined_on' => $this->joinedOn,
            'left_on' => $this->leftOn,
            'notes' => $this->notes,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
