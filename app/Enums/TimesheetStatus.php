<?php

namespace App\Enums;

enum TimesheetStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case RETURNED = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted (Pending Review)',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::RETURNED => 'Returned for Revision',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-slate-100 text-slate-700 border-slate-200',
            self::SUBMITTED => 'bg-amber-50 text-amber-700 border-amber-200',
            self::APPROVED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::REJECTED => 'bg-rose-50 text-rose-700 border-rose-200',
            self::RETURNED => 'bg-purple-50 text-purple-700 border-purple-200',
        };
    }
}
