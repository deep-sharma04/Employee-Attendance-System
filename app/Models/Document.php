<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'document_type_id',
        'title',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'status',
        'rejection_reason',
        'uploaded_by',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'status' => DocumentStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
