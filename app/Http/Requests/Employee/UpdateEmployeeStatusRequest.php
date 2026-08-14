<?php

namespace App\Http\Requests\Employee;

use App\Enums\EmployeeStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeStatusRequest extends FormRequest
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
            'status' => ['required', Rule::enum(EmployeeStatus::class)],
            'status_change_reason' => ['required', 'string', 'max:500'],
        ];
    }
}
