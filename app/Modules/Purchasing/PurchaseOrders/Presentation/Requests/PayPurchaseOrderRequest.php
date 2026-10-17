<?php

namespace App\Modules\Purchasing\PurchaseOrders\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class PayPurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if (array_diff(array_keys($this->all()), ['version', 'payments', '_token']) !== []) {
            throw ValidationException::withMessages(['purchase_order' => 'Pembayaran hanya menerima revisi dan rincian metode/jumlah.']);
        }

        return ['version' => ['required', 'integer', 'min:1', 'max:2147483646'], 'payments' => ['required', 'array', 'list', 'min:1', 'max:10'],
            'payments.*' => ['required', 'array:method_id,amount'], 'payments.*.method_id' => ['required', 'ulid'], 'payments.*.amount' => ['required', 'string', 'regex:/^\d{1,24}(?:\.\d{1,6})?$/D']];
    }
}
