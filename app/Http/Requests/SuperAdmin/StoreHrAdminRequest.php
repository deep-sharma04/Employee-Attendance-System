<?php

namespace App\Http\Requests\SuperAdmin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreHrAdminRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9._-]+$/', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8'],
            'auto_generate_password' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
