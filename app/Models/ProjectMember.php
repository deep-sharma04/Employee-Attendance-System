<?php

namespace App\Models;

use App\Enums\ProjectMemberRole;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMember extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'project_id',
        'user_id',
        'employee_id',
        'project_role',
        'is_active',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'project_role' => ProjectMemberRole::class,
            'is_active' => 'boolean',
            'joined_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
