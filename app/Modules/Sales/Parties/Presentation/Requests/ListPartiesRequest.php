<?php

namespace App\Modules\Sales\Parties\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPartiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['page' => ['sometimes', 'integer', 'min:1', 'max:2147483647'],
            'search' => ['nullable', 'string', 'max:150'], 'status' => ['sometimes', Rule::in(['all', 'active', 'inactive'])],
            'trashed' => ['sometimes', 'boolean'], 'customer_level_id' => ['nullable', 'ulid'],
        ];
    }

    public function filters(): array
    {
        $input = $this->validated();

        return ['search' => trim($input['search'] ?? ''), 'status' => $input['status'] ?? 'all',
            'trashed' => $this->boolean('trashed'), 'customer_level_id' => empty($input['customer_level_id']) ? null : strtolower($input['customer_level_id'])];
    }
}
