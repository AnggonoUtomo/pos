<?php

namespace App\Modules\Purchasing\Suppliers\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListSuppliersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['page' => ['sometimes', 'integer', 'min:1', 'max:2147483647'],
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['sometimes', Rule::in(['all', 'active', 'inactive'])],
            'trashed' => ['sometimes', 'boolean']];
    }

    public function filters(): array
    {
        return ['search' => trim($this->validated('search') ?? ''),
            'status' => $this->validated('status', 'all'), 'trashed' => $this->boolean('trashed')];
    }
}
