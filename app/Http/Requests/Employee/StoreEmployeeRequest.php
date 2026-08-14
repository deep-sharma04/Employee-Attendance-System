<?php

namespace App\Http\Requests\Employee;

use App\Enums\EmployeeStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
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
            // User Credentials
            'username' => ['nullable', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'password' => ['nullable', 'string', 'min:8'],
            'auto_generate_password' => ['nullable', 'boolean'],

            // Personal Information
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email', 'unique:employees,email'],
            'phone' => ['required', 'string', 'max:20'],
            'gender' => ['required', 'string', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['required', 'date', 'before:' . now()->subYears(18)->format('Y-m-d')],

            // Employment Details
            'employee_code' => ['nullable', 'string', 'max:30', 'unique:employees,employee_code'],
            'department' => ['required', 'string', 'max:100'],
            'designation' => ['required', 'string', 'max:100'],
            'joining_date' => ['required', 'date'],
            'shift_id' => ['required', 'exists:shifts,id'],
            'status' => ['nullable', Rule::enum(EmployeeStatus::class)],

            // Compensation & Salary
            'monthly_salary' => ['required', 'numeric', 'min:0'],

            // Bank & Statutory Details
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'max:30'],
            'ifsc_code' => ['required', 'string', 'max:20'],
            'pan_number' => ['required', 'string', 'max:20'],

            // Initial Leave Allocations
            'leave_allocations' => ['nullable', 'array'],
            'leave_allocations.*' => ['nullable', 'numeric', 'min:0', 'max:365'],
        ];
    }
}
