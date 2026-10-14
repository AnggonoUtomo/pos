<?php

namespace App\Modules\Finance\Payments\Presentation\Requests;

use App\Modules\Finance\Payments\Domain\Enums\PaymentAccountType;
use App\Modules\Finance\Payments\Domain\Enums\PaymentMethodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPaymentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['page' => ['sometimes', 'integer', 'min:1', 'max:2147483647'], 'search' => ['nullable', 'string', 'max:150'],
            'status' => ['sometimes', Rule::in(['all', 'active', 'inactive'])], 'trashed' => ['sometimes', 'boolean'],
            'type' => ['nullable', Rule::enum($this->routeIs('finance.payment-accounts.*') ? PaymentAccountType::class : PaymentMethodType::class)],
            'payment_account_id' => ['nullable', 'ulid']];
    }

    public function filters(): array
    {
        $data = $this->validated();

        return ['search' => trim($data['search'] ?? ''), 'status' => $data['status'] ?? 'all', 'trashed' => $this->boolean('trashed'),
            'type' => $data['type'] ?? null, 'payment_account_id' => empty($data['payment_account_id']) ? null : strtolower($data['payment_account_id'])];
    }
}
