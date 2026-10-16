<?php

namespace App\Modules\Purchasing\PurchaseOrders\Presentation\Requests;

use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PreparePurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;

class SavePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(PreparePurchaseOrder::normalize($this->all()));
    }

    public function rules(): array
    {
        return PreparePurchaseOrder::rules() + ['version' => [$this->route('id') === null ? 'sometimes' : 'required', 'integer', 'min:1', 'max:2147483646']];
    }
}
