<?php

namespace App\Modules\Inventory\Units\Presentation\Requests;

use Illuminate\Validation\Rule;

class UpdateUnitRequest extends StoreUnitRequest
{
    public function rules(): array
    {
        return array_replace(parent::rules(), [
            'code' => ['required', 'string', 'max:32', Rule::unique('units', 'code')->ignore($this->route('id'))],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
