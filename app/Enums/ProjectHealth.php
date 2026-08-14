<?php

namespace App\Enums;

enum ProjectHealth: string
{
    case GOOD = 'good';
    case AT_RISK = 'at_risk';
    case CRITICAL = 'critical';
    case NOT_STARTED = 'not_started';

    public function label(): string
    {
        return match ($this) {
            self::GOOD => 'Good',
            self::AT_RISK => 'At Risk',
            self::CRITICAL => 'Critical',
            self::NOT_STARTED => 'Not Started',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::GOOD => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::AT_RISK => 'bg-amber-50 text-amber-700 border-amber-200',
            self::CRITICAL => 'bg-rose-50 text-rose-700 border-rose-200',
            self::NOT_STARTED => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }
}
