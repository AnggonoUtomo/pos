<?php

namespace App\Modules\Inventory\Units\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['code', 'name', 'symbol'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32', Rule::unique('units', 'code')],
            'name' => ['required', 'string', 'max:150'],
            'symbol' => ['required', 'string', 'max:16'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
