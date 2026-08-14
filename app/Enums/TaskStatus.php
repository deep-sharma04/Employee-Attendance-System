<?php

namespace App\Enums;

enum TaskStatus: string
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case IN_REVIEW = 'in_review';
    case BLOCKED = 'blocked';
    case DONE = 'done';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::TODO => 'To Do',
            self::IN_PROGRESS => 'In Progress',
            self::IN_REVIEW => 'In Review',
            self::BLOCKED => 'Blocked',
            self::DONE => 'Done',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::TODO => 'bg-slate-100 text-slate-700 border-slate-200',
            self::IN_PROGRESS => 'bg-blue-50 text-blue-700 border-blue-200',
            self::IN_REVIEW => 'bg-purple-50 text-purple-700 border-purple-200',
            self::BLOCKED => 'bg-rose-50 text-rose-700 border-rose-200',
            self::DONE => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::CANCELLED => 'bg-slate-200 text-slate-600 border-slate-300',
        };
    }

    public function kanbanHeaderClass(): string
    {
        return match ($this) {
            self::TODO => 'bg-slate-100 text-slate-800 border-slate-300',
            self::IN_PROGRESS => 'bg-blue-50 text-blue-800 border-blue-300',
            self::IN_REVIEW => 'bg-purple-50 text-purple-800 border-purple-300',
            self::BLOCKED => 'bg-rose-50 text-rose-800 border-rose-300',
            self::DONE => 'bg-emerald-50 text-emerald-800 border-emerald-300',
            self::CANCELLED => 'bg-slate-200 text-slate-700 border-slate-400',
        };
    }
}
