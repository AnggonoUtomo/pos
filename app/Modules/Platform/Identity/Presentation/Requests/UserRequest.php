<?php

namespace App\Modules\Platform\Identity\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => is_string($this->name) ? trim($this->name) : $this->name,
            'email' => is_string($this->email) ? strtolower(trim($this->email)) : $this->email]);
    }

    public function rules(): array
    {
        $rules = ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'string', 'email', 'max:255']];
        if ($this->routeIs('platform.identity.users.store')) {
            $rules += ['password' => ['required', 'string', 'min:12', 'max:72', 'confirmed'],
                'password_confirmation' => ['required', 'string'],
                'roles' => ['present', 'array', 'list', 'max:1000'], 'roles.*' => ['required', 'string', 'max:150', 'distinct']];
        }

        return $rules;
    }
}
