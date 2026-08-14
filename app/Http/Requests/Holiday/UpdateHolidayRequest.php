<?php

namespace App\Http\Requests\Holiday;

use App\Enums\UserRole;
use App\Models\Holiday;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'hr_admin']);
    }

    public function rules(): array
    {
        $holidayId = $this->route('id') ?? $this->route('holiday');

        return [
            'holiday_date' => [
                'required',
                'date',
                function ($attribute, $value, $fail) use ($holidayId) {
                    $year = date('Y', strtotime($value));
                    $exists = Holiday::whereYear('holiday_date', $year)
                        ->whereDate('holiday_date', $value)
                        ->where('id', '!=', $holidayId)
                        ->exists();

                    if ($exists) {
                        $fail("A declared holiday already exists on {$value} for year {$year}.");
                    }
                },
            ],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_recurring_yearly' => ['nullable', 'boolean'],
        ];
    }
}
