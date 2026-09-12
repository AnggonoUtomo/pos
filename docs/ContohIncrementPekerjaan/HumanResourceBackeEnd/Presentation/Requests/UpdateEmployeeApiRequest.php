<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateEmployeeApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $employeeId = (string) $this->route('employee');

        return [
            'primary_unit_id' => ['sometimes', 'nullable', 'ulid', Rule::exists('organization_units', 'id')],
            'employee_no' => ['sometimes', 'required', 'string', 'max:40', 'regex:/^[A-Z0-9][A-Z0-9_-]*$/', Rule::unique('employees', 'employee_no')->ignore($employeeId)],
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:180'],
            'preferred_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'employment_type' => ['sometimes', 'required', 'string', Rule::in($this->employmentTypes())],
            'position' => ['sometimes', 'nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'required', 'string', Rule::in(['active', 'inactive'])],
            'joined_on' => ['sometimes', 'nullable', 'date'],
            'left_on' => ['sometimes', 'nullable', 'date', 'prohibited_if:status,active'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->hasAny([
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
            ])) {
                $validator->errors()->add('payload', 'Minimal satu field perubahan wajib diisi.');
            }
        });
    }

    /** @return array<string, string|null> */
    public function changes(): array
    {
        $validated = $this->validated();
        $changes = [];

        foreach ([
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
        ] as $field) {
            if (array_key_exists($field, $validated)) {
                $changes[$field] = $validated[$field] === null ? null : (string) $validated[$field];
            }
        }

        return $changes;
    }

    /** @return list<string> */
    private function employmentTypes(): array
    {
        return ['teacher', 'ustadz', 'musyrif', 'finance_staff', 'administration_staff', 'unit_head', 'staff'];
    }
}
