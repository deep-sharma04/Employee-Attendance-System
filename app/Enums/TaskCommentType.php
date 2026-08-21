<?php

namespace App\Enums;

enum TaskCommentType: string
{
    case GENERAL = 'general';
    case INFORMATION_REQUIRED = 'information_required';
    case INFO = 'info';
    case REMARK = 'remark';

    public function label(): string
    {
        return match($this) {
            self::GENERAL => 'General',
            self::INFORMATION_REQUIRED => 'Information Required',
            self::INFO => 'Info',
            self::REMARK => 'Remark',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::GENERAL => 'bg-slate-100 text-slate-800 border-slate-200',
            self::INFORMATION_REQUIRED => 'bg-amber-100 text-amber-800 border-amber-200',
            self::INFO => 'bg-blue-100 text-blue-800 border-blue-200',
            self::REMARK => 'bg-purple-100 text-purple-800 border-purple-200',
        };
    }
}
