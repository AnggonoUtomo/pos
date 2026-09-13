<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DeactivateEmployeeApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'left_on' => ['required', 'date'],
        ];
    }

    public function leftOn(): string
    {
        return (string) $this->validated('left_on');
    }
}
