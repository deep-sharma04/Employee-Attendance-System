<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class PunchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role?->value === 'employee' || $this->user()?->role === 'employee';
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:punch_in,punch_out'],
        ];
    }
}
