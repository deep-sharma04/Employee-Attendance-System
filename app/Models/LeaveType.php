<?php

namespace App\Models;

use App\Enums\LeaveTypeSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'annual_quota',
        'requires_document',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'annual_quota' => 'decimal:1',
            'requires_document' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function balances(): HasMany
    {
        return $this->hasMany(EmployeeLeaveBalance::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
