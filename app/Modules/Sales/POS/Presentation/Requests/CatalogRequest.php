<?php

namespace App\Modules\Sales\POS\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['warehouse_id', 'customer_id'] as $key) {
            if (is_string($this->input($key))) {
                $this->merge([$key => strtolower(trim($this->input($key)))]);
            }
        }
    }

    public function rules(): array
    {
        return ['warehouse_id' => ['required', 'ulid'], 'customer_id' => ['required', 'ulid'],
            'search' => ['sometimes', 'nullable', 'string', 'max:150'], 'page' => ['sometimes', 'integer', 'min:1', 'max:100000']];
    }
}
