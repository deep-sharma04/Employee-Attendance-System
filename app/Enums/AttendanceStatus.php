<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case LATE = 'late';
    case HALF_DAY = 'half_day';
    case ABSENT = 'absent';
    case LEAVE = 'leave';
    case HOLIDAY = 'holiday';
    case WEEKEND = 'weekend';

    public function label(): string
    {
        return match ($this) {
            self::PRESENT => 'Present',
            self::LATE => 'Late',
            self::HALF_DAY => 'Half Day',
            self::ABSENT => 'Absent',
            self::LEAVE => 'Approved Leave',
            self::HOLIDAY => 'Company Holiday',
            self::WEEKEND => 'Weekend Off',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PRESENT => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::LATE => 'bg-amber-50 text-amber-700 border-amber-200',
            self::HALF_DAY => 'bg-orange-50 text-orange-700 border-orange-200',
            self::ABSENT => 'bg-rose-50 text-rose-700 border-rose-200',
            self::LEAVE => 'bg-blue-50 text-blue-700 border-blue-200',
            self::HOLIDAY => 'bg-purple-50 text-purple-700 border-purple-200',
            self::WEEKEND => 'bg-slate-50 text-slate-600 border-slate-200',
        };
    }
}
