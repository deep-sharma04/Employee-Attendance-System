<?php

namespace App\Enums;

enum EmployeeStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case TERMINATED = 'terminated';
    case RESIGNED = 'resigned';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::TERMINATED => 'Terminated',
            self::RESIGNED => 'Resigned',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::INACTIVE => 'bg-slate-100 text-slate-700 border-slate-200',
            self::TERMINATED => 'bg-rose-100 text-rose-800 border-rose-200',
            self::RESIGNED => 'bg-amber-100 text-amber-800 border-amber-200',
        };
    }
}
