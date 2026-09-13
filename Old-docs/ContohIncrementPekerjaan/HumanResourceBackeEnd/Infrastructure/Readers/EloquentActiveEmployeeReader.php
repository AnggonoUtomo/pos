<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Infrastructure\Readers;

use App\Modules\HumanResource\HumanResource\Application\Contracts\ActiveEmployeeReader;
use App\Modules\HumanResource\HumanResource\Application\DTO\ActiveEmployeeOptionData;
use App\Modules\HumanResource\HumanResource\Infrastructure\Models\EmployeeRecord;

final class EloquentActiveEmployeeReader implements ActiveEmployeeReader
{
    public function findActive(string $employeeId, ?string $primaryUnitId = null, ?string $employmentType = null): ?ActiveEmployeeOptionData
    {
        $record = EmployeeRecord::query()
            ->whereKey($employeeId)
            ->where('status', 'active')
            ->when($primaryUnitId !== null, fn ($query) => $query->where('primary_unit_id', $primaryUnitId))
            ->when($employmentType !== null, fn ($query) => $query->where('employment_type', $employmentType))
            ->first(['id', 'employee_no', 'name', 'primary_unit_id', 'employment_type', 'position']);

        return $record instanceof EmployeeRecord ? $this->map($record) : null;
    }

    public function options(?string $primaryUnitId = null, ?string $employmentType = null, ?string $search = null, int $limit = 50): array
    {
        $safeLimit = max(1, min($limit, 100));

        return EmployeeRecord::query()
            ->where('status', 'active')
            ->when($primaryUnitId !== null, fn ($query) => $query->where('primary_unit_id', $primaryUnitId))
            ->when($employmentType !== null, fn ($query) => $query->where('employment_type', $employmentType))
            ->when($search !== null && trim($search) !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('employee_no', 'like', '%'.$search.'%')
                        ->orWhere('preferred_name', 'like', '%'.$search.'%')
                        ->orWhere('position', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->limit($safeLimit)
            ->get(['id', 'employee_no', 'name', 'primary_unit_id', 'employment_type', 'position'])
            ->map(fn (EmployeeRecord $record): ActiveEmployeeOptionData => $this->map($record))
            ->values()
            ->all();
    }

    private function map(EmployeeRecord $record): ActiveEmployeeOptionData
    {
        return new ActiveEmployeeOptionData(
            id: (string) $record->getKey(),
            employeeNo: (string) $record->employee_no,
            name: (string) $record->name,
            primaryUnitId: $record->primary_unit_id === null ? null : (string) $record->primary_unit_id,
            employmentType: (string) $record->employment_type,
            position: $record->position === null ? null : (string) $record->position,
        );
    }
}
