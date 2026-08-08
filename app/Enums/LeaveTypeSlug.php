<?php

namespace App\Enums;

enum LeaveTypeSlug: string
{
    case CASUAL = 'casual';
    case MEDICAL = 'medical';

    public function label(): string
    {
        return match ($this) {
            self::CASUAL => 'Casual Leave',
            self::MEDICAL => 'Medical Leave',
        };
    }
}
