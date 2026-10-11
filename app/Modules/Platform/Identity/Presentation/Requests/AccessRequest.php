<?php

namespace App\Modules\Platform\Identity\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    public function rules(): array
    {
        if ($this->isMethod('get')) {
            return ['tab' => ['sometimes', 'in:roles,permissions,users'],
                'search' => ['nullable', 'string', 'max:150'], 'page' => ['sometimes', 'integer', 'min:1', 'max:2147483647']];
        }
        if ($this->routeIs('platform.identity.roles.permissions')) {
            return ['permissions' => ['present', 'array', 'list', 'max:1000'], 'permissions.*' => ['required', 'string', 'max:150', 'distinct']];
        }
        if ($this->routeIs('platform.identity.users.roles')) {
            return ['roles' => ['present', 'array', 'list', 'max:1000'], 'roles.*' => ['required', 'string', 'max:64', 'distinct']];
        }

        return ['name' => ['required', 'string', 'max:64']];
    }
}
