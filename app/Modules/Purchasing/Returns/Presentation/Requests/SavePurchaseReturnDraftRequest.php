<?php

namespace App\Modules\Purchasing\Returns\Presentation\Requests;

use App\Modules\Purchasing\Returns\Application\Actions\ManagePurchaseReturnDraft;
use Illuminate\Foundation\Http\FormRequest;

final class SavePurchaseReturnDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(ManagePurchaseReturnDraft::normalize($this->all()));
    }

    public function rules(): array
    {
        return ['purchase_order_id' => ['required', 'ulid'], 'notes' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'lines.*' => ['required', 'array:purchase_order_line_id,quantity'],
            'lines.*.purchase_order_line_id' => ['required', 'ulid', 'distinct:ignore_case'],
            'lines.*.quantity' => ['required', 'string', 'max:21', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/D'],
            'version' => [$this->route('id') === null ? 'sometimes' : 'required', 'integer', 'min:1', 'max:2147483646']];
    }
}
