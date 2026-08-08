<?php

namespace App\Http\Requests\HrAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
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
            'document_type_id' => ['required', 'integer'],
            'document_file' => [
                'required',
                'file',
                'mimes:jpeg,png,pdf',
                'max:500', // 500 KB limit enforced strictly per PRD
            ],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
