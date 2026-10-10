<?php

namespace App\Modules\Inventory\Warehouses\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['code', 'name'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32', Rule::unique('warehouses', 'code')],
            'name' => ['required', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
