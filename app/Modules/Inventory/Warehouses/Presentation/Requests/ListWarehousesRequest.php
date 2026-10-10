<?php

namespace App\Modules\Inventory\Warehouses\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListWarehousesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1', 'max:2147483647'],
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['sometimes', Rule::in(['all', 'active', 'inactive'])],
            'trashed' => ['sometimes', 'boolean'],
        ];
    }

    public function filters(): array
    {
        $input = $this->validated();

        return ['search' => trim($input['search'] ?? ''), 'status' => $input['status'] ?? 'all', 'trashed' => $this->boolean('trashed')];
    }
}
