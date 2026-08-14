<?php

namespace App\Http\Requests\Leave;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\Leave\LeaveBalanceService;
use App\Services\Leave\LeaveWorkingDayService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_half_day' => ['nullable', 'boolean'],
            'half_day_type' => ['nullable', 'in:first_half,second_half'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = Auth::user();
            $employee = Employee::where('user_id', $user->id)->first();

            if (!$employee) {
                $validator->errors()->add('leave_type_id', 'No active employee profile linked to this account.');
                return;
            }

            $leaveTypeId = (int) $this->input('leave_type_id');
            $leaveType = LeaveType::find($leaveTypeId);
            if (!$leaveType || !$leaveType->is_active) {
                $validator->errors()->add('leave_type_id', 'The selected leave type is not active.');
                return;
            }

            $startDate = $this->input('start_date');
            $endDate = $this->input('end_date');
            $isHalfDay = (bool) $this->input('is_half_day');

            // 1. Calculate actual working days excluding Sundays and company holidays
            $workingDayService = app(LeaveWorkingDayService::class);
            $totalDays = $workingDayService->calculateWorkingDays($startDate, $endDate, $isHalfDay);

            if ($totalDays <= 0) {
                $validator->errors()->add('start_date', 'The selected date range contains no working days (all dates are weekends or company holidays).');
                return;
            }

            // 2. Validate sufficient remaining leave balance
            $balanceService = app(LeaveBalanceService::class);
            $currentBalance = $balanceService->getBalance($employee->id, $leaveTypeId, (int) Carbon::parse($startDate)->year);

            if ($currentBalance < $totalDays) {
                $validator->errors()->add('leave_type_id', "Insufficient leave balance for {$leaveType->name}. Requested: {$totalDays} days, Available: {$currentBalance} days.");
                return;
            }

            // 3. Prevent overlapping pending or approved leave requests
            $overlap = LeaveRequest::where('employee_id', $employee->id)
                ->whereIn('status', ['pending', 'approved'])
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                })
                ->exists();

            if ($overlap) {
                $validator->errors()->add('start_date', 'You already have an active (pending or approved) leave application overlapping these dates.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'leave_type_id.required' => 'Please select a leave type.',
            'leave_type_id.exists' => 'The selected leave type is invalid.',
            'start_date.required' => 'Please provide a leave start date.',
            'end_date.required' => 'Please provide a leave end date.',
            'end_date.after_or_equal' => 'The end date must be on or after the start date.',
            'reason.required' => 'A reason is mandatory for leave applications.',
            'reason.min' => 'The reason must be at least 5 characters long.',
        ];
    }
}
