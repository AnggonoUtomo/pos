<?php

namespace App\Modules\Sales\Parties\Presentation\Requests;

use Illuminate\Validation\Rule;

class SaveCustomerRequest extends SaveCustomerLevelRequest
{
    public function rules(): array
    {
        return array_replace(parent::rules(), [
            'code' => ['required', 'string', 'max:32', Rule::unique('customers', 'code')->ignore(strtolower((string) $this->route('id')))],
            'customer_level_id' => ['required', 'ulid'], 'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:150'], 'address' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
