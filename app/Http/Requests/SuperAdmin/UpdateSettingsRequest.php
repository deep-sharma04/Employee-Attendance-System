<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role?->value === 'super_admin' || $this->user()?->role === 'super_admin';
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:150'],
            'company_address' => ['required', 'string', 'max:255'],
            'salary_divisor' => ['required', 'integer', 'min:20', 'max:31'],
            'late_grace_period_minutes' => ['required', 'integer', 'min:0', 'max:60'],
            'half_day_threshold_minutes' => ['required', 'integer', 'min:15', 'max:180'],
        ];
    }
}
