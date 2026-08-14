<?php

namespace App\Http\Requests\IpAllowlist;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreIpAllowlistRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'hr_admin']);
    }

    public function rules(): array
    {
        return [
            'ip_address' => ['required', 'ip', 'unique:office_ip_allowlists,ip_address'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
