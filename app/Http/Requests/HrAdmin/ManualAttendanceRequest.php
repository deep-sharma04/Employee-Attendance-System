<?php

namespace App\Http\Requests\HrAdmin;

use Illuminate\Foundation\Http\FormRequest;

class ManualAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->user()?->role?->value ?? (string) $this->user()?->role;
        return in_array($role, ['super_admin', 'hr_admin']);
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer'],
            'attendance_date' => ['required', 'date'],
            'punch_in' => ['nullable', 'date_format:H:i'],
            'punch_out' => ['nullable', 'date_format:H:i'],
            'status' => ['required', 'string', 'in:present,late,half_day,absent,leave,holiday'],
            'correction_reason' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }
}
