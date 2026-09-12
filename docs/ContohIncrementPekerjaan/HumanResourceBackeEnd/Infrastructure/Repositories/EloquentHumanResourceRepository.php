<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Infrastructure\Repositories;

use App\Modules\HumanResource\HumanResource\Application\Contracts\HumanResourceRepository;
use App\Modules\HumanResource\HumanResource\Application\DTO\EmployeeData;
use App\Modules\HumanResource\HumanResource\Application\DTO\EmployeeListFilter;
use App\Modules\HumanResource\HumanResource\Application\DTO\EmployeeUnitAssignmentData;
use App\Modules\HumanResource\HumanResource\Application\DTO\PaginatedEmployeeData;
use App\Modules\HumanResource\HumanResource\Application\DTO\UpsertEmployeeData;
use App\Modules\HumanResource\HumanResource\Application\DTO\UpsertEmployeeUnitAssignmentData;
use App\Modules\HumanResource\HumanResource\Infrastructure\Models\EmployeeRecord;
use App\Modules\HumanResource\HumanResource\Infrastructure\Models\EmployeeUnitAssignmentRecord;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentHumanResourceRepository implements HumanResourceRepository
{
    public function paginateEmployees(EmployeeListFilter $filter): PaginatedEmployeeData
    {
        $query = EmployeeRecord::query()
            ->when($filter->search !== null, function ($query) use ($filter): void {
                $query->where(function ($query) use ($filter): void {
                    $query->where('name', 'like', '%'.$filter->search.'%')
                        ->orWhere('employee_no', 'like', '%'.$filter->search.'%')
                        ->orWhere('preferred_name', 'like', '%'.$filter->search.'%');
                });
            })
            ->when($filter->status !== null, fn ($query) => $query->where('status', $filter->status))
            ->when($filter->employmentType !== null, fn ($query) => $query->where('employment_type', $filter->employmentType))
            ->when($filter->primaryUnitId !== null, fn ($query) => $query->where('primary_unit_id', $filter->primaryUnitId))
            ->orderBy($filter->sortField, $filter->sortDirection === 'desc' ? 'desc' : 'asc');

        /** @var LengthAwarePaginator<int, EmployeeRecord> $page */
        $page = $query->paginate($filter->perPage, ['*'], 'page', $filter->page);
        $records = array_values($page->items());
        $primaryUnits = $this->primaryUnitsFor($records);

        return new PaginatedEmployeeData(
            data: array_map(
                fn (EmployeeRecord $record): EmployeeData => $this->map($record, $primaryUnits),
                $records,
            ),
            currentPage: $page->currentPage(),
            perPage: $page->perPage(),
            total: $page->total(),
            lastPage: $page->lastPage(),
        );
    }

    public function findEmployee(string $id): ?EmployeeData
    {
        $record = EmployeeRecord::query()->find($id);

        return $record instanceof EmployeeRecord ? $this->map($record, $this->primaryUnitsFor([$record])) : null;
    }

    public function createEmployee(UpsertEmployeeData $data): EmployeeData
    {
        /** @var EmployeeRecord $record */
        $record = EmployeeRecord::query()->create($data->toArray());

        return $this->map($record->refresh(), $this->primaryUnitsFor([$record]));
    }

    public function updateEmployee(string $id, array $changes): ?EmployeeData
    {
        $record = EmployeeRecord::query()->find($id);

        if (! $record instanceof EmployeeRecord) {
            return null;
        }

        $record->fill($changes);
        $record->save();
        $record->refresh();

        return $this->map($record, $this->primaryUnitsFor([$record]));
    }

    public function hasActiveUnitAssignments(string $employeeId): bool
    {
        return DB::table('employee_unit_assignments')
            ->where('employee_id', $employeeId)
            ->whereNull('ends_on')
            ->exists();
    }

    public function assignEmployeeToUnit(UpsertEmployeeUnitAssignmentData $data): EmployeeUnitAssignmentData
    {
        /** @var EmployeeUnitAssignmentRecord $record */
        $record = EmployeeUnitAssignmentRecord::query()->create($data->toArray());

        return $this->mapAssignment($record->refresh());
    }

    /**
     * @param  list<EmployeeRecord>  $records
     * @return Collection<string, object{id: string, code: string, name: string, status: string}>
     */
    private function primaryUnitsFor(array $records): Collection
    {
        $unitIds = collect($records)
            ->pluck('primary_unit_id')
            ->filter()
            ->unique()
            ->values();

        if ($unitIds->isEmpty()) {
            return collect();
        }

        /** @var Collection<string, object{id: string, code: string, name: string, status: string}> $units */
        $units = DB::table('organization_units')
            ->whereIn('id', $unitIds)
            ->get(['id', 'code', 'name', 'status'])
            ->keyBy('id');

        return $units;
    }

    /** @param Collection<string, object{id: string, code: string, name: string, status: string}> $primaryUnits */
    private function map(EmployeeRecord $record, Collection $primaryUnits): EmployeeData
    {
        $primaryUnit = $record->primary_unit_id === null ? null : $primaryUnits->get((string) $record->primary_unit_id);

        return new EmployeeData(
            id: (string) $record->getKey(),
            primaryUnitId: $record->primary_unit_id === null ? null : (string) $record->primary_unit_id,
            primaryUnit: $primaryUnit === null ? null : [
                'id' => (string) $primaryUnit->id,
                'code' => (string) $primaryUnit->code,
                'name' => (string) $primaryUnit->name,
                'status' => (string) $primaryUnit->status,
            ],
            employeeNo: (string) $record->employee_no,
            name: (string) $record->name,
            preferredName: $record->preferred_name === null ? null : (string) $record->preferred_name,
            employmentType: (string) $record->employment_type,
            position: $record->position === null ? null : (string) $record->position,
            status: (string) $record->status,
            joinedOn: $record->joined_on?->toDateString(),
            leftOn: $record->left_on?->toDateString(),
            notes: $record->notes === null ? null : (string) $record->notes,
            createdAt: $record->created_at->toJSON(),
            updatedAt: $record->updated_at->toJSON(),
        );
    }

    private function mapAssignment(EmployeeUnitAssignmentRecord $record): EmployeeUnitAssignmentData
    {
        return new EmployeeUnitAssignmentData(
            id: (string) $record->getKey(),
            employeeId: (string) $record->employee_id,
            organizationUnitId: (string) $record->organization_unit_id,
            role: (string) $record->role,
            startsOn: $record->starts_on?->toDateString(),
            endsOn: $record->ends_on?->toDateString(),
            isPrimary: (bool) $record->is_primary,
            createdAt: $record->created_at->toJSON(),
            updatedAt: $record->updated_at->toJSON(),
        );
    }
}
