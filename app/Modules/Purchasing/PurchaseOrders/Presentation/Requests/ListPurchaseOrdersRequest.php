<?php

namespace App\Modules\Purchasing\PurchaseOrders\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListPurchaseOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['page' => ['sometimes', 'integer', 'min:1', 'max:2147483647'], 'search' => ['nullable', 'string', 'max:150'],
            'supplier_id' => ['nullable', 'ulid'], 'warehouse_id' => ['nullable', 'ulid'], 'trashed' => ['sometimes', 'boolean']];
    }

    public function filters(): array
    {
        return ['search' => trim($this->validated('search') ?? ''), 'supplier_id' => strtolower($this->validated('supplier_id') ?? ''),
            'warehouse_id' => strtolower($this->validated('warehouse_id') ?? ''), 'trashed' => $this->boolean('trashed')];
    }
}
