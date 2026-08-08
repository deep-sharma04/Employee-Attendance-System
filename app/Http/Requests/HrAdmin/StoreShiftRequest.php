<?php

namespace App\Http\Requests\HrAdmin;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->user()?->role?->value ?? (string) $this->user()?->role;
        return in_array($role, ['super_admin', 'hr_admin']);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'working_days' => ['required', 'array', 'min:1'],
            'grace_period_minutes' => ['required', 'integer', 'min:0', 'max:60'],
            'half_day_threshold_minutes' => ['required', 'integer', 'min:15', 'max:180'],
        ];
    }
}
