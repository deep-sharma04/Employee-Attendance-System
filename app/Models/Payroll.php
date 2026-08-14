<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PayrollStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'payroll_month',
        'payroll_year',
        'monthly_salary',
        'daily_salary',
        'salary_divisor',
        'total_days_in_month',
        'present_days',
        'late_days',
        'half_days',
        'absent_days',
        'leave_days',
        'holiday_days',
        'weekend_days',
        'bridged_holiday_days',
        'converted_late_absent_days',
        'converted_half_day_absent_days',
        'total_lop_days',
        'lop_deduction_amount',
        'total_earnings',
        'total_deductions',
        'net_salary',
        'status',
        'payment_status',
        'revision_number',
        'revision_reason',
        'generated_by',
        'approved_by',
        'finalized_by',
        'approved_at',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'payroll_month' => 'integer',
            'payroll_year' => 'integer',
            'monthly_salary' => 'decimal:2',
            'daily_salary' => 'decimal:2',
            'present_days' => 'decimal:1',
            'absent_days' => 'decimal:1',
            'leave_days' => 'decimal:1',
            'total_lop_days' => 'decimal:1',
            'lop_deduction_amount' => 'decimal:2',
            'total_earnings' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'status' => PayrollStatus::class,
            'payment_status' => PaymentStatus::class,
            'approved_at' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function payslip(): HasOne
    {
        return $this->hasOne(Payslip::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }
}
