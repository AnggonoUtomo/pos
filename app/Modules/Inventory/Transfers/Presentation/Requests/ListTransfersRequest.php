<?php

namespace App\Modules\Inventory\Transfers\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListTransfersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['page' => ['sometimes', 'integer', 'min:1', 'max:2147483647'], 'search' => ['nullable', 'string', 'max:150'],
            'source_warehouse_id' => ['nullable', 'ulid'], 'destination_warehouse_id' => ['nullable', 'ulid'], 'trashed' => ['sometimes', 'boolean']];
    }

    public function filters(): array
    {
        return ['search' => trim($this->validated('search') ?? ''), 'source_warehouse_id' => strtolower($this->validated('source_warehouse_id') ?? ''),
            'destination_warehouse_id' => strtolower($this->validated('destination_warehouse_id') ?? ''), 'trashed' => $this->boolean('trashed')];
    }
}
