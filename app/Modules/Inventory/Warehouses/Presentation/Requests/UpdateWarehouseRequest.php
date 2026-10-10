<?php

namespace App\Modules\Inventory\Warehouses\Presentation\Requests;

use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends StoreWarehouseRequest
{
    public function rules(): array
    {
        return array_replace(parent::rules(), [
            'code' => ['required', 'string', 'max:32', Rule::unique('warehouses', 'code')->ignore($this->route('id'))],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
