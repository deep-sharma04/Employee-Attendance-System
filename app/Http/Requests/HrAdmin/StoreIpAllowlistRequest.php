<?php

namespace App\Http\Requests\HrAdmin;

use Illuminate\Foundation\Http\FormRequest;

class StoreIpAllowlistRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->user()?->role?->value ?? (string) $this->user()?->role;
        return in_array($role, ['super_admin', 'hr_admin']);
    }

    public function rules(): array
    {
        return [
            'ip_address' => ['required', 'ip', 'unique:office_ip_allowlists,ip_address'],
            'description' => ['nullable', 'string', 'max:150'],
        ];
    }
}
