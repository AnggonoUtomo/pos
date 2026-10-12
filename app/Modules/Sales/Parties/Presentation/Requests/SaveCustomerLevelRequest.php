<?php

namespace App\Modules\Sales\Parties\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCustomerLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['code', 'name', 'phone', 'email', 'address', 'customer_level_id'] as $field) {
            if (is_string($this->input($field))) {
                $value = trim($this->input($field));
                $this->merge([$field => match ($field) {
                    'code' => strtoupper($value), 'customer_level_id' => strtolower($value), default => $value,
                }]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32', Rule::unique('customer_levels', 'code')->ignore(strtolower((string) $this->route('id')))],
            'name' => ['required', 'string', 'max:150'], 'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
