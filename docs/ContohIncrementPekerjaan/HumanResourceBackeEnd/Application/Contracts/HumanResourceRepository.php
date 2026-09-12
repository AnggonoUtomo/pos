<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Application\Contracts;

use App\Modules\HumanResource\HumanResource\Application\DTO\EmployeeData;
use App\Modules\HumanResource\HumanResource\Application\DTO\EmployeeListFilter;
use App\Modules\HumanResource\HumanResource\Application\DTO\EmployeeUnitAssignmentData;
use App\Modules\HumanResource\HumanResource\Application\DTO\PaginatedEmployeeData;
use App\Modules\HumanResource\HumanResource\Application\DTO\UpsertEmployeeData;
use App\Modules\HumanResource\HumanResource\Application\DTO\UpsertEmployeeUnitAssignmentData;

interface HumanResourceRepository
{
    public function paginateEmployees(EmployeeListFilter $filter): PaginatedEmployeeData;

    public function findEmployee(string $id): ?EmployeeData;

    public function createEmployee(UpsertEmployeeData $data): EmployeeData;

    /** @param array<string, string|null> $changes */
    public function updateEmployee(string $id, array $changes): ?EmployeeData;

    public function hasActiveUnitAssignments(string $employeeId): bool;

    public function assignEmployeeToUnit(UpsertEmployeeUnitAssignmentData $data): EmployeeUnitAssignmentData;
}
