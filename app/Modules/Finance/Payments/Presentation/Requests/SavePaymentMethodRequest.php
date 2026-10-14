<?php

namespace App\Modules\Finance\Payments\Presentation\Requests;

use App\Modules\Finance\Payments\Domain\Enums\PaymentMethodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['code', 'name', 'payment_account_id'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => match ($field) {
                    'code' => strtoupper(trim($this->input($field))), 'payment_account_id' => strtolower(trim($this->input($field))), default => trim($this->input($field))
                }]);
            }
        }
    }

    public function messages(): array
    {
        return ['code.unique' => 'Kode sudah digunakan, termasuk pada data terhapus.'];
    }

    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:32', Rule::unique('payment_methods', 'code')->ignore(strtolower((string) $this->route('id')))],
            'name' => ['required', 'string', 'max:150'], 'type' => ['required', Rule::enum(PaymentMethodType::class)],
            'is_active' => [$this->isMethod('put') ? 'required' : 'sometimes', 'boolean'], 'payment_account_id' => ['required', 'ulid']];
    }
}
