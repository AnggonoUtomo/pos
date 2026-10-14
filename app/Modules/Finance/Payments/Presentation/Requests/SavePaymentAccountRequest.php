<?php

namespace App\Modules\Finance\Payments\Presentation\Requests;

use App\Modules\Finance\Payments\Domain\Enums\PaymentAccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePaymentAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['code', 'name', 'bank_name', 'account_number', 'account_holder'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => $field === 'code' ? strtoupper(trim($this->input($field))) : trim($this->input($field))]);
            }
        }
    }

    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:32', Rule::unique('payment_accounts', 'code')->ignore(strtolower((string) $this->route('id')))],
            'name' => ['required', 'string', 'max:150'], 'type' => ['required', Rule::enum(PaymentAccountType::class)],
            'is_active' => [$this->isMethod('put') ? 'required' : 'sometimes', 'boolean'],
            'bank_name' => ['nullable', 'string', 'max:150', 'prohibited_unless:type,bank'],
            'account_number' => ['nullable', 'string', 'max:64', 'prohibited_unless:type,bank'],
            'account_holder' => ['nullable', 'string', 'max:150', 'prohibited_unless:type,bank']];
    }

    public function messages(): array
    {
        return ['code.unique' => 'Kode sudah digunakan, termasuk pada data terhapus.'];
    }
}
