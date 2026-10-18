<?php

namespace App\Modules\Sales\Returns\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListReturnDraftsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:150'], 'page' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
            'trashed' => ['sometimes', 'boolean']];
    }

    public function filters(): array
    {
        return ['search' => trim($this->validated('search') ?? ''), 'trashed' => (bool) $this->validated('trashed', false)];
    }
}
