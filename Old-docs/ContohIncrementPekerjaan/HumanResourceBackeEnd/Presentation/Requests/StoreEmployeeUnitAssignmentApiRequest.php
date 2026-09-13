<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Presentation\Requests;

use App\Modules\HumanResource\HumanResource\Application\DTO\UpsertEmployeeUnitAssignmentData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreEmployeeUnitAssignmentApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'organization_unit_id' => ['required', 'ulid', Rule::exists('organization_units', 'id')],
            'role' => ['required', 'string', Rule::in($this->roles())],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }

    public function toData(string $employeeId): UpsertEmployeeUnitAssignmentData
    {
        $data = $this->validated();

        return new UpsertEmployeeUnitAssignmentData(
            employeeId: $employeeId,
            organizationUnitId: (string) $data['organization_unit_id'],
            role: (string) $data['role'],
            startsOn: isset($data['starts_on']) ? (string) $data['starts_on'] : null,
            endsOn: isset($data['ends_on']) ? (string) $data['ends_on'] : null,
            isPrimary: isset($data['is_primary']) && (bool) $data['is_primary'],
        );
    }

    /** @return list<string> */
    private function roles(): array
    {
        return ['teacher', 'ustadz', 'musyrif', 'finance_staff', 'administration_staff', 'unit_head', 'staff'];
    }
}
