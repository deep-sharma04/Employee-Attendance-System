<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:150'],
            'company_address' => ['required', 'string', 'max:255'],
            'company_email' => ['nullable', 'email', 'max:150'],
            'company_phone' => ['nullable', 'string', 'max:30'],
            'salary_divisor' => ['required', 'integer', 'min:20', 'max:31'],
            'late_grace_period_minutes' => ['required', 'integer', 'min:0', 'max:60'],
            'half_day_threshold_minutes' => ['required', 'integer', 'min:15', 'max:180'],
            'late_to_absent_ratio' => ['nullable', 'integer', 'min:1', 'max:10'],
            'half_day_to_absent_ratio' => ['nullable', 'integer', 'min:1', 'max:10'],
            'enable_sandwich_rule' => ['nullable', 'boolean'],
        ];
    }
}
