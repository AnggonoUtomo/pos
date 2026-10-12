<?php

namespace App\Modules\Inventory\Stock\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['tab' => ['sometimes', Rule::in(['openings', 'balances', 'movements'])],
            'page' => ['sometimes', 'integer', 'min:1', 'max:2147483647'], 'search' => ['nullable', 'string', 'max:150'],
            'warehouse_id' => ['nullable', 'ulid'], 'item_id' => ['nullable', 'ulid'],
            'status' => ['sometimes', Rule::in(['all', 'draft', 'posted'])], 'trashed' => ['sometimes', 'boolean']];
    }

    public function filters(): array
    {
        $data = $this->validated();

        return ['tab' => $data['tab'] ?? 'openings', 'search' => trim($data['search'] ?? ''),
            'warehouse_id' => $data['warehouse_id'] ?? '', 'item_id' => $data['item_id'] ?? '',
            'status' => $data['status'] ?? 'all', 'trashed' => $this->boolean('trashed')];
    }
}
