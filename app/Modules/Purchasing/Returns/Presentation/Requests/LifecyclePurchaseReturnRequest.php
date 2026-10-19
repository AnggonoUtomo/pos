<?php

namespace App\Modules\Purchasing\Returns\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LifecyclePurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['version' => ['required', 'integer', 'min:1', 'max:2147483646']];
    }
}
