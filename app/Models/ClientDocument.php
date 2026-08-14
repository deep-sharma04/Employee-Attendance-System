<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientDocument extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'client_id',
        'uploaded_by',
        'title',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'is_shared_with_client',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_shared_with_client' => 'boolean',
            'file_size' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeSharedWithClient(Builder $query): Builder
    {
        return $query->where('is_shared_with_client', true);
    }

    /**
     * Formatted file size string (KB / MB).
     */
    public function formattedSize(): string
    {
        if ($this->file_size < 1024) {
            return $this->file_size . ' B';
        }

        if ($this->file_size < 1048576) {
            return round($this->file_size / 1024, 1) . ' KB';
        }

        return round($this->file_size / 1048576, 2) . ' MB';
    }
}
