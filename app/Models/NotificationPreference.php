<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category',
        'channel',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Supported notification categories.
     */
    public static function supportedCategories(): array
    {
        return [
            'task_assignment' => [
                'name' => 'Task Assignments & Updates',
                'description' => 'When a task is assigned, updated, or marked for review.',
                'mandatory' => false,
            ],
            'deadlines' => [
                'name' => 'Approaching Deadlines & Overdue Tasks',
                'description' => 'Reminders for tasks approaching due dates or overdue work.',
                'mandatory' => false,
            ],
            'timesheets' => [
                'name' => 'Timesheet Submissions & Approvals',
                'description' => 'When timesheets are submitted, approved, or returned for revision.',
                'mandatory' => false,
            ],
            'project_milestones' => [
                'name' => 'Project Milestones & Health Alerts',
                'description' => 'When milestones are achieved or project health status shifts.',
                'mandatory' => false,
            ],
            'daily_summary' => [
                'name' => 'Daily Work Morning Summary',
                'description' => 'Daily overview of assigned work, upcoming deadlines, and pending approvals.',
                'mandatory' => false,
            ],
            'security' => [
                'name' => 'Security & Account Alerts',
                'description' => 'Password changes, security events, and audit actions (Mandatory).',
                'mandatory' => true,
            ],
        ];
    }

    /**
     * Supported delivery channels.
     */
    public static function supportedChannels(): array
    {
        return [
            'in_app' => 'In-App Notification',
            'email' => 'Email Notification',
            'web_push' => 'Browser Web Push',
        ];
    }

    /**
     * Check if a specific category and channel is enabled for a given user.
     */
    public static function isChannelEnabled(User|int $user, string $category, string $channel): bool
    {
        // Security category is always mandatory and cannot be disabled
        if ($category === 'security') {
            return true;
        }

        $userId = $user instanceof User ? $user->id : $user;

        $preference = static::where('user_id', $userId)
            ->where('category', $category)
            ->where('channel', $channel)
            ->first();

        // Default to enabled (true) if no explicit preference record exists
        return $preference ? (bool) $preference->is_enabled : true;
    }
}
