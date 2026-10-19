<?php

namespace App\Modules\Purchasing\Returns\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListPurchaseReturnDraftsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:150'], 'page' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
            'trashed' => ['sometimes', 'boolean'], 'status' => ['sometimes', Rule::in(['all', 'draft'])]];
    }

    public function filters(): array
    {
        return ['search' => trim($this->validated('search') ?? ''), 'trashed' => $this->boolean('trashed'), 'status' => $this->validated('status', 'all')];
    }
}
