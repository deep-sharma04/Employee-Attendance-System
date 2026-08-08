<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case CLEARED = 'cleared';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Payment Pending',
            self::PROCESSING => 'Bank Processing',
            self::CLEARED => 'Payment Cleared',
            self::FAILED => 'Payment Failed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'bg-amber-50 text-amber-700 border-amber-200',
            self::PROCESSING => 'bg-blue-50 text-blue-700 border-blue-200',
            self::CLEARED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::FAILED => 'bg-rose-50 text-rose-700 border-rose-200',
        };
    }
}
