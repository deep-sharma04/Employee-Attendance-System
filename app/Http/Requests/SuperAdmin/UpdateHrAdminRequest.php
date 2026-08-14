<?php

namespace App\Http\Requests\SuperAdmin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHrAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return $role === 'super_admin';
    }

    public function rules(): array
    {
        $hrAdminId = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($hrAdminId)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
