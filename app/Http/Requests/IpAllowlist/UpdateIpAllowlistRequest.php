<?php

namespace App\Http\Requests\IpAllowlist;

use App\Enums\UserRole;
use App\Models\OfficeIpAllowlist;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIpAllowlistRequest extends FormRequest
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
        $ipId = $this->route('id') ?? $this->route('ip_allowlist');

        return [
            'ip_address' => ['required', 'ip', Rule::unique('office_ip_allowlists', 'ip_address')->ignore($ipId)],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
