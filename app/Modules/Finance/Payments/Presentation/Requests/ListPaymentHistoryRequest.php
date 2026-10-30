<?php

namespace App\Modules\Finance\Payments\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ListPaymentHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['page' => ['sometimes', 'integer', 'min:1', 'max:2147483647'], 'search' => ['nullable', 'string', 'max:150'],
            'direction' => ['sometimes', Rule::in(['all', 'incoming', 'outgoing'])], 'account_id' => ['nullable', 'ulid'], 'method_id' => ['nullable', 'ulid'],
            'date_from' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1900-01-01', 'before_or_equal:9998-12-31'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1900-01-01', 'before_or_equal:9998-12-31']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (array_diff(array_keys($this->query()), ['page', 'per_page', 'search', 'direction', 'account_id', 'method_id', 'date_from', 'date_to']) !== []) {
                $validator->errors()->add('filters', 'Filter tidak diizinkan.');
            }
            if (! $validator->errors()->has('date_from') && ! $validator->errors()->has('date_to') && $this->input('date_from') && $this->input('date_to') && $this->input('date_to') < $this->input('date_from')) {
                $validator->errors()->add('date_to', 'Tanggal akhir harus sama atau sesudah tanggal awal.');
            }
        }];
    }

    public function filters(): array
    {
        $input = $this->validated();
        $result = ['search' => trim($input['search'] ?? ''), 'direction' => $input['direction'] ?? 'all', 'date_from' => $input['date_from'] ?? null, 'date_to' => $input['date_to'] ?? null];
        foreach (['account_id', 'method_id'] as $key) {
            $result[$key] = empty($input[$key]) ? null : strtolower($input[$key]);
        }

        return $result;
    }
}
