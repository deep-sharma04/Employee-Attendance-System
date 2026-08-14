<?php

namespace App\Http\Requests\Shift;

use App\Enums\UserRole;
use App\Models\Shift;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShiftRequest extends FormRequest
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
        $shiftId = $this->route('id') ?? $this->route('shift');

        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('shifts', 'code')->ignore($shiftId)],
            'start_time' => ['required'],
            'end_time' => ['required', 'different:start_time'],
            'working_days' => ['required', 'array', 'min:1'],
            'working_days.*' => ['string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'grace_period_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'half_day_threshold_minutes' => ['required', 'integer', 'gte:grace_period_minutes', 'max:360'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
