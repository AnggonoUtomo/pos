<?php

namespace App\Modules\Platform\AuditLog\Presentation\Requests;

use App\Modules\Platform\OperationalSettings\Application\Contracts\TablePreferences;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ListAuditLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['page' => ['sometimes', 'integer', 'min:1', 'max:2147483647'],
            'per_page' => ['sometimes', 'integer', Rule::in(TablePreferences::SIZES)],
            'search' => ['nullable', 'string', 'max:150'],
            'module' => ['nullable', 'string', 'regex:/^[a-z][a-z0-9_.-]{0,99}$/D'],
            'event' => ['nullable', 'string', 'regex:/^[a-z][a-z0-9_.-]{0,99}$/D'],
            'actor_id' => ['nullable', 'string', 'regex:/^(system|[1-9][0-9]{0,18})$/D'],
            'date_from' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1900-01-01', 'before_or_equal:9998-12-31'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1900-01-01', 'before_or_equal:9998-12-31']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (array_diff(array_keys($this->query()), array_keys($this->rules())) !== []) {
                $validator->errors()->add('filters', 'Filter tidak diizinkan.');
            }
            if (! $validator->errors()->has('date_from') && ! $validator->errors()->has('date_to')
                && $this->input('date_from') && $this->input('date_to') && $this->input('date_to') < $this->input('date_from')) {
                $validator->errors()->add('date_to', 'Tanggal akhir harus sama atau sesudah tanggal awal.');
            }
        }];
    }

    public function filters(): array
    {
        $input = $this->validated();

        return ['search' => trim($input['search'] ?? ''), 'module' => $input['module'] ?? null, 'event' => $input['event'] ?? null,
            'actor_id' => $input['actor_id'] ?? null, 'date_from' => $input['date_from'] ?? null, 'date_to' => $input['date_to'] ?? null];
    }
}
