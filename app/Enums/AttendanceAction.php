<?php

namespace App\Enums;

enum AttendanceAction: string
{
    case PUNCH_IN = 'punch_in';
    case PUNCH_OUT = 'punch_out';

    public function label(): string
    {
        return match ($this) {
            self::PUNCH_IN => 'Punch In',
            self::PUNCH_OUT => 'Punch Out',
        };
    }
}
