<?php

namespace App\Enums;

enum ClientStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case LEAD = 'lead';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::LEAD => 'Lead',
            self::ARCHIVED => 'Archived',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::INACTIVE => 'bg-slate-50 text-slate-700 border-slate-200',
            self::LEAD => 'bg-blue-50 text-blue-700 border-blue-200',
            self::ARCHIVED => 'bg-amber-50 text-amber-700 border-amber-200',
        };
    }
}
