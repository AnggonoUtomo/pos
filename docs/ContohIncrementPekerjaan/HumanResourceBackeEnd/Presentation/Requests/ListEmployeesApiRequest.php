<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Presentation\Requests;

use App\Modules\HumanResource\HumanResource\Application\DTO\EmployeeListFilter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListEmployeesApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'filter' => ['nullable', 'array:status,employment_type,primary_unit_id'],
            'filter.status' => ['nullable', 'string', Rule::in($this->statuses())],
            'filter.employment_type' => ['nullable', 'string', Rule::in($this->employmentTypes())],
            'filter.primary_unit_id' => ['nullable', 'ulid', Rule::exists('organization_units', 'id')],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50, 100])],
            'sort' => ['nullable', 'string', Rule::in(['created_at', '-created_at', 'employee_no', '-employee_no', 'name', '-name', 'joined_on', '-joined_on'])],
        ];
    }

    public function toFilter(): EmployeeListFilter
    {
        $data = $this->validated();
        $filters = is_array($data['filter'] ?? null) ? $data['filter'] : [];
        $sort = (string) ($data['sort'] ?? 'employee_no');

        return new EmployeeListFilter(
            search: isset($data['search']) ? (string) $data['search'] : null,
            status: isset($filters['status']) ? (string) $filters['status'] : null,
            employmentType: isset($filters['employment_type']) ? (string) $filters['employment_type'] : null,
            primaryUnitId: isset($filters['primary_unit_id']) ? (string) $filters['primary_unit_id'] : null,
            page: isset($data['page']) ? (int) $data['page'] : 1,
            perPage: isset($data['per_page']) ? (int) $data['per_page'] : 25,
            sortField: ltrim($sort, '-'),
            sortDirection: str_starts_with($sort, '-') ? 'desc' : 'asc',
        );
    }

    /** @return list<string> */
    private function employmentTypes(): array
    {
        return ['teacher', 'ustadz', 'musyrif', 'finance_staff', 'administration_staff', 'unit_head', 'staff'];
    }

    /** @return list<string> */
    private function statuses(): array
    {
        return ['active', 'inactive'];
    }
}
