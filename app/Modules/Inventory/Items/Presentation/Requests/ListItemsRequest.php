<?php

namespace App\Modules\Inventory\Items\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListItemsRequest extends FormRequest
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
            'category_id' => ['nullable', 'ulid'], 'brand_id' => ['nullable', 'ulid'],
        ];
    }

    public function filters(): array
    {
        $input = $this->validated();

        return ['search' => trim($input['search'] ?? ''), 'status' => $input['status'] ?? 'all', 'trashed' => $this->boolean('trashed'),
            'category_id' => isset($input['category_id']) ? strtolower($input['category_id']) : null, 'brand_id' => isset($input['brand_id']) ? strtolower($input['brand_id']) : null];
    }
}
