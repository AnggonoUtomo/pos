<?php

namespace App\Modules\Purchasing\Suppliers\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:32'], 'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:32'], 'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'], 'is_active' => ['sometimes', 'boolean']];
    }
}
