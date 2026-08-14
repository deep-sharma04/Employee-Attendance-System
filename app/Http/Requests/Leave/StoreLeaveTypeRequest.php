<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'slug' => ['required', 'string', 'max:30', 'alpha_dash', 'unique:leave_types,slug'],
            'annual_quota' => ['required', 'numeric', 'min:0', 'max:365'],
            'requires_document' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
