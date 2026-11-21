<?php

namespace App\Modules\Platform\Hardware\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HardwareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['q' => ['sometimes', 'nullable', 'string', 'max:150'],
            'page' => ['sometimes', 'integer', 'between:1,100000'],
            'per_page' => ['sometimes', 'integer', 'in:10,25,50,100'],
            'status' => ['sometimes', 'nullable', 'in:queued,sending,submitted,uncertain,failed_before_send']];
    }
}
