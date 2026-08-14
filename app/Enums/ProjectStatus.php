<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case PLANNING = 'planning';
    case ACTIVE = 'active';
    case ON_HOLD = 'on_hold';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PLANNING => 'Planning',
            self::ACTIVE => 'Active',
            self::ON_HOLD => 'On Hold',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PLANNING => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            self::ACTIVE => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::ON_HOLD => 'bg-amber-50 text-amber-700 border-amber-200',
            self::COMPLETED => 'bg-blue-50 text-blue-700 border-blue-200',
            self::CANCELLED => 'bg-rose-50 text-rose-700 border-rose-200',
        };
    }
}
