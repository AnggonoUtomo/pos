<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Application\Actions;

use App\Modules\HumanResource\HumanResource\Application\Contracts\HumanResourceActivityPublisher;
use App\Modules\HumanResource\HumanResource\Application\Contracts\HumanResourceRepository;
use App\Modules\HumanResource\HumanResource\Application\DTO\EmployeeUnitAssignmentData;
use App\Modules\HumanResource\HumanResource\Application\DTO\UpsertEmployeeUnitAssignmentData;
use Illuminate\Contracts\Auth\Authenticatable;

final readonly class AssignEmployeeToUnit
{
    public function __construct(
        private HumanResourceActivityPublisher $activities,
        private HumanResourceRepository $repository,
    ) {}

    public function execute(
        ?Authenticatable $actor,
        string $employeeId,
        UpsertEmployeeUnitAssignmentData $data,
        ?string $correlationId = null,
    ): ?EmployeeUnitAssignmentData {
        if ($this->repository->findEmployee($employeeId) === null) {
            return null;
        }

        return $this->activities->publish(
            actorId: $actor ? (string) $actor->getAuthIdentifier() : null,
            action: 'human_resource.employee.assigned_to_unit',
            subjectType: 'employee_unit_assignment',
            mutation: fn (): EmployeeUnitAssignmentData => $this->repository->assignEmployeeToUnit($data),
            subjectId: static fn (EmployeeUnitAssignmentData $assignment): string => $assignment->id,
            metadata: static fn (EmployeeUnitAssignmentData $assignment): array => [
                'changed_fields' => [
                    'employee_id',
                    'organization_unit_id',
                    'role',
                    'starts_on',
                    'ends_on',
                    'is_primary',
                ],
                'employee_id' => $assignment->employeeId,
                'result' => $assignment->toArray(),
            ],
            correlationId: $correlationId,
        );
    }
}
