<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AllocateLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'year' => ['required', 'integer', 'min:2020', 'max:2099'],
            'allocated_days' => ['required', 'numeric', 'min:0', 'max:365'],
        ];
    }
}
