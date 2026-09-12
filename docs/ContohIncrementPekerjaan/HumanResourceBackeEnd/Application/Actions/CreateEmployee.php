<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Application\Actions;

use App\Modules\HumanResource\HumanResource\Application\Contracts\HumanResourceActivityPublisher;
use App\Modules\HumanResource\HumanResource\Application\Contracts\HumanResourceRepository;
use App\Modules\HumanResource\HumanResource\Application\DTO\EmployeeData;
use App\Modules\HumanResource\HumanResource\Application\DTO\UpsertEmployeeData;
use Illuminate\Contracts\Auth\Authenticatable;

final readonly class CreateEmployee
{
    public function __construct(
        private HumanResourceActivityPublisher $activities,
        private HumanResourceRepository $repository,
    ) {}

    public function execute(
        ?Authenticatable $actor,
        UpsertEmployeeData $data,
        ?string $correlationId = null,
    ): EmployeeData {
        return $this->activities->publish(
            actorId: $actor ? (string) $actor->getAuthIdentifier() : null,
            action: 'human_resource.employee.created',
            subjectType: 'employee',
            mutation: fn (): EmployeeData => $this->repository->createEmployee($data),
            subjectId: static fn (EmployeeData $employee): string => $employee->id,
            metadata: static fn (EmployeeData $employee): array => [
                'changed_fields' => [
                    'primary_unit_id',
                    'employee_no',
                    'name',
                    'preferred_name',
                    'employment_type',
                    'position',
                    'status',
                    'joined_on',
                    'left_on',
                    'notes',
                ],
                'result' => self::auditResult($employee),
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
