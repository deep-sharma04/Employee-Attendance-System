<?php

namespace App\Models;

use App\Enums\ClientStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'company_name',
        'company_code',
        'email',
        'phone',
        'website',
        'address',
        'status',
        'currency',
        'billing_type',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ClientStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ClientContact::class);
    }

    public function primaryContact(): HasOne
    {
        return $this->hasOne(ClientContact::class)->where('is_primary', true);
    }

    public function clientUsers(): HasMany
    {
        return $this->hasMany(ClientUser::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'client_users')
            ->withPivot(['is_primary', 'status'])
            ->withTimestamps();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ClientDocument::class);
    }

    public function sharedDocuments(): HasMany
    {
        return $this->hasMany(ClientDocument::class)->where('is_shared_with_client', true);
    }

    public function communications(): HasMany
    {
        return $this->hasMany(ClientCommunication::class)->latest('communication_date');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ClientStatus::ACTIVE->value);
    }
}
