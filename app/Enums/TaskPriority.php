<?php

namespace App\Enums;

enum TaskPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::URGENT => 'Urgent',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::LOW => 'bg-slate-100 text-slate-700 border-slate-200',
            self::MEDIUM => 'bg-blue-50 text-blue-700 border-blue-200',
            self::HIGH => 'bg-amber-50 text-amber-800 border-amber-200',
            self::URGENT => 'bg-rose-50 text-rose-700 border-rose-200',
        };
    }
}
