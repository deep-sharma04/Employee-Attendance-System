<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'attendance_date' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['required', 'string', 'in:present,late,half_day,absent,leave,holiday,week_off'],
            'punch_in_at' => ['nullable', 'date_format:H:i'],
            'punch_out_at' => ['nullable', 'date_format:H:i'],
            'total_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'correction_reason' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }
}
