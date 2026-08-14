<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'title' => ['required', 'string', 'max:150'],
            'document_file' => [
                'required',
                'file',
                'mimes:png,jpg,jpeg,pdf',
                'mimetypes:image/png,image/jpeg,application/pdf',
                'max:500', // max 500 KB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Please select an employee.',
            'employee_id.exists' => 'The selected employee record does not exist.',
            'document_type_id.required' => 'Please select a document type.',
            'document_type_id.exists' => 'The selected document type is invalid.',
            'title.required' => 'Please provide a descriptive document title.',
            'document_file.required' => 'Please select a document file to upload.',
            'document_file.mimes' => 'The document must be a file of type: png, jpeg, pdf.',
            'document_file.max' => 'The document file size must not exceed 500 KB.',
        ];
    }
}
