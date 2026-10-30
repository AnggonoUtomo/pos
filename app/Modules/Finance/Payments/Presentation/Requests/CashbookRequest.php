<?php

namespace App\Modules\Finance\Payments\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CashbookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $date = ['date_format:Y-m-d', 'after_or_equal:1900-01-01', 'before_or_equal:'.now('Asia/Jakarta')->toDateString()];

        return match ($this->route()->getActionMethod()) {
            'open' => ['account_id' => ['required', 'ulid'], 'amount' => ['required', 'string', 'max:32'], 'starts_on' => ['required', ...$date]],
            'store' => ['account_id' => ['required', 'ulid'], 'counter_account_id' => ['nullable', 'ulid'], 'kind' => ['required', Rule::in(['incoming', 'outgoing', 'transfer'])],
                'amount' => ['required', 'string', 'max:32'], 'occurred_on' => ['required', ...$date], 'description' => ['required', 'string', 'max:200'], 'idempotency_key' => ['required', 'uuid']],
            'reverse' => ['reason' => ['required', 'string', 'max:200']],
            default => ['account_id' => ['nullable', 'ulid'], 'date_from' => ['nullable', ...$date], 'date_to' => ['nullable', ...$date], 'page' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
                'per_page' => ['sometimes', 'integer', 'in:10,25,50,100']],
        };
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (array_diff(array_keys($this->isMethod('GET') ? $this->query() : $this->all()), array_keys($this->rules())) !== []) {
                $validator->errors()->add('form', 'Field tambahan tidak diizinkan.');
            }
            if ($this->input('date_from') && $this->input('date_to') && $this->input('date_to') < $this->input('date_from')) {
                $validator->errors()->add('date_to', 'Tanggal akhir harus sesudah atau sama dengan tanggal awal.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        foreach (['account_id', 'counter_account_id'] as $key) {
            if (is_string($this->input($key))) {
                $this->merge([$key => strtolower($this->input($key))]);
            }
        }
    }
}
