<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientCommunication extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'client_id',
        'user_id',
        'type',
        'subject',
        'details',
        'communication_date',
    ];

    protected function casts(): array
    {
        return [
            'communication_date' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Badge CSS class for communication type.
     */
    public function typeBadgeClass(): string
    {
        return match ($this->type) {
            'email' => 'bg-blue-50 text-blue-700 border-blue-200',
            'call' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'meeting' => 'bg-purple-50 text-purple-700 border-purple-200',
            'note' => 'bg-amber-50 text-amber-700 border-amber-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }
}
