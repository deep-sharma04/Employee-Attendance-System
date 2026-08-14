<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProjectDocument extends Model
{
    protected $fillable = [
        'project_id',
        'uploaded_by',
        'name',
        'description',
        'is_client_visible',
        'current_version',
    ];

    protected function casts(): array
    {
        return [
            'is_client_visible' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ProjectDocumentVersion::class)->latest('version_number');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(ProjectDocumentVersion::class)->ofMany('version_number', 'max');
    }

    public function scopeClientVisible($query)
    {
        return $query->where('is_client_visible', true);
    }
}