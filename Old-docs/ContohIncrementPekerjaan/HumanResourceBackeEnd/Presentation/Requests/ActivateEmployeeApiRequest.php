<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ActivateEmployeeApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [];
    }
}
