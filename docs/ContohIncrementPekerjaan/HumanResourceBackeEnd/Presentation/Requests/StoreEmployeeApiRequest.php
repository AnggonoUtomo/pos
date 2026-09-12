<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Presentation\Requests;

use App\Modules\HumanResource\HumanResource\Application\DTO\UpsertEmployeeData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreEmployeeApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'primary_unit_id' => ['nullable', 'ulid', Rule::exists('organization_units', 'id')],
            'employee_no' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9][A-Z0-9_-]*$/', Rule::unique('employees', 'employee_no')],
            'name' => ['required', 'string', 'min:2', 'max:180'],
            'preferred_name' => ['nullable', 'string', 'max:120'],
            'employment_type' => ['required', 'string', Rule::in($this->employmentTypes())],
            'position' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            'joined_on' => ['nullable', 'date'],
            'left_on' => ['nullable', 'date', 'after_or_equal:joined_on', 'prohibited_if:status,active'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function toData(): UpsertEmployeeData
    {
        $data = $this->validated();

        return new UpsertEmployeeData(
            primaryUnitId: isset($data['primary_unit_id']) ? (string) $data['primary_unit_id'] : null,
            employeeNo: (string) $data['employee_no'],
            name: (string) $data['name'],
            preferredName: isset($data['preferred_name']) ? (string) $data['preferred_name'] : null,
            employmentType: (string) $data['employment_type'],
            position: isset($data['position']) ? (string) $data['position'] : null,
            status: (string) $data['status'],
            joinedOn: isset($data['joined_on']) ? (string) $data['joined_on'] : null,
            leftOn: isset($data['left_on']) ? (string) $data['left_on'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
        );
    }

    /** @return list<string> */
    private function employmentTypes(): array
    {
        return ['teacher', 'ustadz', 'musyrif', 'finance_staff', 'administration_staff', 'unit_head', 'staff'];
    }
}
