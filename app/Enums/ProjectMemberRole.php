<?php

namespace App\Enums;

enum ProjectMemberRole: string
{
    case MANAGER = 'manager';
    case TEAM_LEAD = 'team_lead';
    case MEMBER = 'member';
    case VIEWER = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::MANAGER => 'Manager',
            self::TEAM_LEAD => 'Team Lead',
            self::MEMBER => 'Member',
            self::VIEWER => 'Viewer',
        };
    }
}
