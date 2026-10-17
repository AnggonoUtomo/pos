<?php

namespace App\Modules\Purchasing\PurchaseOrders\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class PostPurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if (array_diff(array_keys($this->all()), ['version', '_token']) !== []) {
            throw ValidationException::withMessages(['purchase_order' => 'Penerimaan hanya menerima revisi draft, bukan perubahan isi.']);
        }

        return ['version' => ['required', 'integer', 'min:1', 'max:2147483646']];
    }
}
