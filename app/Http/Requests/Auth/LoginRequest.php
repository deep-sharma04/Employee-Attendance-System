<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', new Enum(UserRole::class)],
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Please select your role before signing in.',
            'role.Illuminate\Validation\Rules\Enum' => 'The selected role is invalid.',
        ];
    }
}
