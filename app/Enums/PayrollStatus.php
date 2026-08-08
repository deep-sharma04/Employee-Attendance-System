<?php

namespace App\Enums;

enum PayrollStatus: string
{
    case DRAFT = 'draft';
    case REVIEWED = 'reviewed';
    case APPROVED = 'approved';
    case FINALIZED = 'finalized';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft Generated',
            self::REVIEWED => 'Under Review',
            self::APPROVED => 'Super Admin Approved',
            self::FINALIZED => 'Finalized & Locked',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-slate-50 text-slate-700 border-slate-200',
            self::REVIEWED => 'bg-amber-50 text-amber-700 border-amber-200',
            self::APPROVED => 'bg-blue-50 text-blue-700 border-blue-200',
            self::FINALIZED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        };
    }
}
