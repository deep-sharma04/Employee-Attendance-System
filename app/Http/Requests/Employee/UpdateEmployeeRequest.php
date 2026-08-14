<?php

namespace App\Http\Requests\Employee;

use App\Enums\EmployeeStatus;
use App\Enums\UserRole;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
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
        $employeeId = $this->route('id') ?? $this->route('employee');
        $employee = Employee::findOrFail($employeeId);
        $userId = $employee->user_id;

        return [
            // Personal Information
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($userId),
                Rule::unique('employees', 'email')->ignore($employee->id),
            ],
            'phone' => ['required', 'string', 'max:20'],
            'gender' => ['required', 'string', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['required', 'date', 'before:' . now()->subYears(18)->format('Y-m-d')],

            // Employment Details
            'employee_code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('employees', 'employee_code')->ignore($employee->id),
            ],
            'department' => ['required', 'string', 'max:100'],
            'designation' => ['required', 'string', 'max:100'],
            'joining_date' => ['required', 'date'],
            'shift_id' => ['required', 'exists:shifts,id'],
            'status' => ['required', Rule::enum(EmployeeStatus::class)],

            // Compensation & Salary
            'monthly_salary' => ['required', 'numeric', 'min:0'],

            // Bank & Statutory Details
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'max:30'],
            'ifsc_code' => ['required', 'string', 'max:20'],
            'pan_number' => ['required', 'string', 'max:20'],
        ];
    }
}
