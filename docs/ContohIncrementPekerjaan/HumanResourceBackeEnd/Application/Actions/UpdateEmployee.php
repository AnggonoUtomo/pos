<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Application\Actions;

use App\Modules\HumanResource\HumanResource\Application\Contracts\HumanResourceActivityPublisher;
use App\Modules\HumanResource\HumanResource\Application\Contracts\HumanResourceRepository;
use App\Modules\HumanResource\HumanResource\Application\DTO\EmployeeData;
use Illuminate\Contracts\Auth\Authenticatable;

final readonly class UpdateEmployee
{
    public function __construct(
        private HumanResourceActivityPublisher $activities,
        private HumanResourceRepository $repository,
    ) {}

    /** @param array<string, string|null> $changes */
    public function execute(
        ?Authenticatable $actor,
        string $id,
        array $changes,
        ?string $correlationId = null,
    ): ?EmployeeData {
        if ($this->repository->findEmployee($id) === null) {
            return null;
        }

        return $this->activities->publish(
            actorId: $actor ? (string) $actor->getAuthIdentifier() : null,
            action: 'human_resource.employee.updated',
            subjectType: 'employee',
            mutation: fn (): ?EmployeeData => $this->repository->updateEmployee($id, $changes),
            subjectId: static fn (?EmployeeData $employee): ?string => $employee?->id,
            metadata: static fn (?EmployeeData $employee): array => [
                'changed_fields' => array_keys($changes),
                'to_status' => $employee?->status,
                'result' => $employee === null ? null : self::auditResult($employee),
            ],
            correlationId: $correlationId,
        );
    }

    /** @return array<string, string|null> */
    private static function auditResult(EmployeeData $employee): array
    {
        return [
            'employee_no' => $employee->employeeNo,
            'name' => $employee->name,
            'employment_type' => $employee->employmentType,
            'position' => $employee->position,
            'status' => $employee->status,
            'primary_unit_id' => $employee->primaryUnitId,
            'joined_on' => $employee->joinedOn,
            'left_on' => $employee->leftOn,
        ];
    }
}
