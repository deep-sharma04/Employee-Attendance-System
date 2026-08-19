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
            // SMTP & Email Settings
            'mail_mailer' => ['nullable', 'string', 'in:smtp,sendmail,log,array'],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_encryption' => ['nullable', 'string', 'in:tls,ssl,null,none'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
