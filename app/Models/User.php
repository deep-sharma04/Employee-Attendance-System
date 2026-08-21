<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'is_active',
        'email_verified_at',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Linked employee profile if user is an employee.
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Roles assigned to user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPER_ADMIN;
    }

    public function isHrAdmin(): bool
    {
        return $this->role === UserRole::HR_ADMIN;
    }

    public function isEmployee(): bool
    {
        return $this->role === UserRole::EMPLOYEE;
    }

    public function isManager(): bool
    {
        return $this->role === UserRole::MANAGER;
    }

    public function isTeamLead(): bool
    {
        return $this->role === UserRole::TEAM_LEAD;
    }

    public function isClient(): bool
    {
        return $this->role === UserRole::CLIENT;
    }

    /**
     * Client user mapping if user is a client portal account.
     */
    public function clientUser(): HasOne
    {
        return $this->hasOne(ClientUser::class);
    }

    /**
     * Teams managed by this user as Manager.
     */
    public function managedTeams(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Team::class, 'manager_id');
    }

    /**
     * Teams led by this user as Team Lead.
     */
    public function ledTeams(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Team::class, 'team_lead_id');
    }

    /**
     * Team memberships.
     */
    public function teamMemberships(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    /**
     * Teams this user belongs to.
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withPivot(['employee_id', 'is_primary', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Projects directly managed by this user.
     */
    public function managedProjects(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Project::class, 'manager_id');
    }

    /**
     * Project memberships.
     */
    public function projectMemberships(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    /**
     * Projects this user is a member of.
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->withPivot(['role', 'is_active', 'joined_at'])
            ->withTimestamps();
    }

    public function projectProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EmployeeProjectProfile::class);
    }

    /**
     * Check if the user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if (empty($permissions)) {
            return true;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->roles()->whereHas('permissions', function ($query) use ($permissions) {
            $query->whereIn('slug', $permissions);
        })->exists();
    }
}
