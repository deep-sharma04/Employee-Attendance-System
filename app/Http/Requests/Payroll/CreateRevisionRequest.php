<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class CreateRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'revision_reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'revision_reason.required' => 'Please provide an authorized justification for revising this finalized payroll.',
            'revision_reason.min' => 'The revision reason must be at least 5 characters.',
        ];
    }
}
